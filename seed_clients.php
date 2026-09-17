<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApiClient;
use App\Models\ApiCredential;
use App\Models\ApiClientBalance;
use App\Enums\ApiClientStatus;
use App\Enums\ApiCredentialStatus;
use Illuminate\Support\Facades\Crypt;

$clientId = 'test_web_saham_18dcf3aab5a0d552f5670a3978c7cd22';
$keyId = 'kid_4e0479ba4b715ac5';
$secret = 'b60777bc6d6569ad65f875e81f824cda74b3c1cb05a188081ef974ee6c943ed7';

$client = ApiClient::firstOrCreate(
    ['client_id' => $clientId],
    [
        'name' => 'CIO Investor Portal',
        'code' => 'CIO_INVESTOR',
        'status' => ApiClientStatus::Active,
        'is_manual_balance_enabled' => true,
        'is_xendit_balance_enabled' => true,
        'description' => 'Aplikasi Investor Saham CIO',
        'rate_limit_per_minute' => 120,
    ]
);

// Also create or update balance record
ApiClientBalance::firstOrCreate(
    ['api_client_id' => $client->id],
    [
        'balance_manual' => 0,
        'balance_xendit' => 0,
    ]
);

$credential = ApiCredential::firstOrCreate(
    [
        'api_client_id' => $client->id,
        'key_id' => $keyId,
    ],
    [
        'secret_hash' => hash('sha256', $secret),
        'secret_encrypted' => Crypt::encryptString($secret),
        'status' => ApiCredentialStatus::Active,
    ]
);

// Also ensure WEB_SLIP_GAJI and CIO_OPERASIONAL exist as clients
$otherClients = [
    [
        'code' => 'WEB_SLIP_GAJI',
        'name' => 'Web Slip Gaji',
        'client_id' => 'client_slip_gaji_' . substr(md5('slip_gaji'), 0, 16),
    ],
    [
        'code' => 'CIO_OPERASIONAL',
        'name' => 'CIO Operasional',
        'client_id' => 'client_operasional_' . substr(md5('operasional'), 0, 16),
    ],
    [
        'code' => 'CIO_FINANCE',
        'name' => 'CIO Finance',
        'client_id' => 'client_finance_' . substr(md5('finance'), 0, 16),
    ],
];

foreach ($otherClients as $oc) {
    $c = ApiClient::firstOrCreate(
        ['code' => $oc['code']],
        [
            'name' => $oc['name'],
            'client_id' => $oc['client_id'],
            'status' => ApiClientStatus::Active,
            'is_manual_balance_enabled' => true,
            'is_xendit_balance_enabled' => true,
        ]
    );
    ApiClientBalance::firstOrCreate(
        ['api_client_id' => $c->id],
        ['balance_manual' => 0, 'balance_xendit' => 0]
    );
}

echo "Seeded clients and credentials successfully!\n";
