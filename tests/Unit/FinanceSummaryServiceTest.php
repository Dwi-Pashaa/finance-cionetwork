<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\FinanceCategory;
use App\Models\Income;
use App\Services\Finance\FinanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summary_includes_admin_fee_in_expense_totals(): void
    {
        $incomeCategory = FinanceCategory::create([
            'type' => 'income',
            'name' => 'Top Up',
        ]);

        $expenseCategory = FinanceCategory::create([
            'type' => 'expense',
            'name' => 'Vendor',
        ]);

        Income::create([
            'finance_category_id' => $incomeCategory->id,
            'transaction_date' => '2026-08-10',
            'amount' => '100000.00',
        ]);

        Expense::create([
            'finance_category_id' => $expenseCategory->id,
            'transaction_date' => '2026-08-11',
            'amount' => '30000.00',
            'has_admin_fee' => true,
            'admin_fee_amount' => '2500.00',
        ]);

        $summary = app(FinanceSummaryService::class)->dashboard(now()->setDate(2026, 8, 26));

        $this->assertSame(100000.0, $summary['monthly_income']);
        $this->assertSame(32500.0, $summary['monthly_expense']);
        $this->assertSame(67500.0, $summary['monthly_net']);
        $this->assertSame(67500.0, $summary['net_balance']);
    }
}
