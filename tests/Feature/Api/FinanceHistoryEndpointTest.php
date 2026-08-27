<?php

namespace Tests\Feature\Api;

use App\Models\ApiClient;
use App\Services\Api\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class FinanceHistoryEndpointTest extends TestCase
{
    use RefreshDatabase;

    private ApiClient $client;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();

        $result = app(ApiClientService::class)->register([
            'name' => 'Website History',
            'code' => 'WEBHIST',
            'rate_limit_per_minute' => 60,
        ]);

        $this->client = $result['client'];
        $this->secret = $result['secret'];
    }

    public function test_client_can_create_history_entry(): void
    {
        $payload = [
            'event' => 'balance.synced',
            'subject_type' => 'balance',
            'subject_external_id' => 'BAL-001',
            'description' => 'Balance synced from client',
            'properties' => [
                'amount' => '125000.00',
            ],
        ];

        $response = $this->postJson('/api/v1/history', $payload, $this->headers('POST', '/api/v1/history', $payload));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.event', 'balance.synced')
            ->assertJsonPath('data.description', 'Balance synced from client');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'external_finance',
            'event' => 'balance.synced',
            'description' => 'Balance synced from client',
        ]);
    }

    public function test_client_only_receives_own_history_entries(): void
    {
        $otherClient = app(ApiClientService::class)->register([
            'name' => 'Other Website',
            'code' => 'WEBOTHER',
            'rate_limit_per_minute' => 60,
        ])['client'];

        $ownActivity = Activity::create([
            'log_name' => 'external_finance',
            'event' => 'invoice.paid',
            'description' => 'Own invoice paid',
            'properties' => [
                'api_client_id' => $this->client->id,
                'client_id' => $this->client->client_id,
                'client_code' => $this->client->code,
                'subject_type' => 'invoice',
                'subject_external_id' => 'INV-001',
            ],
        ]);

        Activity::create([
            'log_name' => 'external_finance',
            'event' => 'invoice.paid',
            'description' => 'Other invoice paid',
            'properties' => [
                'api_client_id' => $otherClient->id,
                'client_id' => $otherClient->client_id,
                'client_code' => $otherClient->code,
                'subject_type' => 'invoice',
                'subject_external_id' => 'INV-999',
            ],
        ]);

        $response = $this->getJson('/api/v1/history?subject_type=invoice', $this->headers('GET', '/api/v1/history?subject_type=invoice'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.id', $ownActivity->id)
            ->assertJsonPath('data.items.0.subject_external_id', 'INV-001')
            ->assertJsonPath('data.pagination.total', 1);

        $this->assertStringNotContainsString('Other invoice paid', $response->getContent());
    }

    private function headers(string $method, string $pathWithQuery, array $payload = []): array
    {
        $credential = $this->client->activeCredentials()->first();
        $timestamp = (string) now()->getTimestamp();
        $nonce = uniqid('nonce_', true);
        $body = $payload === [] ? '[]' : json_encode($payload);

        $canonical = implode("\n", [
            $method,
            $pathWithQuery,
            $this->client->client_id,
            $credential->key_id,
            $timestamp,
            $nonce,
            hash('sha256', $body),
        ]);

        return [
            'X-Client-ID' => $this->client->client_id,
            'X-Key-ID' => $credential->key_id,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => base64_encode(hash_hmac('sha256', $canonical, $this->secret, true)),
        ];
    }
}
