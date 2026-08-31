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
        $balances = $this->getAllBalances($useCache);
        $key = strtolower($accountType);

        return (float) ($balances[$key] ?? 0.0);
    }

    /**
     * Ambil semua tipe saldo (CASH, HOLDING, TAX) secara concurrent (parallel HTTP pool).
     *
     * @param  bool $useCache
     * @return array{cash: float, holding: float, tax: float, total: float}
     */
    public function getAllBalances(bool $useCache = true): array
    {
        if (!$this->isConfigured()) {
            return ['cash' => 0.0, 'holding' => 0.0, 'tax' => 0.0, 'total' => 0.0];
        }

        $cacheKey = 'xendit_all_balances';
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $responses = Http::pool(fn (\Illuminate\Http\Client\Pool $pool) => [
                $pool->as('cash')->withoutVerifying()->withBasicAuth($this->secretKey, '')->timeout(10)->get("{$this->baseUrl}/balance", ['account_type' => 'CASH']),
                $pool->as('holding')->withoutVerifying()->withBasicAuth($this->secretKey, '')->timeout(10)->get("{$this->baseUrl}/balance", ['account_type' => 'HOLDING']),
                $pool->as('tax')->withoutVerifying()->withBasicAuth($this->secretKey, '')->timeout(10)->get("{$this->baseUrl}/balance", ['account_type' => 'TAX']),
            ]);

            $cash    = (float) ($responses['cash']->successful() ? ($responses['cash']->json('balance') ?? 0) : 0);
            $holding = (float) ($responses['holding']->successful() ? ($responses['holding']->json('balance') ?? 0) : 0);
            $tax     = (float) ($responses['tax']->successful() ? ($responses['tax']->json('balance') ?? 0) : 0);

            $result = [
                'cash'    => $cash,
                'holding' => $holding,
                'tax'     => $tax,
                'total'   => $cash + $holding + $tax,
            ];

            Cache::put($cacheKey, $result, now()->addMinutes(5));
            Cache::put('xendit_balance_CASH', $cash, now()->addMinutes(5));
            Cache::put('xendit_balance_HOLDING', $holding, now()->addMinutes(5));
            Cache::put('xendit_balance_TAX', $tax, now()->addMinutes(5));

            return $result;
        } catch (\Throwable $e) {
            Log::error('[Xendit] Error concurrent balances: ' . $e->getMessage());
            return ['cash' => 0.0, 'holding' => 0.0, 'tax' => 0.0, 'total' => 0.0];
        }
    }

    /**
     * Ambil ringkasan performa finansial Xendit bulan berjalan dan saldo kumulatif.
     *
     * @param  bool $useCache
     * @return array{monthly_inflow: float, monthly_outflow: float, monthly_net: float, cumulative_balance: float, balances: array}
     */
    public function getMonthlySummary(bool $useCache = true): array
    {
        $transactions = $this->getRecentTransactions(50, $useCache);
        $appTz = config('app.timezone', 'Asia/Jakarta');
        $now = now($appTz);
        $thisMonthKey = $now->format('Y-m');

        $thisMonthTrx = $transactions->filter(fn($t) => $t->created_at->format('Y-m') === $thisMonthKey);

        $inflow = (float) $thisMonthTrx->where('is_income', true)->sum('amount');
        $outflow = (float) $thisMonthTrx->where('is_income', false)->sum('amount');
        $net = $inflow - $outflow;

        $balances = $this->getAllBalances($useCache);
        $cumulativeBalance = $balances['total'] ?? 0;

        return [
            'monthly_inflow'     => $inflow,
            'monthly_outflow'    => $outflow,
            'monthly_net'        => $net,
            'cumulative_balance' => $cumulativeBalance,
            'balances'           => $balances,
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
        Cache::forget('xendit_all_balances');
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
            $rawChannel = $trx['channel_code']
                ?? $trx['channel_category']
                ?? 'Xendit';
            $channelCategory = $trx['channel_category'] ?? null;

            // Formatted channel name (e.g. Bank BCA, ShopeePay, etc.)
            $formattedChannel = $this->formatChannelName($rawChannel);
            
            // Clean broad category (e.g. Transfer Bank & VA, E-Wallet, QRIS, etc.)
            $paymentCategory = $this->categorizePaymentMethod($rawChannel, $channelCategory, $type);

            // Determine credit/debit direction
            $isIncome = ($cashflow === 'MONEY_IN') || in_array($type, [
                'PAYMENT', 'DEPOSIT', 'CREDIT', 'TOPUP', 'REFUND_REVERSAL',
                'INVOICE', 'QR_CODE', 'EWALLET', 'DIRECT_DEBIT', 'VIRTUAL_ACCOUNT', 'CARD'
            ]);

            // Generate human-readable narrative message similar to activity logs
            $message = $this->formatTransactionMessage($type, $status, $amount, $formattedChannel, $trx);

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
                'source'           => 'xendit',

                // Display fields
                'type'             => $type,
                'action_label'     => $actionLabel,
                'status'           => $status,
                'amount'           => $amount,
                'fee'              => $fee,
                'net_amount'       => $net,
                'currency'         => $trx['currency'] ?? 'IDR',
                'is_income'        => $isIncome,
                'message'          => $message,

                // Description / reference
                'description'      => $trx['description'] ?? $trx['reference_id'] ?? ($type . ' via ' . $formattedChannel),
                'channel'          => $formattedChannel,
                'raw_channel'      => $rawChannel,
                'payment_category' => $paymentCategory,
                'reference_id'     => $trx['reference_id'] ?? null,
                'xendit_id'        => $trx['id'] ?? null,

                // Timestamps converted to app timezone (WIB/Asia/Jakarta)
                'created_at'       => isset($trx['created'])
                    ? \Carbon\Carbon::parse($trx['created'])->setTimezone($appTz)
                    : \Carbon\Carbon::now($appTz),
                'updated_at'       => isset($trx['updated'])
                    ? \Carbon\Carbon::parse($trx['updated'])->setTimezone($appTz)
                    : \Carbon\Carbon::now($appTz),
            ];
        });
    }

    /**
     * Kategorisasi metode pembayaran / channel Xendit ke kelompok rapi.
     */
    public function categorizePaymentMethod(?string $channelCode, ?string $channelCategory = null, ?string $type = null): string
    {
        $code = strtoupper($channelCode ?? '');
        $cat  = strtoupper($channelCategory ?? '');
        $t    = strtoupper($type ?? '');

        // 1. QRIS
        if (str_contains($code, 'QRIS') || str_contains($code, 'QR_CODE') || $cat === 'QR_CODE') {
            return 'QRIS';
        }

        // 2. E-Wallet
        if (
            str_contains($code, 'SHOPEEPAY') || str_contains($code, 'DANA') ||
            str_contains($code, 'OVO') || str_contains($code, 'LINKAJA') ||
            str_contains($code, 'ASTRAPAY') || str_contains($code, 'JENIUSPAY') ||
            str_contains($code, 'GOPAY') || $cat === 'EWALLET'
        ) {
            return 'E-Wallet';
        }

        // 3. Kartu Kredit / Debit
        if (
            str_contains($code, 'CARD') || str_contains($code, 'VISA') ||
            str_contains($code, 'MASTERCARD') || str_contains($code, 'JCB') ||
            $cat === 'CARDS'
        ) {
            return 'Kartu Kredit / Debit';
        }

        // 4. Retail / Minimarket
        if (
            str_contains($code, 'ALFAMART') || str_contains($code, 'INDOMARET') ||
            str_contains($code, '7ELEVEN') || $cat === 'RETAIL_OUTLET'
        ) {
            return 'Gerai Retail';
        }

        // 5. PayLater
        if (
            str_contains($code, 'KREDIVO') || str_contains($code, 'AKULAKU') ||
            str_contains($code, 'ATOME') || $cat === 'PAYLATER'
        ) {
            return 'PayLater / Cicilan';
        }

        // 6. Transfer Bank / Virtual Account / Disbursement
        if (
            str_contains($code, 'BCA') || str_contains($code, 'BRI') ||
            str_contains($code, 'MANDIRI') || str_contains($code, 'BNI') ||
            str_contains($code, 'CIMB') || str_contains($code, 'PERMATA') ||
            str_contains($code, 'BJB') || str_contains($code, 'BSI') ||
            str_contains($code, 'BNC') || str_contains($code, 'JAGO') ||
            str_contains($code, 'BTPN') || str_contains($code, 'SAHABAT_SAMPOERNA') ||
            $cat === 'BANK' || str_contains($code, 'VIRTUAL_ACCOUNT') ||
            $t === 'DISBURSEMENT' || $t === 'PAYOUT'
        ) {
            return 'Transfer Bank & VA';
        }

        return 'Metode Lainnya';
    }

    /**
     * Format nama channel Xendit agar mudah dibaca pengguna.
     */
    public function formatChannelName(?string $channel): string
    {
        if (empty($channel) || $channel === '-') {
            return 'Xendit Gateway';
        }

        $upper = strtoupper($channel);

        return match (true) {
            str_contains($upper, 'BCA') => 'Bank BCA',
            str_contains($upper, 'BRI') => 'Bank BRI',
            str_contains($upper, 'MANDIRI') => 'Bank Mandiri',
            str_contains($upper, 'BNI') => 'Bank BNI',
            str_contains($upper, 'CIMB') => 'CIMB Niaga',
            str_contains($upper, 'BJB') => 'Bank BJB',
            str_contains($upper, 'PERMATA') => 'Bank Permata',
            str_contains($upper, 'BSI') => 'Bank Syariah Indonesia (BSI)',
            str_contains($upper, 'JAGO') => 'Bank Jago',
            str_contains($upper, 'SHOPEEPAY') => 'ShopeePay',
            str_contains($upper, 'DANA') => 'DANA',
            str_contains($upper, 'OVO') => 'OVO',
            str_contains($upper, 'LINKAJA') => 'LinkAja',
            str_contains($upper, 'ASTRAPAY') => 'AstraPay',
            str_contains($upper, 'GOPAY') => 'GoPay',
            str_contains($upper, 'QRIS') => 'QRIS',
            str_contains($upper, 'ALFAMART') => 'Alfamart',
            str_contains($upper, 'INDOMARET') => 'Indomaret',
            str_contains($upper, 'KREDIVO') => 'Kredivo',
            str_contains($upper, 'AKULAKU') => 'Akulaku',
            $upper === 'BANK' => 'Transfer Bank',
            default => trim(str_replace(['ID_', '_'], ['', ' '], $channel)) ?: 'Xendit',
        };
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

        // Payment Method Category Breakdown (Clean Grouping)
        $categoryTotals = [];
        foreach ($transactions as $trx) {
            $cat = $trx->payment_category ?: 'Metode Lainnya';
            if (!isset($categoryTotals[$cat])) {
                $categoryTotals[$cat] = 0.0;
            }
            $categoryTotals[$cat] += $trx->amount;
        }

        // Sort descending by total amount
        arsort($categoryTotals);

        return [
            'chart_trends'      => $periods,
            'channel_breakdown' => [
                'labels' => array_keys($categoryTotals),
                'totals' => array_values($categoryTotals),
            ],
            'total_inflow'      => $transactions->where('is_income', true)->sum('amount'),
            'total_outflow'     => $transactions->where('is_income', false)->sum('amount'),
        ];
    }

    /**
     * Buat invoice pembayaran Xendit untuk penambahan saldo client.
     */
    public function createInvoice(string $externalId, float $amount, string $description, ?string $payerEmail = null, ?string $successRedirectUrl = null): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Xendit Secret Key belum dikonfigurasi di server.');
        }

        $payload = [
            'external_id' => $externalId,
            'amount' => $amount,
            'description' => $description,
            'invoice_duration' => 86400, // 24 jam
            'currency' => 'IDR',
        ];

        if ($payerEmail) {
            $payload['payer_email'] = $payerEmail;
        }

        if ($successRedirectUrl) {
            $payload['success_redirect_url'] = $successRedirectUrl;
        }

        $response = Http::withoutVerifying()
            ->withBasicAuth($this->secretKey, '')
            ->timeout(15)
            ->post("{$this->baseUrl}/v2/invoices", $payload);

        if (!$response->successful()) {
            Log::error('[Xendit] Gagal membuat invoice: ' . $response->status() . ' | ' . $response->body());
            throw new \RuntimeException('Gagal membuat invoice Xendit: ' . ($response->json('message') ?? $response->body()));
        }

        return $response->json();
    }
}




