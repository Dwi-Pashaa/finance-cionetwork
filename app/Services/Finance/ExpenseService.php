<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\Income;
use InvalidArgumentException;

class ExpenseService
{
    public function create(array $data, ?int $userId): Expense
    {
        $this->ensureSufficientIncome($data);

        return Expense::create($this->payload($data, $userId, true));
    }

    public function update(Expense $expense, array $data, ?int $userId): void
    {
        $this->ensureSufficientIncome($data, $expense);

        $expense->update($this->payload($data, $userId, false));
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }

    private function payload(array $data, ?int $userId, bool $isCreate): array
    {
        $hasAdminFee = filter_var($data['has_admin_fee'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $payload = [
            'finance_category_id' => $data['finance_category_id'],
            'transaction_date' => $data['transaction_date'],
            'amount' => $data['amount'],
            'has_admin_fee' => $hasAdminFee,
            'admin_fee_amount' => $hasAdminFee ? ($data['admin_fee_amount'] ?? 0) : 0,
            'payee' => $data['payee'] ?? null,
            'description' => $data['description'] ?? null,
            'updated_by' => $userId,
        ];

        if ($isCreate) {
            $payload['created_by'] = $userId;
        }

        return $payload;
    }

    private function ensureSufficientIncome(array $data, ?Expense $currentExpense = null): void
    {
        $hasAdminFee = filter_var($data['has_admin_fee'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $requestedExpense = (float) $data['amount'] + ($hasAdminFee ? (float) ($data['admin_fee_amount'] ?? 0) : 0);
        $totalIncome = (float) Income::query()->sum('amount');

        $expenseQuery = Expense::query();

        if ($currentExpense) {
            $expenseQuery->whereKeyNot($currentExpense->id);
        }

        $usedExpense = (float) $expenseQuery->sum('amount') + (float) $expenseQuery->sum('admin_fee_amount');
        $availableIncome = $totalIncome - $usedExpense;

        if ($requestedExpense > $availableIncome) {
            throw new InvalidArgumentException(
                'Pengeluaran melebihi saldo pemasukan tersedia. Saldo tersedia: Rp '.number_format($availableIncome, 0, ',', '.')
            );
        }
    }
}
