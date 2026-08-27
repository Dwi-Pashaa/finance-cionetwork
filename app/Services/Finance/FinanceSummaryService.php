<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\Income;
use Carbon\CarbonInterface;

class FinanceSummaryService
{
    public function dashboard(?CarbonInterface $date = null): array
    {
        $date ??= now();
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

        // 1. 1HR (1 Hari / Breakdown Per Jam 00:00 - 23:00)
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

        // 2. 7HR (7 Hari Terakhir)
        for ($i = 6; $i >= 0; $i--) {
            $dDate = $date->copy()->subDays($i);
            $dStr = $dDate->toDateString();
            $inc = $this->incomeTotal($dStr, $dStr);
            $exp = $this->expenseTotal($dStr, $dStr);

            $chartTrends['7hr']['labels'][] = $dDate->translatedFormat('d M');
            $chartTrends['7hr']['incomes'][] = $inc;
            $chartTrends['7hr']['expenses'][] = $exp;
            $chartTrends['7hr']['profits'][] = $inc - $exp;
        }

        // 3. 1BLN (1 Bulan Terakhir - 30 Hari)
        for ($i = 29; $i >= 0; $i -= 3) {
            $dStart = $date->copy()->subDays($i);
            $dEnd = $dStart->copy()->addDays(2);
            $inc = $this->incomeTotal($dStart->toDateString(), $dEnd->toDateString());
            $exp = $this->expenseTotal($dStart->toDateString(), $dEnd->toDateString());

            $chartTrends['1bln']['labels'][] = $dStart->translatedFormat('d M');
            $chartTrends['1bln']['incomes'][] = $inc;
            $chartTrends['1bln']['expenses'][] = $exp;
            $chartTrends['1bln']['profits'][] = $inc - $exp;
        }

        // 4. 6BLN (6 Bulan Terakhir)
        for ($i = 5; $i >= 0; $i--) {
            $mDate = $date->copy()->subMonths($i);
            $mStart = $mDate->copy()->startOfMonth()->toDateString();
            $mEnd = $mDate->copy()->endOfMonth()->toDateString();
            $inc = $this->incomeTotal($mStart, $mEnd);
            $exp = $this->expenseTotal($mStart, $mEnd);

            $chartTrends['6bln']['labels'][] = $mDate->translatedFormat('M Y');
            $chartTrends['6bln']['incomes'][] = $inc;
            $chartTrends['6bln']['expenses'][] = $exp;
            $chartTrends['6bln']['profits'][] = $inc - $exp;
        }

        // 5. YTD (Year To Date - Jan s/d Bulan Ini)
        $startOfYear = $date->copy()->startOfYear();
        $monthsPassed = $date->month;
        for ($m = 1; $m <= $monthsPassed; $m++) {
            $mDate = $startOfYear->copy()->month($m);
            $mStart = $mDate->copy()->startOfMonth()->toDateString();
            $mEnd = $mDate->copy()->endOfMonth()->toDateString();
            $inc = $this->incomeTotal($mStart, $mEnd);
            $exp = $this->expenseTotal($mStart, $mEnd);

            $chartTrends['ytd']['labels'][] = $mDate->translatedFormat('M');
            $chartTrends['ytd']['incomes'][] = $inc;
            $chartTrends['ytd']['expenses'][] = $exp;
            $chartTrends['ytd']['profits'][] = $inc - $exp;
        }

        // 6. 1TH (1 Tahun / 12 Bulan Terakhir)
        for ($i = 11; $i >= 0; $i--) {
            $mDate = $date->copy()->subMonths($i);
            $mStart = $mDate->copy()->startOfMonth()->toDateString();
            $mEnd = $mDate->copy()->endOfMonth()->toDateString();
            $inc = $this->incomeTotal($mStart, $mEnd);
            $exp = $this->expenseTotal($mStart, $mEnd);

            $chartTrends['1th']['labels'][] = $mDate->translatedFormat('M Y');
            $chartTrends['1th']['incomes'][] = $inc;
            $chartTrends['1th']['expenses'][] = $exp;
            $chartTrends['1th']['profits'][] = $inc - $exp;
        }

        // 7. 5TH (5 Tahun Terakhir)
        for ($i = 4; $i >= 0; $i--) {
            $yDate = $date->copy()->subYears($i);
            $yStart = $yDate->copy()->startOfYear()->toDateString();
            $yEnd = $yDate->copy()->endOfYear()->toDateString();
            $inc = $this->incomeTotal($yStart, $yEnd);
            $exp = $this->expenseTotal($yStart, $yEnd);

            $chartTrends['5th']['labels'][] = $yDate->translatedFormat('Y');
            $chartTrends['5th']['incomes'][] = $inc;
            $chartTrends['5th']['expenses'][] = $exp;
            $chartTrends['5th']['profits'][] = $inc - $exp;
        }

        // 8. Maks (Maksimal / Semua Tahun)
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
