<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\Income;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class FinanceSummaryService
{
    public function dashboard(?CarbonInterface $date = null, bool $useCache = true): array
    {
        $date ??= now();
        $cacheKey = 'finance_dashboard_summary_' . $date->format('Y-m-d');

        if ($useCache) {
            return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($date) {
                return $this->computeDashboard($date);
            });
        }

        return $this->computeDashboard($date);
    }

    public function clearCache(): void
    {
        Cache::forget('finance_dashboard_summary_' . now()->format('Y-m-d'));
    }

    private function computeDashboard(CarbonInterface $date): array
    {
        $startOfMonth = $date->copy()->startOfMonth()->toDateString();
        $endOfMonth = $date->copy()->endOfMonth()->toDateString();

        $monthlyIncome = $this->incomeTotal($startOfMonth, $endOfMonth);
        $monthlyExpense = $this->expenseTotal($startOfMonth, $endOfMonth);
        $totalIncome = $this->incomeTotal();
        $totalExpense = $this->expenseTotal();

        // Multi-Period Chart Trends (Trading Style: 1HR, 7HR, 1BLN, 6BLN, YTD, 1TH, 5TH, Maks)
        $chartTrends = [
            '1hr'  => ['labels' => [], 'incomes' => [], 'expenses' => [], 'profits' => []],
            '7hr'  => ['labels' => [], 'incomes' => [], 'expenses' => [], 'profits' => []],
            '1bln' => ['labels' => [], 'incomes' => [], 'expenses' => [], 'profits' => []],
            '6bln' => ['labels' => [], 'incomes' => [], 'expenses' => [], 'profits' => []],
            'ytd'  => ['labels' => [], 'incomes' => [], 'expenses' => [], 'profits' => []],
            '1th'  => ['labels' => [], 'incomes' => [], 'expenses' => [], 'profits' => []],
            '5th'  => ['labels' => [], 'incomes' => [], 'expenses' => [], 'profits' => []],
            'maks' => ['labels' => [], 'incomes' => [], 'expenses' => [], 'profits' => []],
        ];

        // 1. 1HR (Breakdown Per Jam 00:00 - 23:00)
        $todayStr = $date->toDateString();
        $incomesByHour = Income::query()
            ->whereDate('transaction_date', $todayStr)
            ->selectRaw('HOUR(created_at) as hr, SUM(amount) as total')
            ->groupBy('hr')
            ->pluck('total', 'hr')
            ->toArray();

        $expensesByHour = Expense::query()
            ->whereDate('transaction_date', $todayStr)
            ->selectRaw('HOUR(created_at) as hr, SUM(amount + COALESCE(admin_fee_amount, 0)) as total')
            ->groupBy('hr')
            ->pluck('total', 'hr')
            ->toArray();

        for ($h = 0; $h < 24; $h++) {
            $hLabel = sprintf('%02d:00', $h);
            $inc = (float) ($incomesByHour[$h] ?? 0);
            $exp = (float) ($expensesByHour[$h] ?? 0);

            $chartTrends['1hr']['labels'][] = $hLabel;
            $chartTrends['1hr']['incomes'][] = $inc;
            $chartTrends['1hr']['expenses'][] = $exp;
            $chartTrends['1hr']['profits'][] = $inc - $exp;
        }

        // 2. 7HR (Batch query 7 Hari Terakhir)
        $start7Days = $date->copy()->subDays(6)->toDateString();
        $incomes7Days = Income::query()
            ->whereBetween('transaction_date', [$start7Days, $todayStr])
            ->selectRaw('DATE(transaction_date) as dt, SUM(amount) as total')
            ->groupBy('dt')
            ->pluck('total', 'dt')
            ->toArray();

        $expenses7Days = Expense::query()
            ->whereBetween('transaction_date', [$start7Days, $todayStr])
            ->selectRaw('DATE(transaction_date) as dt, SUM(amount + COALESCE(admin_fee_amount, 0)) as total')
            ->groupBy('dt')
            ->pluck('total', 'dt')
            ->toArray();

        for ($i = 6; $i >= 0; $i--) {
            $dDate = $date->copy()->subDays($i);
            $dStr = $dDate->toDateString();
            $inc = (float) ($incomes7Days[$dStr] ?? 0);
            $exp = (float) ($expenses7Days[$dStr] ?? 0);

            $chartTrends['7hr']['labels'][] = $dDate->translatedFormat('d M');
            $chartTrends['7hr']['incomes'][] = $inc;
            $chartTrends['7hr']['expenses'][] = $exp;
            $chartTrends['7hr']['profits'][] = $inc - $exp;
        }

        // 3. 1BLN (30 Hari Terakhir - Batch query)
        $start30Days = $date->copy()->subDays(29)->toDateString();
        $incomes30Days = Income::query()
            ->whereBetween('transaction_date', [$start30Days, $todayStr])
            ->selectRaw('DATE(transaction_date) as dt, SUM(amount) as total')
            ->groupBy('dt')
            ->pluck('total', 'dt')
            ->toArray();

        $expenses30Days = Expense::query()
            ->whereBetween('transaction_date', [$start30Days, $todayStr])
            ->selectRaw('DATE(transaction_date) as dt, SUM(amount + COALESCE(admin_fee_amount, 0)) as total')
            ->groupBy('dt')
            ->pluck('total', 'dt')
            ->toArray();

        for ($i = 29; $i >= 0; $i -= 3) {
            $dStart = $date->copy()->subDays($i);
            $inc = 0.0;
            $exp = 0.0;
            for ($k = 0; $k < 3; $k++) {
                $subDateStr = $dStart->copy()->addDays($k)->toDateString();
                $inc += (float) ($incomes30Days[$subDateStr] ?? 0);
                $exp += (float) ($expenses30Days[$subDateStr] ?? 0);
            }

            $chartTrends['1bln']['labels'][] = $dStart->translatedFormat('d M');
            $chartTrends['1bln']['incomes'][] = $inc;
            $chartTrends['1bln']['expenses'][] = $exp;
            $chartTrends['1bln']['profits'][] = $inc - $exp;
        }

        // 4. 12 Bulan Terakhir (Digunakan untuk 6BLN, YTD, dan 1TH - Single Batch Query)
        $start12Months = $date->copy()->subMonths(11)->startOfMonth()->toDateString();
        $incomes12Months = Income::query()
            ->whereBetween('transaction_date', [$start12Months, $endOfMonth])
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->toArray();

        $expenses12Months = Expense::query()
            ->whereBetween('transaction_date', [$start12Months, $endOfMonth])
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as ym, SUM(amount + COALESCE(admin_fee_amount, 0)) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->toArray();

        // 4. 6BLN (6 Bulan Terakhir)
        for ($i = 5; $i >= 0; $i--) {
            $mDate = $date->copy()->subMonths($i);
            $mKey = $mDate->format('Y-m');
            $inc = (float) ($incomes12Months[$mKey] ?? 0);
            $exp = (float) ($expenses12Months[$mKey] ?? 0);

            $chartTrends['6bln']['labels'][] = $mDate->translatedFormat('M Y');
            $chartTrends['6bln']['incomes'][] = $inc;
            $chartTrends['6bln']['expenses'][] = $exp;
            $chartTrends['6bln']['profits'][] = $inc - $exp;
        }

        // 5. YTD (Jan s/d Bulan Ini)
        $startOfYear = $date->copy()->startOfYear();
        $monthsPassed = $date->month;
        for ($m = 1; $m <= $monthsPassed; $m++) {
            $mDate = $startOfYear->copy()->month($m);
            $mKey = $mDate->format('Y-m');
            $inc = (float) ($incomes12Months[$mKey] ?? 0);
            $exp = (float) ($expenses12Months[$mKey] ?? 0);

            $chartTrends['ytd']['labels'][] = $mDate->translatedFormat('M');
            $chartTrends['ytd']['incomes'][] = $inc;
            $chartTrends['ytd']['expenses'][] = $exp;
            $chartTrends['ytd']['profits'][] = $inc - $exp;
        }

        // 6. 1TH (12 Bulan Terakhir)
        for ($i = 11; $i >= 0; $i--) {
            $mDate = $date->copy()->subMonths($i);
            $mKey = $mDate->format('Y-m');
            $inc = (float) ($incomes12Months[$mKey] ?? 0);
            $exp = (float) ($expenses12Months[$mKey] ?? 0);

            $chartTrends['1th']['labels'][] = $mDate->translatedFormat('M Y');
            $chartTrends['1th']['incomes'][] = $inc;
            $chartTrends['1th']['expenses'][] = $exp;
            $chartTrends['1th']['profits'][] = $inc - $exp;
        }

        // 7. 5TH (5 Tahun Terakhir - Batch Query)
        $start5Years = $date->copy()->subYears(4)->startOfYear()->toDateString();
        $end5Years = $date->copy()->endOfYear()->toDateString();
        $incomes5Years = Income::query()
            ->whereBetween('transaction_date', [$start5Years, $end5Years])
            ->selectRaw('YEAR(transaction_date) as yr, SUM(amount) as total')
            ->groupBy('yr')
            ->pluck('total', 'yr')
            ->toArray();

        $expenses5Years = Expense::query()
            ->whereBetween('transaction_date', [$start5Years, $end5Years])
            ->selectRaw('YEAR(transaction_date) as yr, SUM(amount + COALESCE(admin_fee_amount, 0)) as total')
            ->groupBy('yr')
            ->pluck('total', 'yr')
            ->toArray();

        for ($i = 4; $i >= 0; $i--) {
            $yDate = $date->copy()->subYears($i);
            $yKey = (int) $yDate->format('Y');
            $inc = (float) ($incomes5Years[$yKey] ?? 0);
            $exp = (float) ($expenses5Years[$yKey] ?? 0);

            $chartTrends['5th']['labels'][] = (string) $yKey;
            $chartTrends['5th']['incomes'][] = $inc;
            $chartTrends['5th']['expenses'][] = $exp;
            $chartTrends['5th']['profits'][] = $inc - $exp;
        }

        // 8. Maks
        $chartTrends['maks'] = $chartTrends['5th'];

        // Backward compatibility aliases
        $chartTrends['day'] = $chartTrends['1hr'];
        $chartTrends['week'] = $chartTrends['7hr'];
        $chartTrends['month'] = $chartTrends['6bln'];
        $chartTrends['year'] = $chartTrends['1th'];

        // Category breakdown for current month (Expense)
        $expenseCategories = Expense::query()
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->join('finance_categories', 'expenses.finance_category_id', '=', 'finance_categories.id')
            ->selectRaw('finance_categories.name as category_name, SUM(expenses.amount + COALESCE(expenses.admin_fee_amount, 0)) as total')
            ->groupBy('finance_categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $categoryBreakdown = [
            'labels' => $expenseCategories->pluck('category_name')->toArray(),
            'totals' => $expenseCategories->pluck('total')->map(fn($v) => (float)$v)->toArray(),
        ];

        // If no expenses this month, get top overall expense categories
        if (empty($categoryBreakdown['labels'])) {
            $topExpenses = Expense::query()
                ->join('finance_categories', 'expenses.finance_category_id', '=', 'finance_categories.id')
                ->selectRaw('finance_categories.name as category_name, SUM(expenses.amount + COALESCE(expenses.admin_fee_amount, 0)) as total')
                ->groupBy('finance_categories.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
            $categoryBreakdown = [
                'labels' => $topExpenses->pluck('category_name')->toArray(),
                'totals' => $topExpenses->pluck('total')->map(fn($v) => (float)$v)->toArray(),
            ];
        }

        $expenseRatio = $monthlyIncome > 0 ? round(($monthlyExpense / $monthlyIncome) * 100, 1) : 0;

        return [
            'monthly_income' => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'monthly_net' => $monthlyIncome - $monthlyExpense,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_balance' => $totalIncome - $totalExpense,
            'expense_ratio' => $expenseRatio,
            'chart_trends' => $chartTrends,
            'monthly_trend' => $chartTrends['month'],
            'category_breakdown' => $categoryBreakdown,
            'latest_incomes' => Income::with('category')->latest('transaction_date')->latest('id')->limit(5)->get(),
            'latest_expenses' => Expense::with('category')->latest('transaction_date')->latest('id')->limit(5)->get(),
        ];
    }

    private function incomeTotal(?string $dateFrom = null, ?string $dateTo = null): float
    {
        return (float) Income::query()
            ->when($dateFrom, fn($query) => $query->whereDate('transaction_date', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('transaction_date', '<=', $dateTo))
            ->sum('amount');
    }

    private function expenseTotal(?string $dateFrom = null, ?string $dateTo = null): float
    {
        $query = Expense::query()
            ->when($dateFrom, fn($query) => $query->whereDate('transaction_date', '>=', $dateFrom))
            ->when($dateTo, fn($query) => $query->whereDate('transaction_date', '<=', $dateTo));

        return (float) $query->sum('amount') + (float) $query->sum('admin_fee_amount');
    }
}

