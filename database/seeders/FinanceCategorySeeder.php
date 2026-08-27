<?php

namespace Database\Seeders;

use App\Models\FinanceCategory;
use Illuminate\Database\Seeder;

class FinanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['type' => 'income', 'name' => 'Pendapatan Operasional'],
            ['type' => 'income', 'name' => 'Top Up'],
            ['type' => 'income', 'name' => 'Lainnya'],
            ['type' => 'expense', 'name' => 'Operasional'],
            ['type' => 'expense', 'name' => 'Vendor'],
            ['type' => 'expense', 'name' => 'Biaya Admin'],
            ['type' => 'expense', 'name' => 'Lainnya'],
        ];

        foreach ($categories as $category) {
            FinanceCategory::firstOrCreate(
                ['type' => $category['type'], 'name' => $category['name']],
                ['is_active' => true]
            );
        }
    }
}
