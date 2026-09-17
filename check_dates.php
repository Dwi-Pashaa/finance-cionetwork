<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Activitylog\Models\Activity;
use App\Models\Income;
use App\Models\Expense;

echo "Activities count: " . Activity::count() . "\n";
foreach (Activity::orderBy('id', 'desc')->take(10)->get() as $a) {
    echo "Activity ID: {$a->id}, Event: {$a->event}, Amount: " . ($a->properties['amount'] ?? '-') . ", Created: {$a->created_at}, Description: {$a->description}\n";
}

echo "\n--- Incomes ---\n";
echo "Income count: " . Income::count() . "\n";
foreach (Income::orderBy('id', 'desc')->take(10)->get() as $i) {
    echo "Income ID: {$i->id}, Amount: {$i->amount}, TransDate: {$i->transaction_date}, Created: {$i->created_at}\n";
}

echo "\n--- Expenses ---\n";
echo "Expense count: " . Expense::count() . "\n";
foreach (Expense::orderBy('id', 'desc')->take(10)->get() as $e) {
    echo "Expense ID: {$e->id}, Amount: {$e->amount}, TransDate: {$e->transaction_date}, Created: {$e->created_at}\n";
}
