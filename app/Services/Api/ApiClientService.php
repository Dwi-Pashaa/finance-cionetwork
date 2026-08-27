<?php

namespace App\Services\Api;

use App\Enums\ApiClientStatus;
use App\Enums\ApiCredentialStatus;
use App\Models\ApiClient;
use App\Models\ApiClientBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ApiClientService
{
    public function __construct(private ApiCredentialService $credentialService) {}

    public function register(array $data): array
    {
        if (ApiClient::where('code', $data['code'])->exists()) {
            throw new InvalidArgumentException('Kode client sudah digunakan.');
        }

        $result = DB::transaction(function () use ($data) {
            $client = ApiClient::create([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'client_id' => strtolower($data['code']).'_'.bin2hex(random_bytes(16)),
                'status' => ApiClientStatus::Active,
                'description' => $data['description'] ?? null,
                'rate_limit_per_minute' => (int) ($data['rate_limit_per_minute'] ?? 60),
            ]);

            ApiClientBalance::create([
                'api_client_id' => $client->id,
                'balance' => 0,
            ]);

            $secret = $this->credentialService->provision($client);

            return [$client, $secret];
        });

        return [
            'client' => $result[0],
            'secret' => $result[1],
        ];
    }

    public function update(ApiClient $client, array $data): void
    {
        $client->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'rate_limit_per_minute' => (int) ($data['rate_limit_per_minute'] ?? 60),
        ]);
    }

    public function setStatus(ApiClient $client, ApiClientStatus $status): void
    {
        DB::transaction(function () use ($client, $status) {
            if ($status === ApiClientStatus::Revoked) {
                $client->credentials()
                    ->where('status', ApiCredentialStatus::Active->value)
                    ->update([
                        'status' => ApiCredentialStatus::Revoked->value,
                        'revoked_at' => now(),
                    ]);
            }

            $client->update([
                'status' => $status,
                'revoked_at' => $status === ApiClientStatus::Revoked ? now() : null,
            ]);
        });
    }

    public function generateRequestId(): string
    {
        return 'req_'.Str::lower(Str::random(24));
    }
}
