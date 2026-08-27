<?php

namespace App\Services\Api;

use App\Enums\ApiCredentialStatus;
use App\Models\ApiClient;
use App\Models\ApiCredential;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApiCredentialService
{
    public function provision(ApiClient $client): string
    {
        $secret = bin2hex(random_bytes(32));

        ApiCredential::create([
            'api_client_id' => $client->id,
            'key_id' => 'kid_'.bin2hex(random_bytes(8)),
            'secret_hash' => hash('sha256', $secret),
            'secret_encrypted' => Crypt::encryptString($secret),
            'status' => ApiCredentialStatus::Active,
        ]);

        return $secret;
    }

    public function rotate(ApiClient $client, bool $immediate = false, int $graceHours = 24): string
    {
        return DB::transaction(function () use ($client, $immediate, $graceHours) {
            $activeCredentials = $client->activeCredentials();

            if ($immediate) {
                $activeCredentials->update([
                    'status' => ApiCredentialStatus::Revoked->value,
                    'revoked_at' => now(),
                ]);
            } else {
                $activeCredentials->update([
                    'expires_at' => now()->addHours($graceHours),
                ]);
            }

            return $this->provision($client);
        });
    }

    public function revoke(ApiCredential $credential): void
    {
        if ($credential->status === ApiCredentialStatus::Revoked) {
            throw new InvalidArgumentException('Kredential sudah dicabut.');
        }

        $credential->update([
            'status' => ApiCredentialStatus::Revoked->value,
            'revoked_at' => now(),
        ]);
    }
}
