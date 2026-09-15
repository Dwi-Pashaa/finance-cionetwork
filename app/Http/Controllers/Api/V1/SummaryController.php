<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Income;
use App\Services\Finance\FinanceSummaryService;
use App\Services\Support\ApiResponse;
use App\Services\Xendit\XenditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class SummaryController extends Controller
{
    public function __construct(
        private FinanceSummaryService $summaryService,
        private XenditService $xenditService
    ) {}

    public function index(Request $request)
    {
        $startDate = $request->query('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : now()->startOfMonth();
        $endDate = $request->query('date_to') ? Carbon::parse($request->query('date_to'))->endOfDay() : now()->endOfDay();

        // 1. Pemasukan Kas Internal
        $manualIncome = (float) Income::whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])->sum('amount');

        // 2. Pengeluaran Kas Internal
        $generalFinanceExpense = (float) Expense::whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum(DB::raw('amount + COALESCE(admin_fee_amount, 0)'));

        // 3. Log External Activity dari Semua Web (Operasional, Slip Gaji, Investor, dll)
        $operationalExpense = 0.0;
        $payrollExpense = 0.0;
        $investorExpense = 0.0;
        $xenditOutflowAndFees = 0.0;
        $xenditInflow = 0.0;

        $externalLogs = Activity::where('log_name', 'external_finance')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        foreach ($externalLogs as $log) {
            $props = $log->properties ?? [];
            $amount = (float) ($props['amount'] ?? 0);
            $clientCode = strtolower($props['client_code'] ?? ($props['client_id'] ?? ''));
            $channel = strtolower($props['channel'] ?? ($props['balance_type'] ?? 'manual'));
            $event = strtolower($log->event ?? ($props['type'] ?? ''));

            if ($event === 'income' || ($props['type'] ?? '') === 'income' || $event === 'refund_balance') {
                if ($channel === 'xendit') {
                    $xenditInflow += $amount;
                } else {
                    $manualIncome += $amount;
                }
            } else {
                if (str_contains($clientCode, 'operasional')) {
                    $operationalExpense += $amount;
                } elseif (str_contains($clientCode, 'slip') || str_contains($clientCode, 'keuangan') || str_contains($clientCode, 'gaji')) {
                    $payrollExpense += $amount;
                } elseif (str_contains($clientCode, 'investor')) {
                    $investorExpense += $amount;
                } else {
                    if ($channel === 'xendit') {
                        $xenditOutflowAndFees += $amount;
                    } else {
                        $generalFinanceExpense += $amount;
                    }
                }
            }
        }

        // 4. Data Xendit
        $xenditBalances = [
            'total' => 0.0,
            'cash' => 0.0,
            'holding' => 0.0,
            'tax' => 0.0,
            'status' => 'offline',
        ];

        if ($this->xenditService->isConfigured()) {
            $allBalances = $this->xenditService->getAllBalances();
            if ($allBalances) {
                $xenditBalances = [
                    'total' => (float) ($allBalances['total'] ?? 0),
                    'cash' => (float) ($allBalances['cash'] ?? 0),
                    'holding' => (float) ($allBalances['holding'] ?? 0),
                    'tax' => (float) ($allBalances['tax'] ?? 0),
                    'status' => 'connected',
                ];
            }

            $monthlyXendit = $this->xenditService->getMonthlySummary();
            if ($monthlyXendit) {
                if ($xenditInflow == 0) {
                    $xenditInflow = (float) ($monthlyXendit['total_inflow'] ?? 0);
                }
                if ($xenditOutflowAndFees == 0) {
                    $xenditOutflowAndFees = (float) (($monthlyXendit['total_outflow'] ?? 0) + ($monthlyXendit['total_fee'] ?? 0));
                }
            }
        }

        $totalIncome = $manualIncome + $xenditInflow;
        $totalExpense = $operationalExpense + $payrollExpense + $investorExpense + $generalFinanceExpense + $xenditOutflowAndFees;
        $netProfit = $totalIncome - $totalExpense;
        $margin = ($totalIncome > 0) ? round(($netProfit / $totalIncome) * 100, 2) : 0;

        return ApiResponse::success('Financial summary retrieved successfully', [
            'total_income' => $totalIncome,
            'manual_income' => $manualIncome,
            'xendit_inflow' => $xenditInflow,
            'total_expense' => $totalExpense,
            'operational_expense' => $operationalExpense,
            'payroll_expense' => $payrollExpense,
            'investor_expense' => $investorExpense,
            'general_finance_expense' => $generalFinanceExpense,
            'xendit_outflow_and_fees' => $xenditOutflowAndFees,
            'net_profit' => $netProfit,
            'profit_margin_percentage' => $margin,
            'xendit_balances' => $xenditBalances,
            'period' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
        ]);
    }
}