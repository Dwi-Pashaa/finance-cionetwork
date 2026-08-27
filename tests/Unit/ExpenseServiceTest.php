<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\FinanceCategory;
use App\Models\Income;
use App\Services\Finance\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ExpenseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_expense_rejects_amount_above_available_income(): void
    {
        [$incomeCategory, $expenseCategory] = $this->categories();

        Income::create([
            'finance_category_id' => $incomeCategory->id,
            'transaction_date' => '2026-08-26',
            'amount' => '100000.00',
        ]);

        Expense::create([
            'finance_category_id' => $expenseCategory->id,
            'transaction_date' => '2026-08-26',
            'amount' => '80000.00',
            'has_admin_fee' => false,
            'admin_fee_amount' => '0.00',
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ExpenseService::class)->create([
            'finance_category_id' => $expenseCategory->id,
            'transaction_date' => '2026-08-26',
            'amount' => '25000',
            'has_admin_fee' => '0',
        ], null);
    }

    public function test_create_expense_counts_admin_fee_against_available_income(): void
    {
        [$incomeCategory, $expenseCategory] = $this->categories();

        Income::create([
            'finance_category_id' => $incomeCategory->id,
            'transaction_date' => '2026-08-26',
            'amount' => '100000.00',
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ExpenseService::class)->create([
            'finance_category_id' => $expenseCategory->id,
            'transaction_date' => '2026-08-26',
            'amount' => '99000',
            'has_admin_fee' => '1',
            'admin_fee_amount' => '2000',
        ], null);
    }

    public function test_update_expense_allows_current_expense_balance_to_be_reused(): void
    {
        [$incomeCategory, $expenseCategory] = $this->categories();

        Income::create([
            'finance_category_id' => $incomeCategory->id,
            'transaction_date' => '2026-08-26',
            'amount' => '100000.00',
        ]);

        $expense = Expense::create([
            'finance_category_id' => $expenseCategory->id,
            'transaction_date' => '2026-08-26',
            'amount' => '90000.00',
            'has_admin_fee' => false,
            'admin_fee_amount' => '0.00',
        ]);

        app(ExpenseService::class)->update($expense, [
            'finance_category_id' => $expenseCategory->id,
            'transaction_date' => '2026-08-26',
            'amount' => '95000',
            'has_admin_fee' => '0',
        ], null);

        $this->assertSame('95000.00', $expense->refresh()->amount);
    }

    private function categories(): array
    {
        return [
            FinanceCategory::create([
                'type' => 'income',
                'name' => 'Top Up',
            ]),
            FinanceCategory::create([
                'type' => 'expense',
                'name' => 'Vendor',
            ]),
        ];
    }
}
