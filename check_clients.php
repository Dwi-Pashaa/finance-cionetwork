<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApiClient;
use App\Models\ApiCredential;

echo "=== API CLIENTS ===\n";
foreach (ApiClient::all() as $c) {
    echo "ID: {$c->id}, Code: {$c->code}, Name: {$c->name}, ClientID: {$c->client_id}, Status: {$c->status}\n";
}

echo "=== API CREDENTIALS ===\n";
foreach (ApiCredential::all() as $cr) {
    echo "ID: {$cr->id}, ClientID_FK: {$cr->api_client_id}, KeyID: {$cr->key_id}, Secret: {$cr->secret_key}, Status: {$cr->status}\n";
}
