<?php

namespace Tests\Feature\Api;

use App\Models\ApiClient;
use App\Models\ApiClientBalance;
use App\Services\Api\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_own_balance(): void
    {
        [$client2, $secret2] = $this->register('WEB2', '15000000.00');
        $this->register('WEB3', '99000.00');

        $response = $this->getJson('/api/v1/balance', $this->headers($client2, $secret2));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.client_code', 'WEB2')
            ->assertJsonPath('data.balance', '15000000.00');

        $this->assertStringNotContainsString('99000', $response->getContent());
    }

    public function test_balance_requires_auth(): void
    {
        $this->getJson('/api/v1/balance')->assertStatus(401);
    }

    private function register(string $code, string $balance): array
    {
        $result = app(ApiClientService::class)->register([
            'name' => "Website {$code}",
            'code' => $code,
        ]);

        ApiClientBalance::where('api_client_id', $result['client']->id)
            ->update(['balance' => $balance]);

        return [$result['client'], $result['secret']];
    }

    private function headers(ApiClient $client, string $secret): array
    {
        $keyId = $client->activeCredentials()->first()->key_id;
        $timestamp = (string) now()->getTimestamp();
        $nonce = uniqid('nonce_', true);

        $canonical = implode("\n", [
            'GET', '/api/v1/balance', $client->client_id, $keyId, $timestamp, $nonce, hash('sha256', '[]'),
        ]);

        return [
            'X-Client-ID' => $client->client_id,
            'X-Key-ID' => $keyId,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => base64_encode(hash_hmac('sha256', $canonical, $secret, true)),
        ];
    }
}
