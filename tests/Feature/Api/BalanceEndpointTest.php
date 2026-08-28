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

    public function test_can_deduct_balance_successfully(): void
    {
        [$client, $secret] = $this->register('WEB_PAY', '1000000.00');

        $payload = [
            'amount' => 50000,
            'reference_id' => 'KB-12',
            'description' => 'Pembayaran kasbon karyawan Toni',
            'category' => 'kasbon',
            'note' => 'Potong saldo kasbon karyawan',
        ];

        $response = $this->postJson(
            '/api/v1/balance/deduct',
            $payload,
            $this->headers($client, $secret, 'POST', '/api/v1/balance/deduct', $payload)
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Saldo berhasil dipotong')
            ->assertJsonPath('data.client_code', 'WEB_PAY')
            ->assertJsonPath('data.amount_deducted', 50000)
            ->assertJsonPath('data.current_balance', 950000)
            ->assertJsonPath('data.reference_id', 'KB-12');

        $this->assertDatabaseHas('api_client_balances', [
            'api_client_id' => $client->id,
            'balance' => '950000.00',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'external_finance',
            'event' => 'deduct_balance',
            'description' => 'Pembayaran kasbon karyawan Toni',
        ]);
    }

    public function test_can_refund_balance_successfully(): void
    {
        [$client, $secret] = $this->register('WEB_REFUND', '1000000.00');

        $payload = [
            'amount' => 500000,
            'reference_id' => 'REFUND-GAJI-5-9-1787929941',
            'description' => 'Refund gaji Budi Santoso — transfer gagal (INVALID_DESTINATION)',
            'reason' => 'INVALID_DESTINATION',
        ];

        $response = $this->postJson(
            '/api/v1/balance/refund',
            $payload,
            $this->headers($client, $secret, 'POST', '/api/v1/balance/refund', $payload)
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Saldo berhasil dikembalikan')
            ->assertJsonPath('data.reference_id', 'REFUND-GAJI-5-9-1787929941')
            ->assertJsonPath('data.amount', 500000)
            ->assertJsonPath('data.balance_after', 1500000);

        $this->assertDatabaseHas('api_client_balances', [
            'api_client_id' => $client->id,
            'balance' => '1500000.00',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'external_finance',
            'event' => 'refund_balance',
            'description' => 'Refund gaji Budi Santoso — transfer gagal (INVALID_DESTINATION)',
        ]);
    }

    public function test_refund_balance_fails_on_duplicate_reference_id(): void
    {
        [$client, $secret] = $this->register('WEB_REFUND_DUP', '1000000.00');

        $payload = [
            'amount' => 200000,
            'reference_id' => 'REFUND-DUP-123',
            'description' => 'Refund initial',
            'reason' => 'TRANSFER_FAILED',
        ];

        $first = $this->postJson(
            '/api/v1/balance/refund',
            $payload,
            $this->headers($client, $secret, 'POST', '/api/v1/balance/refund', $payload)
        );
        $first->assertOk();

        // Coba refund dengan reference_id yang sama
        $second = $this->postJson(
            '/api/v1/balance/refund',
            $payload,
            $this->headers($client, $secret, 'POST', '/api/v1/balance/refund', $payload)
        );
        $second->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('errors.reference_id.0', 'Reference ID sudah digunakan');
    }

    public function test_refund_balance_validation_fails_on_invalid_amount(): void
    {
        [$client, $secret] = $this->register('WEB_REFUND_VAL', '1000000.00');

        $payload = [
            'amount' => 0,
            'reference_id' => 'REFUND-ZERO-123',
            'description' => 'Refund zero amount',
        ];

        $response = $this->postJson(
            '/api/v1/balance/refund',
            $payload,
            $this->headers($client, $secret, 'POST', '/api/v1/balance/refund', $payload)
        );

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
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

    private function headers(ApiClient $client, string $secret, string $method = 'GET', string $path = '/api/v1/balance', array $payload = []): array
    {
        $keyId = $client->activeCredentials()->first()->key_id;
        $timestamp = (string) now()->getTimestamp();
        $nonce = uniqid('nonce_', true);
        $body = $payload === [] ? '[]' : json_encode($payload);

        $canonical = implode("\n", [
            $method, $path, $client->client_id, $keyId, $timestamp, $nonce, hash('sha256', $body),
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
