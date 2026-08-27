<?php

namespace App\Services\Finance;

use App\Models\Income;

class IncomeService
{
    public function create(array $data, ?int $userId): Income
    {
        return Income::create([
            'finance_category_id' => $data['finance_category_id'],
            'transaction_date' => $data['transaction_date'],
            'amount' => $data['amount'],
            'source' => $data['source'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function update(Income $income, array $data, ?int $userId): void
    {
        $income->update([
            'finance_category_id' => $data['finance_category_id'],
            'transaction_date' => $data['transaction_date'],
            'amount' => $data['amount'],
            'source' => $data['source'] ?? null,
            'description' => $data['description'] ?? null,
            'updated_by' => $userId,
        ]);
    }

    public function delete(Income $income): void
    {
        $income->delete();
    }
}
