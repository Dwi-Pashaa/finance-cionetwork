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
            $response = Http::withoutVerifying()
                ->withBasicAuth($this->secretKey, '')
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
     * @param  bool   $useCache
     * @return array
     */
    public function getTransactions(int $limit = 10, array $filters = [], bool $useCache = true): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        // Xendit API Transaction View mengharuskan query limit <= 50
        $limit = min(max($limit, 1), 50);

        $cacheKey = 'xendit_transactions_' . md5(json_encode(array_merge($filters, ['limit' => $limit])));

        if (!$useCache) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($limit, $filters) {
            try {
                $response = Http::withoutVerifying()
                    ->withBasicAuth($this->secretKey, '')
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
     * Hapus semua cache terkait Xendit (saldo dan riwayat transaksi/grafik).
     */
    public function clearCache(): void
    {
        Cache::forget('xendit_balance_CASH');
        Cache::forget('xendit_balance_HOLDING');
        Cache::forget('xendit_balance_TAX');

        // Hapus cache transaksi & grafik untuk limit umum
        Cache::forget('xendit_transactions_' . md5(json_encode(['limit' => 10])));
        Cache::forget('xendit_transactions_' . md5(json_encode(['limit' => 50])));
        Cache::forget('xendit_transactions_' . md5(json_encode(['limit' => 100])));
    }

    /**
     * Ambil transaksi terbaru Xendit untuk digabung di dashboard feed log.
     * Otomatis normalize ke format yang mudah di-render di Blade dengan pesan deskriptif.
     *
     * @param  int  $limit
     * @param  bool $useCache
     * @return \Illuminate\Support\Collection
     */
    public function getRecentTransactions(int $limit = 10, bool $useCache = true): \Illuminate\Support\Collection
    {
        $raw = $this->getTransactions($limit, [], $useCache);
        $appTz = config('app.timezone', 'Asia/Jakarta');

        return collect($raw)->map(function (array $trx) use ($appTz) {
            $type     = strtoupper($trx['type'] ?? 'UNKNOWN');
            $status   = strtoupper($trx['status'] ?? 'UNKNOWN');
            $cashflow = strtoupper($trx['cashflow'] ?? '');
            $amount   = (float) ($trx['amount'] ?? 0);
            $fee      = (float) ($trx['fee'] ?? 0);
            $net      = (float) ($trx['net_amount'] ?? ($amount - $fee));
            $channel  = $trx['channel_code']
                ?? $trx['channel_category']
                ?? 'Xendit';

            // Determine credit/debit direction
            $isIncome = ($cashflow === 'MONEY_IN') || in_array($type, [
                'PAYMENT', 'DEPOSIT', 'CREDIT', 'TOPUP', 'REFUND_REVERSAL',
                'INVOICE', 'QR_CODE', 'EWALLET', 'DIRECT_DEBIT', 'VIRTUAL_ACCOUNT', 'CARD'
            ]);

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

                // Timestamps converted to app timezone (WIB/Asia/Jakarta)
                'created_at'    => isset($trx['created'])
                    ? \Carbon\Carbon::parse($trx['created'])->setTimezone($appTz)
                    : \Carbon\Carbon::now($appTz),
                'updated_at'    => isset($trx['updated'])
                    ? \Carbon\Carbon::parse($trx['updated'])->setTimezone($appTz)
                    : \Carbon\Carbon::now($appTz),
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
     * Ambil data tren multi-periode dan distribusi untuk grafik Transaksi Xendit.
     * Mendukung filter trading style: 1HR, 7HR, 1BLN, 6BLN, YTD, 1TH, 5TH, Maks.
     *
     * @param  bool $useCache
     * @return array
     */
    public function getXenditChartData(bool $useCache = true): array
    {
        $transactions = $this->getRecentTransactions(50, $useCache);
        $appTz = config('app.timezone', 'Asia/Jakarta');
        $now = now($appTz);

        $periods = [
            '1hr'  => ['labels' => [], 'inflow' => [], 'outflow' => [], 'net' => []],
            '7hr'  => ['labels' => [], 'inflow' => [], 'outflow' => [], 'net' => []],
            '1bln' => ['labels' => [], 'inflow' => [], 'outflow' => [], 'net' => []],
            '6bln' => ['labels' => [], 'inflow' => [], 'outflow' => [], 'net' => []],
            'ytd'  => ['labels' => [], 'inflow' => [], 'outflow' => [], 'net' => []],
            '1th'  => ['labels' => [], 'inflow' => [], 'outflow' => [], 'net' => []],
            '5th'  => ['labels' => [], 'inflow' => [], 'outflow' => [], 'net' => []],
            'maks' => ['labels' => [], 'inflow' => [], 'outflow' => [], 'net' => []],
        ];

        // 1. 1HR (Breakdown Per Jam Hari Ini 00:00 - 23:00)
        $todayStr = $now->format('Y-m-d');
        $todayTrx = $transactions->filter(fn($t) => $t->created_at->format('Y-m-d') === $todayStr);
        for ($h = 0; $h < 24; $h++) {
            $hLabel = sprintf('%02d:00', $h);
            $hTrx = $todayTrx->filter(fn($t) => (int) $t->created_at->format('H') === $h);
            $inc = $hTrx->where('is_income', true)->sum('amount');
            $exp = $hTrx->where('is_income', false)->sum('amount');

            $periods['1hr']['labels'][]  = $hLabel;
            $periods['1hr']['inflow'][]  = (float) $inc;
            $periods['1hr']['outflow'][] = (float) $exp;
            $periods['1hr']['net'][]     = (float) ($inc - $exp);
        }

        // 2. 7HR (7 Hari Terakhir)
        for ($i = 6; $i >= 0; $i--) {
            $dDate = $now->copy()->subDays($i);
            $dStr = $dDate->format('Y-m-d');
            $dTrx = $transactions->filter(fn($t) => $t->created_at->format('Y-m-d') === $dStr);
            $inc = $dTrx->where('is_income', true)->sum('amount');
            $exp = $dTrx->where('is_income', false)->sum('amount');

            $periods['7hr']['labels'][]  = $dDate->translatedFormat('d M');
            $periods['7hr']['inflow'][]  = (float) $inc;
            $periods['7hr']['outflow'][] = (float) $exp;
            $periods['7hr']['net'][]     = (float) ($inc - $exp);
        }

        // 3. 1BLN (30 Hari Terakhir - Agregasi 3 Hari)
        for ($i = 29; $i >= 0; $i -= 3) {
            $dStart = $now->copy()->subDays($i)->startOfDay();
            $dEnd = $dStart->copy()->addDays(2)->endOfDay();
            $dTrx = $transactions->filter(fn($t) => $t->created_at->between($dStart, $dEnd));
            $inc = $dTrx->where('is_income', true)->sum('amount');
            $exp = $dTrx->where('is_income', false)->sum('amount');

            $periods['1bln']['labels'][]  = $dStart->translatedFormat('d M');
            $periods['1bln']['inflow'][]  = (float) $inc;
            $periods['1bln']['outflow'][] = (float) $exp;
            $periods['1bln']['net'][]     = (float) ($inc - $exp);
        }

        // 4. 6BLN (6 Bulan Terakhir)
        for ($i = 5; $i >= 0; $i--) {
            $mDate = $now->copy()->subMonths($i);
            $mKey = $mDate->format('Y-m');
            $mTrx = $transactions->filter(fn($t) => $t->created_at->format('Y-m') === $mKey);
            $inc = $mTrx->where('is_income', true)->sum('amount');
            $exp = $mTrx->where('is_income', false)->sum('amount');

            $periods['6bln']['labels'][]  = $mDate->translatedFormat('M Y');
            $periods['6bln']['inflow'][]  = (float) $inc;
            $periods['6bln']['outflow'][] = (float) $exp;
            $periods['6bln']['net'][]     = (float) ($inc - $exp);
        }

        // 5. YTD (Year To Date)
        $startOfYear = $now->copy()->startOfYear();
        $monthsCount = $now->month;
        for ($m = 1; $m <= $monthsCount; $m++) {
            $mDate = $now->copy()->month($m);
            $mKey = $mDate->format('Y-m');
            $mTrx = $transactions->filter(fn($t) => $t->created_at->format('Y-m') === $mKey);
            $inc = $mTrx->where('is_income', true)->sum('amount');
            $exp = $mTrx->where('is_income', false)->sum('amount');

            $periods['ytd']['labels'][]  = $mDate->translatedFormat('M Y');
            $periods['ytd']['inflow'][]  = (float) $inc;
            $periods['ytd']['outflow'][] = (float) $exp;
            $periods['ytd']['net'][]     = (float) ($inc - $exp);
        }

        // 6. 1TH (12 Bulan Terakhir)
        for ($i = 11; $i >= 0; $i--) {
            $mDate = $now->copy()->subMonths($i);
            $mKey = $mDate->format('Y-m');
            $mTrx = $transactions->filter(fn($t) => $t->created_at->format('Y-m') === $mKey);
            $inc = $mTrx->where('is_income', true)->sum('amount');
            $exp = $mTrx->where('is_income', false)->sum('amount');

            $periods['1th']['labels'][]  = $mDate->translatedFormat('M Y');
            $periods['1th']['inflow'][]  = (float) $inc;
            $periods['1th']['outflow'][] = (float) $exp;
            $periods['1th']['net'][]     = (float) ($inc - $exp);
        }

        // 7. 5TH (5 Tahun Terakhir)
        for ($i = 4; $i >= 0; $i--) {
            $yDate = $now->copy()->subYears($i);
            $yKey = $yDate->format('Y');
            $yTrx = $transactions->filter(fn($t) => $t->created_at->format('Y') === $yKey);
            $inc = $yTrx->where('is_income', true)->sum('amount');
            $exp = $yTrx->where('is_income', false)->sum('amount');

            $periods['5th']['labels'][]  = $yKey;
            $periods['5th']['inflow'][]  = (float) $inc;
            $periods['5th']['outflow'][] = (float) $exp;
            $periods['5th']['net'][]     = (float) ($inc - $exp);
        }

        // 8. Maks (All Data Grouped by Month)
        $periods['maks'] = $periods['1th'];

        // Channel Breakdown
        $channelTotals = [];
        foreach ($transactions as $trx) {
            $ch = $trx->channel ?: 'Lainnya';
            if (!isset($channelTotals[$ch])) {
                $channelTotals[$ch] = 0.0;
            }
            $channelTotals[$ch] += $trx->amount;
        }

        return [
            'chart_trends'      => $periods,
            'channel_breakdown' => [
                'labels' => array_keys($channelTotals),
                'totals' => array_values($channelTotals),
            ],
            'total_inflow'      => $transactions->where('is_income', true)->sum('amount'),
            'total_outflow'     => $transactions->where('is_income', false)->sum('amount'),
        ];
    }
}



