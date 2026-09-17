<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = app(\App\Services\Finance\AnalyticsService::class);
$res = $s->getChart('1d', 'hourly', null, false);
echo json_encode($res, JSON_PRETTY_PRINT);
