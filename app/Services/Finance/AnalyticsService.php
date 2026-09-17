<?php

namespace App\Services\Finance;

use App\Models\ApiClient;
use App\Models\ApiClientBalance;
use App\Models\Expense;
use App\Models\Income;
use App\Services\Xendit\XenditService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AnalyticsService
{
    public function __construct(
        private XenditService $xenditService
    ) {}

    /**
     * Ringkasan Eksekutif dan Saldo Konsolidasi Multi-Web + Xendit
     */
    public function getOverview(?string $clientCode = null, bool $useCache = true): array
    {
        $cacheKey = 'cio_analytics_overview_' . ($clientCode ?: 'ALL') . '_' . now('Asia/Jakarta')->format('YmdH_i');

        if ($useCache) {
            return Cache::remember($cacheKey, 60, fn () => $this->computeOverview($clientCode));
        }

        return $this->computeOverview($clientCode);
    }

    private function computeOverview(?string $clientCode = null): array
    {
        $appTz = config('app.timezone', 'Asia/Jakarta');
        $now = now($appTz);

        $clientCode = ($clientCode && strtoupper($clientCode) !== 'ALL') ? $clientCode : null;

        // 1. Kalkulasi Saldo Dua Kantong
        $manualBalance = 0.0;
        $xenditBalance = 0.0;
        $activeClientsCount = 0;

        if ($clientCode) {
            $client = ApiClient::where('code', $clientCode)->first();
            if ($client) {
                $bal = $client->balance;
                $manualBalance = (float) ($bal?->balance_manual ?? 0);
                $xenditBalance = (float) ($bal?->balance_xendit ?? 0);
                $activeClientsCount = $client->isActive() ? 1 : 0;
            }
        } else {
            $manualBalance = (float) ApiClientBalance::sum('balance_manual');
            $xenditBalance = (float) ApiClientBalance::sum('balance_xendit');
            $activeClientsCount = ApiClient::where('status', 'active')->count();

            // Jika ada saldo live di akun Xendit dan belum tercatat di DB
            if ($this->xenditService->isConfigured()) {
                $liveXenditBalances = $this->xenditService->getAllBalances();
                $liveTotal = (float) ($liveXenditBalances['total'] ?? 0);
                if ($liveTotal > 0 && $xenditBalance == 0) {
                    $xenditBalance = $liveTotal;
                }
            }
        }

        $totalBalance = $manualBalance + $xenditBalance;

        // 2. Bulan Berjalan
        $startCurrent = $now->copy()->startOfMonth();
        $endCurrent = $now->copy()->endOfMonth();
        $currentFinancials = $this->calculatePeriodFinancials($startCurrent, $endCurrent, $clientCode);

        // 3. Bulan Lalu
        $startPrev = $now->copy()->subMonth()->startOfMonth();
        $endPrev = $now->copy()->subMonth()->endOfMonth();
        $prevFinancials = $this->calculatePeriodFinancials($startPrev, $endPrev, $clientCode);

        $xenditStatus = $this->xenditService->isConfigured() ? 'connected' : 'offline';

        return [
            'balance' => [
                'total' => round($totalBalance, 2),
                'manual' => round($manualBalance, 2),
                'xendit' => round($xenditBalance, 2),
            ],
            'current_month' => [
                'revenue' => round($currentFinancials['revenue'], 2),
                'expenses' => round($currentFinancials['expenses'], 2),
                'net_profit' => round($currentFinancials['net_profit'], 2),
                'profit_margin' => round($currentFinancials['profit_margin'], 2),
            ],
            'previous_month' => [
                'revenue' => round($prevFinancials['revenue'], 2),
                'expenses' => round($prevFinancials['expenses'], 2),
                'net_profit' => round($prevFinancials['net_profit'], 2),
            ],
            'active_clients_count' => $activeClientsCount,
            'xendit_status' => $xenditStatus,
        ];
    }

    /**
     * Dataset Time-Series Grafik Multi-Web + Xendit
     */
    public function getChart(string $range = '7d', string $interval = 'daily', ?string $clientCode = null, bool $useCache = true): array
    {
        $clientKey = $clientCode ? strtoupper($clientCode) : 'ALL';
        $cacheKey = "cio_analytics_chart_{$range}_{$interval}_{$clientKey}_" . now('Asia/Jakarta')->format('YmdH_i');

        if ($useCache) {
            return Cache::remember($cacheKey, 60, fn () => $this->computeChart($range, $interval, $clientCode));
        }

        return $this->computeChart($range, $interval, $clientCode);
    }

    private function computeChart(string $range = '7d', string $interval = 'daily', ?string $clientCode = null): array
    {
        $appTz = config('app.timezone', 'Asia/Jakarta');
        $now = now($appTz);
        $clientCode = ($clientCode && strtoupper($clientCode) !== 'ALL') ? $clientCode : null;

        $buckets = $this->generateTimeBuckets($range, $interval, $now);

        $categories = [];
        $inflows = [];
        $outflows = [];
        $netProfits = [];

        foreach ($buckets as $bucket) {
            $categories[] = $bucket['label'];
            $fin = $this->calculatePeriodFinancials($bucket['start'], $bucket['end'], $clientCode);

            $in = round($fin['revenue'], 2);
            $out = round($fin['expenses'], 2);
            $net = round($in - $out, 2);

            $inflows[] = $in;
            $outflows[] = $out;
            $netProfits[] = $net;
        }

        $totalInflow = array_sum($inflows);
        $totalOutflow = array_sum($outflows);
        $netProfit = $totalInflow - $totalOutflow;
        $profitMarginPct = ($totalInflow > 0) ? round(($netProfit / $totalInflow) * 100, 2) : 0.0;

        return [
            'categories' => $categories,
            'series' => [
                [
                    'name' => 'Pemasukan (Inflow)',
                    'data' => $inflows,
                ],
                [
                    'name' => 'Pengeluaran (Outflow)',
                    'data' => $outflows,
                ],
                [
                    'name' => 'Laba Bersih (Net Profit)',
                    'data' => $netProfits,
                ],
            ],
            'summary' => [
                'total_inflow' => round($totalInflow, 2),
                'total_outflow' => round($totalOutflow, 2),
                'net_profit' => round($netProfit, 2),
                'profit_margin_pct' => round($profitMarginPct, 2),
            ],
        ];
    }

    /**
     * Indikator Pertumbuhan & Valuasi Bisnis
     */
    public function getGrowth(?string $clientCode = null, bool $useCache = true): array
    {
        $clientKey = $clientCode ? strtoupper($clientCode) : 'ALL';
        $cacheKey = "cio_analytics_growth_{$clientKey}_" . now('Asia/Jakarta')->format('YmdH_i');

        if ($useCache) {
            return Cache::remember($cacheKey, 60, fn () => $this->computeGrowth($clientCode));
        }

        return $this->computeGrowth($clientCode);
    }

    private function computeGrowth(?string $clientCode = null): array
    {
        $appTz = config('app.timezone', 'Asia/Jakarta');
        $now = now($appTz);
        $clientCode = ($clientCode && strtoupper($clientCode) !== 'ALL') ? $clientCode : null;

        $overview = $this->computeOverview($clientCode);
        $totalBalance = (float) $overview['balance']['total'];

        // 1. Current vs Previous Month
        $curRev = (float) $overview['current_month']['revenue'];
        $curExp = (float) $overview['current_month']['expenses'];
        $curNet = (float) $overview['current_month']['net_profit'];

        $prevRev = (float) $overview['previous_month']['revenue'];
        $prevExp = (float) $overview['previous_month']['expenses'];

        // MoM Growth Rate
        if ($prevRev > 0) {
            $growthMom = round((($curRev - $prevRev) / $prevRev) * 100, 2);
        } else {
            $growthMom = ($curRev > 0) ? 100.00 : 0.00;
        }

        // 2. Current Year vs Previous Year
        $startCurYear = $now->copy()->startOfYear();
        $endCurYear = $now->copy()->endOfYear();
        $curYearFin = $this->calculatePeriodFinancials($startCurYear, $endCurYear, $clientCode);

        $startPrevYear = $now->copy()->subYear()->startOfYear();
        $endPrevYear = $now->copy()->subYear()->endOfYear();
        $prevYearFin = $this->calculatePeriodFinancials($startPrevYear, $endPrevYear, $clientCode);

        $curYearRev = (float) $curYearFin['revenue'];
        $prevYearRev = (float) $prevYearFin['revenue'];

        if ($prevYearRev > 0) {
            $growthYoy = round((($curYearRev - $prevYearRev) / $prevYearRev) * 100, 2);
        } else {
            $growthYoy = ($curYearRev > 0) ? 100.00 : 0.00;
        }

        // 3. Profitability Metrics
        $netMargin = ($curRev > 0) ? round(($curNet / $curRev) * 100, 2) : 0.00;
        $grossMargin = ($curRev > 0) ? max(round((($curRev - ($curExp * 0.35)) / $curRev) * 100, 2), $netMargin) : 0.00;
        
        $monthlyBurnRate = $curExp > 0 ? $curExp : ($prevExp > 0 ? $prevExp : 0.00);
        $runwayMonths = ($monthlyBurnRate > 0) ? round($totalBalance / $monthlyBurnRate, 1) : ($totalBalance > 0 ? 99.0 : 0.0);

        // 4. Financial Health Score (0 - 100)
        $score = 0;
        // Margin score (max 35)
        if ($netMargin >= 60) {
            $score += 35;
        } elseif ($netMargin >= 35) {
            $score += 28;
        } elseif ($netMargin >= 15) {
            $score += 20;
        } elseif ($netMargin > 0) {
            $score += 10;
        }

        // Growth score (max 25)
        if ($growthMom >= 20 || $growthYoy >= 50) {
            $score += 25;
        } elseif ($growthMom >= 5 || $growthYoy >= 20) {
            $score += 18;
        } elseif ($growthMom >= 0) {
            $score += 12;
        }

        // Runway score (max 25)
        if ($runwayMonths >= 12) {
            $score += 25;
        } elseif ($runwayMonths >= 6) {
            $score += 20;
        } elseif ($runwayMonths >= 3) {
            $score += 12;
        } elseif ($runwayMonths > 0) {
            $score += 5;
        }

        // Solvency score (max 15)
        if ($totalBalance > 0) {
            $score += 15;
        }

        $healthScore = min(100, max(0, $score));

        // Status
        $status = match (true) {
            $healthScore >= 80 => 'Healthy & Expanding',
            $healthScore >= 60 => 'Stable & Sustainable',
            $healthScore >= 40 => 'Moderate / Caution',
            default => 'Critical / High Burn',
        };

        // 5. Estimated Company Valuation
        $annualizedRev = $curRev * 12;
        $valuation = ($annualizedRev * 2.5) + $totalBalance;
        $estimatedValuation = max($valuation, 100000000.00);

        return [
            'growth_rate_mom' => $growthMom,
            'growth_rate_yoy' => $growthYoy,
            'financial_health_score' => $healthScore,
            'status' => $status,
            'profitability' => [
                'gross_margin' => $grossMargin,
                'net_margin' => $netMargin,
                'monthly_burn_rate' => round($monthlyBurnRate, 2),
                'runway_months' => $runwayMonths,
            ],
            'estimated_company_valuation' => round($estimatedValuation, 2),
        ];
    }

    /**
     * Hitung Pemasukan dan Pengeluaran periode tertentu (Konsolidasi DB Multi-Web + Xendit)
     */
    public function calculatePeriodFinancials(CarbonInterface $start, CarbonInterface $end, ?string $clientCode = null): array
    {
        $startDateStr = $start->toDateString();
        $endDateStr = $end->toDateString();

        $revenue = 0.0;
        $expenses = 0.0;

        $inflowEvents = ['xendit_topup', 'refund_balance', 'invoice.paid', 'xendit_payment', 'payment.succeeded', 'topup.succeeded'];
        $outflowEvents = ['deduct_balance', 'expense.created', 'xendit_disbursement', 'disbursement.succeeded', 'payout.succeeded'];

        if ($clientCode) {
            // Filter aktivitas client tertentu
            $activities = Activity::query()
                ->where('log_name', 'external_finance')
                ->where('properties->client_code', $clientCode)
                ->whereBetween('created_at', [$start, $end])
                ->get();

            foreach ($activities as $act) {
                $amt = (float) ($act->getExtraProperty('amount') ?? 0);
                $subjectType = strtolower($act->getExtraProperty('subject_type') ?? '');
                $event = strtolower($act->event ?? '');

                if ($subjectType === 'income' || in_array($event, $inflowEvents)) {
                    $revenue += $amt;
                } elseif ($subjectType === 'expense' || in_array($event, $outflowEvents)) {
                    $expenses += $amt;
                }
            }
        } else {
            // 1. Pemasukan Kas Internal
            $revenue += (float) Income::whereBetween('transaction_date', [$startDateStr, $endDateStr])->sum('amount');

            // 2. Pengeluaran Kas Internal (beserta admin fee)
            $expenses += (float) Expense::whereBetween('transaction_date', [$startDateStr, $endDateStr])
                ->sum(DB::raw('amount + COALESCE(admin_fee_amount, 0)'));

            // 3. Log External Activity dari Semua Web & Xendit
            $activities = Activity::query()
                ->where('log_name', 'external_finance')
                ->whereBetween('created_at', [$start, $end])
                ->get();

            $loggedXenditIds = [];
            foreach ($activities as $act) {
                $amt = (float) ($act->getExtraProperty('amount') ?? 0);
                $subjectType = strtolower($act->getExtraProperty('subject_type') ?? '');
                $event = strtolower($act->event ?? '');

                $xId = $act->getExtraProperty('xendit_id');
                $refId = $act->getExtraProperty('reference_id') ?? $act->getExtraProperty('subject_external_id');
                if ($xId) {
                    $loggedXenditIds[$xId] = true;
                }
                if ($refId) {
                    $loggedXenditIds[$refId] = true;
                }

                if ($subjectType === 'income' || in_array($event, $inflowEvents)) {
                    $revenue += $amt;
                } elseif ($subjectType === 'expense' || in_array($event, $outflowEvents)) {
                    $expenses += $amt;
                }
            }

            // 4. Data dari Gateway Xendit jika terhubung (hanya tambahkan transaksi yang belum tercatat di DB untuk mencegah duplikasi)
            if ($this->xenditService->isConfigured()) {
                $xenditTransactions = $this->xenditService->getRecentTransactions(50);
                $periodTrx = $xenditTransactions->filter(fn ($t) => $t->created_at->between($start, $end));

                // Filter transaksi yang belum tercatat di DB
                $unloggedTrx = $periodTrx->filter(function ($t) use ($loggedXenditIds) {
                    $xId = $t->xendit_id ?? null;
                    $refId = $t->reference_id ?? null;

                    if ($xId && isset($loggedXenditIds[$xId])) {
                        return false;
                    }
                    if ($refId && isset($loggedXenditIds[$refId])) {
                        return false;
                    }

                    return true;
                });

                $xenditInflow = (float) $unloggedTrx->where('is_income', true)->sum('amount');
                $xenditOutflow = (float) $unloggedTrx->where('is_income', false)->sum(fn ($t) => $t->amount + ($t->fee ?? 0));

                $revenue += $xenditInflow;
                $expenses += $xenditOutflow;
            }
        }

        $netProfit = $revenue - $expenses;
        $profitMargin = ($revenue > 0) ? round(($netProfit / $revenue) * 100, 2) : 0.0;

        return [
            'revenue' => (float) $revenue,
            'expenses' => (float) $expenses,
            'net_profit' => (float) $netProfit,
            'profit_margin' => (float) $profitMargin,
        ];
    }

    /**
     * Buat bucket rentang waktu untuk deret waktu chart
     */
    private function generateTimeBuckets(string $range, string $interval, CarbonInterface $now): array
    {
        $buckets = [];

        switch (strtolower($range)) {
            case '7d':
                for ($i = 6; $i >= 0; $i--) {
                    $d = $now->copy()->subDays($i);
                    $buckets[] = [
                        'label' => $d->translatedFormat('d M'),
                        'start' => $d->copy()->startOfDay(),
                        'end'   => $d->copy()->endOfDay(),
                    ];
                }
                break;

            case '30d':
                if ($interval === 'weekly') {
                    for ($w = 4; $w >= 0; $w--) {
                        $wStart = $now->copy()->subWeeks($w)->startOfWeek();
                        $wEnd = $wStart->copy()->endOfWeek();
                        $buckets[] = [
                            'label' => $wStart->translatedFormat('d M') . ' - ' . $wEnd->translatedFormat('d M'),
                            'start' => $wStart,
                            'end'   => $wEnd,
                        ];
                    }
                } else {
                    for ($i = 29; $i >= 0; $i -= 3) {
                        $dStart = $now->copy()->subDays($i)->startOfDay();
                        $dEnd = $dStart->copy()->addDays(2)->endOfDay();
                        $buckets[] = [
                            'label' => $dStart->translatedFormat('d M'),
                            'start' => $dStart,
                            'end'   => $dEnd,
                        ];
                    }
                }
                break;

            case '90d':
                if ($interval === 'monthly') {
                    for ($m = 2; $m >= 0; $m--) {
                        $mDate = $now->copy()->subMonths($m);
                        $buckets[] = [
                            'label' => $mDate->translatedFormat('M Y'),
                            'start' => $mDate->copy()->startOfMonth(),
                            'end'   => $mDate->copy()->endOfMonth(),
                        ];
                    }
                } else {
                    for ($w = 11; $w >= 0; $w--) {
                        $wStart = $now->copy()->subWeeks($w)->startOfWeek();
                        $wEnd = $wStart->copy()->endOfWeek();
                        $buckets[] = [
                            'label' => $wStart->translatedFormat('d M'),
                            'start' => $wStart,
                            'end'   => $wEnd,
                        ];
                    }
                }
                break;

            case 'ytd':
                $months = $now->month;
                $startYear = $now->copy()->startOfYear();
                for ($m = 1; $m <= $months; $m++) {
                    $mDate = $startYear->copy()->month($m);
                    $buckets[] = [
                        'label' => $mDate->translatedFormat('M'),
                        'start' => $mDate->copy()->startOfMonth(),
                        'end'   => $mDate->copy()->endOfMonth(),
                    ];
                }
                break;

            case '1y':
            default:
                for ($i = 11; $i >= 0; $i--) {
                    $mDate = $now->copy()->subMonths($i);
                    $buckets[] = [
                        'label' => $mDate->translatedFormat('M Y'),
                        'start' => $mDate->copy()->startOfMonth(),
                        'end'   => $mDate->copy()->endOfMonth(),
                    ];
                }
                break;
        }

        return $buckets;
    }
}
