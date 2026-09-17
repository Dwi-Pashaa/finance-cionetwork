<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Activitylog\Models\Activity;

$dates = Activity::selectRaw('DATE(created_at) as log_date, count(*) as total, sum(CAST(JSON_UNQUOTE(JSON_EXTRACT(properties, "$.amount")) AS DECIMAL(15,2))) as total_amount')
    ->groupBy('log_date')
    ->orderBy('log_date', 'desc')
    ->get();

foreach ($dates as $d) {
    echo "Date: {$d->log_date}, Records: {$d->total}, Amount: {$d->total_amount}\n";
}
