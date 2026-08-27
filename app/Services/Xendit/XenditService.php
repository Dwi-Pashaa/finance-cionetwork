<?php

namespace App\Services\Xendit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class XenditService
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.xendit.secret_key', '');
        $this->baseUrl   = config('services.xendit.base_url', 'https://api.xendit.co');
    }

    /**
     * Periksa apakah Xendit Secret Key sudah dikonfigurasi.
     */
    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Ambil saldo akun Xendit.
     *
     * @param string $accountType 'CASH' | 'HOLDING' | 'TAX'
     * @param bool   $useCache    Gunakan cache 5 menit agar tidak spam ke API
     * @return float
     */
    public function getBalance(string $accountType = 'CASH', bool $useCache = true): float
    {
        if (!$this->isConfigured()) {
            Log::warning('[Xendit] Secret Key belum dikonfigurasi pada .env (XENDIT_SECRET_KEY).');
            return 0.0;
        }

        $cacheKey = "xendit_balance_{$accountType}";

        if ($useCache && Cache::has($cacheKey)) {
            return (float) Cache::get($cacheKey);
        }

        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->timeout(10)
                ->get("{$this->baseUrl}/balance", [
                    'account_type' => $accountType,
                ]);

            if ($response->successful()) {
                $balance = (float) ($response->json('balance') ?? 0);

                // Cache saldo selama 5 menit
                Cache::put($cacheKey, $balance, now()->addMinutes(5));

                return $balance;
            }

            Log::error('[Xendit] Gagal mengambil saldo. Status: ' . $response->status() . ' | Body: ' . $response->body());
            return 0.0;

        } catch (\Throwable $e) {
            Log::error('[Xendit] Error koneksi API: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Ambil semua tipe saldo (CASH, HOLDING, TAX) sekaligus.
     *
     * @return array{cash: float, holding: float, tax: float, total: float}
     */
    public function getAllBalances(): array
    {
        $cash    = $this->getBalance('CASH');
        $holding = $this->getBalance('HOLDING');
        $tax     = $this->getBalance('TAX');

        return [
            'cash'    => $cash,
            'holding' => $holding,
            'tax'     => $tax,
            'total'   => $cash + $holding + $tax,
        ];
    }

    /**
     * Ambil daftar transaksi dari Xendit Transaction View API.
     *
     * @param  int    $limit   Jumlah transaksi yang diambil (max 100)
     * @param  array  $filters Tambahan filter: types, statuses, created[gte], created[lte], dll.
     * @return array
     */
    public function getTransactions(int $limit = 10, array $filters = []): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $cacheKey = 'xendit_transactions_' . md5(json_encode(array_merge($filters, ['limit' => $limit])));

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(3), function () use ($limit, $filters) {
            try {
                $response = Http::withBasicAuth($this->secretKey, '')
                    ->timeout(10)
                    ->get("{$this->baseUrl}/transactions", array_merge([
                        'limit' => $limit,
                    ], $filters));

                if ($response->successful()) {
                    return $response->json('data', []);
                }

                Log::error('[Xendit] Gagal mengambil transaksi. Status: ' . $response->status() . ' | Body: ' . $response->body());
                return [];

            } catch (\Throwable $e) {
                Log::error('[Xendit] Error ambil transaksi: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Ambil transaksi terbaru Xendit untuk digabung di dashboard feed log.
     * Otomatis normalize ke format yang mudah di-render di Blade dengan pesan deskriptif.
     *
     * @param  int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getRecentTransactions(int $limit = 10): \Illuminate\Support\Collection
    {
        $raw = $this->getTransactions($limit);

        return collect($raw)->map(function (array $trx) {
            $type    = strtoupper($trx['type'] ?? 'UNKNOWN');
            $status  = strtoupper($trx['status'] ?? 'UNKNOWN');
            $amount  = (float) ($trx['amount'] ?? 0);
            $fee     = (float) ($trx['fee'] ?? 0);
            $net     = (float) ($trx['net_amount'] ?? ($amount - $fee));
            $channel = $trx['channel_code']
                ?? $trx['channel_category']
                ?? 'Xendit';

            // Determine credit/debit direction
            $isIncome = in_array($type, ['PAYMENT', 'DEPOSIT', 'CREDIT', 'TOPUP', 'REFUND_REVERSAL']);

            // Generate human-readable narrative message similar to activity logs
            $message = $this->formatTransactionMessage($type, $status, $amount, $channel, $trx);

            $actionLabel = match ($type) {
                'PAYMENT'      => 'PEMBAYARAN',
                'DISBURSEMENT' => 'PENARIKAN',
                'REFUND'       => 'PENGEMBALIAN',
                'TRANSFER'     => 'TRANSFER',
                'TOPUP'        => 'TOP UP',
                'FEE'          => 'BIAYA ADMIN',
                default        => $type,
            };

            return (object) [
                // Source tag
                'source'        => 'xendit',

                // Display fields
                'type'          => $type,
                'action_label'  => $actionLabel,
                'status'        => $status,
                'amount'        => $amount,
                'fee'           => $fee,
                'net_amount'    => $net,
                'currency'      => $trx['currency'] ?? 'IDR',
                'is_income'     => $isIncome,
                'message'       => $message,

                // Description / reference
                'description'   => $trx['description'] ?? $trx['reference_id'] ?? ($type . ' via ' . $channel),
                'channel'       => $channel,
                'reference_id'  => $trx['reference_id'] ?? null,
                'xendit_id'     => $trx['id'] ?? null,

                // Timestamps
                'created_at'    => isset($trx['created'])
                    ? \Carbon\Carbon::parse($trx['created'])
                    : \Carbon\Carbon::now(),
                'updated_at'    => isset($trx['updated'])
                    ? \Carbon\Carbon::parse($trx['updated'])
                    : \Carbon\Carbon::now(),
            ];
        });
    }

    /**
     * Buat kalimat log interaktif deskriptif dari data transaksi Xendit.
     */
    private function formatTransactionMessage(string $type, string $status, float $amount, string $channel, array $trx): string
    {
        $formattedAmount = 'Rp ' . number_format($amount, 0, ',', '.');
        $reference = !empty($trx['reference_id']) ? " (Ref: {$trx['reference_id']})" : '';

        return match ($type) {
            'PAYMENT' => match ($status) {
                'SUCCESS' => "Pembayaran sebesar {$formattedAmount} berhasil diterima via {$channel}{$reference}",
                'PENDING' => "Menunggu pembayaran sebesar {$formattedAmount} melalui {$channel}{$reference}",
                'FAILED', 'EXPIRED' => "Pembayaran sebesar {$formattedAmount} via {$channel} gagal / kedaluwarsa{$reference}",
                default   => "Transaksi pembayaran {$formattedAmount} via {$channel} [{$status}]{$reference}",
            },
            'DISBURSEMENT', 'PAYOUT' => match ($status) {
                'SUCCESS' => "Dana payout / transfer sebesar {$formattedAmount} berhasil dikirim ke rekening {$channel}{$reference}",
                'PENDING' => "Pengiriman dana sebesar {$formattedAmount} ke {$channel} sedang diproses{$reference}",
                'FAILED'  => "Pengiriman dana sebesar {$formattedAmount} ke {$channel} gagal diproses{$reference}",
                default   => "Disbursement dana sebesar {$formattedAmount} ke {$channel} [{$status}]{$reference}",
            },
            'REFUND' => "Pengembalian dana (Refund) sebesar {$formattedAmount} via {$channel}{$reference}",
            'TRANSFER' => "Transfer saldo internal Xendit sebesar {$formattedAmount}{$reference}",
            'TOPUP' => "Top-up saldo Xendit sebesar {$formattedAmount} melalui {$channel}{$reference}",
            default => "Aktivitas transaksi {$type} sebesar {$formattedAmount} via {$channel}{$reference}",
        };
    }

    /**
     * Ambil data tren dan distribusi untuk grafik Transaksi Xendit.
     *
     * @return array{trends: array, channel_breakdown: array, total_inflow: float, total_outflow: float}
     */
    public function getXenditChartData(): array
    {
        $transactions = $this->getRecentTransactions(30);

        if ($transactions->isEmpty()) {
            return [
                'trends' => [
                    'labels'   => ['Hari 1', 'Hari 2', 'Hari 3', 'Hari 4', 'Hari 5', 'Hari 6', 'Hari 7'],
                    'inflow'   => [0, 0, 0, 0, 0, 0, 0],
                    'outflow'  => [0, 0, 0, 0, 0, 0, 0],
                    'net'      => [0, 0, 0, 0, 0, 0, 0],
                ],
                'channel_breakdown' => [
                    'labels' => [],
                    'totals' => [],
                ],
                'total_inflow'  => 0.0,
                'total_outflow' => 0.0,
            ];
        }

        // Group by Date for Trend
        $groupedByDate = [];
        $channelTotals = [];
        $totalInflow   = 0.0;
        $totalOutflow  = 0.0;

        foreach ($transactions as $trx) {
            $dateLabel = $trx->created_at->format('d M');
            if (!isset($groupedByDate[$dateLabel])) {
                $groupedByDate[$dateLabel] = ['inflow' => 0.0, 'outflow' => 0.0];
            }

            if ($trx->is_income) {
                $groupedByDate[$dateLabel]['inflow'] += $trx->amount;
                $totalInflow += $trx->amount;
            } else {
                $groupedByDate[$dateLabel]['outflow'] += $trx->amount;
                $totalOutflow += $trx->amount;
            }

            // Channel aggregation
            $ch = $trx->channel ?: 'Lainnya';
            if (!isset($channelTotals[$ch])) {
                $channelTotals[$ch] = 0.0;
            }
            $channelTotals[$ch] += $trx->amount;
        }

        // Prepare chart arrays
        $labels  = array_keys($groupedByDate);
        $inflows = [];
        $outflows = [];
        $nets    = [];

        foreach ($groupedByDate as $d => $vals) {
            $inflows[]  = $vals['inflow'];
            $outflows[] = $vals['outflow'];
            $nets[]     = $vals['inflow'] - $vals['outflow'];
        }

        return [
            'trends' => [
                'labels'  => array_reverse($labels),
                'inflow'  => array_reverse($inflows),
                'outflow' => array_reverse($outflows),
                'net'     => array_reverse($nets),
            ],
            'channel_breakdown' => [
                'labels' => array_keys($channelTotals),
                'totals' => array_values($channelTotals),
            ],
            'total_inflow'  => $totalInflow,
            'total_outflow' => $totalOutflow,
        ];
    }
}


