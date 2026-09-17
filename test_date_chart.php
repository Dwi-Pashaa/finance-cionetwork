<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\Finance\AnalyticsService::class);
$res = $service->getChart('1d', 'hourly', 'ALL', false, '2026-08-30');
echo "Date 2026-08-30:\n";
echo json_encode($res, JSON_PRETTY_PRINT) . "\n";

$res31 = $service->getChart('1d', 'hourly', 'ALL', false, '2026-08-31');
echo "\nDate 2026-08-31:\n";
echo json_encode($res31, JSON_PRETTY_PRINT) . "\n";
