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
            'name'                  => 'Website History',
            'code'                  => 'WEBHIST',
            'rate_limit_per_minute' => 60,
        ]);

        $this->client = $result['client'];
        $this->secret = $result['secret'];
    }

    public function test_client_can_create_history_entry(): void
    {
        $payload = [
            'event'               => 'balance.synced',
            'subject_type'        => 'balance',
            'subject_external_id' => 'BAL-001',
            'description'         => 'Balance synced from client',
            'properties'          => [
                'amount'       => '125000.00',
                'balance_type' => 'manual',
            ],
        ];

        $response = $this->postJson('/api/v1/history', $payload, $this->headers('POST', '/api/v1/history', $payload));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.event', 'balance.synced')
            ->assertJsonPath('data.description', 'Balance synced from client');

        $this->assertDatabaseHas('activity_log', [
            'log_name'    => 'external_finance',
            'event'       => 'balance.synced',
            'description' => 'Balance synced from client',
        ]);
    }

    public function test_client_only_receives_own_history_entries_by_default(): void
    {
        $otherClient = app(ApiClientService::class)->register([
            'name'                  => 'Other Website',
            'code'                  => 'WEBOTHER',
            'rate_limit_per_minute' => 60,
        ])['client'];

        $ownActivity = Activity::create([
            'log_name'    => 'external_finance',
            'event'       => 'invoice.paid',
            'description' => 'Own invoice paid',
            'properties'  => [
                'api_client_id'       => $this->client->id,
                'client_id'           => $this->client->client_id,
                'client_code'         => $this->client->code,
                'subject_type'        => 'invoice',
                'subject_external_id' => 'INV-001',
            ],
        ]);

        Activity::create([
            'log_name'    => 'external_finance',
            'event'       => 'invoice.paid',
            'description' => 'Other invoice paid',
            'properties'  => [
                'api_client_id'       => $otherClient->id,
                'client_id'           => $otherClient->client_id,
                'client_code'         => $otherClient->code,
                'subject_type'        => 'invoice',
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

    public function test_client_can_retrieve_all_clients_with_client_code_all(): void
    {
        $otherClient = app(ApiClientService::class)->register([
            'name'                  => 'Other Website',
            'code'                  => 'WEBOTHER',
            'rate_limit_per_minute' => 60,
        ])['client'];

        Activity::create([
            'log_name'    => 'external_finance',
            'event'       => 'invoice.paid',
            'description' => 'Own invoice paid',
            'properties'  => [
                'api_client_id'       => $this->client->id,
                'client_code'         => $this->client->code,
                'subject_type'        => 'invoice',
                'subject_external_id' => 'INV-001',
            ],
        ]);

        Activity::create([
            'log_name'    => 'external_finance',
            'event'       => 'invoice.paid',
            'description' => 'Other invoice paid',
            'properties'  => [
                'api_client_id'       => $otherClient->id,
                'client_code'         => $otherClient->code,
                'subject_type'        => 'invoice',
                'subject_external_id' => 'INV-999',
            ],
        ]);

        $response = $this->getJson('/api/v1/history?client_code=ALL', $this->headers('GET', '/api/v1/history?client_code=ALL'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_history_supports_search_and_balance_type_filters(): void
    {
        Activity::create([
            'log_name'    => 'external_finance',
            'event'       => 'xendit_topup',
            'description' => 'Top Up Saldo via Xendit VA BCA',
            'properties'  => [
                'api_client_id'       => $this->client->id,
                'client_code'         => $this->client->code,
                'subject_type'        => 'Income',
                'subject_external_id' => 'INC-XEN-101',
                'amount'              => 1500000.00,
                'balance_type'        => 'xendit',
                'channel'             => 'Xendit Virtual Account BCA',
            ],
        ]);

        Activity::create([
            'log_name'    => 'external_finance',
            'event'       => 'deduct_balance',
            'description' => 'Potong Saldo Operasional Server',
            'properties'  => [
                'api_client_id'       => $this->client->id,
                'client_code'         => $this->client->code,
                'subject_type'        => 'Expense',
                'subject_external_id' => 'EXP-MAN-202',
                'amount'              => 250000.00,
                'balance_type'        => 'manual',
            ],
        ]);

        // 1. Search filter
        $searchResponse = $this->getJson('/api/v1/history?search=INC-XEN', $this->headers('GET', '/api/v1/history?search=INC-XEN'));
        $searchResponse->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.subject_external_id', 'INC-XEN-101');

        // 2. Balance type filter
        $xenditResponse = $this->getJson('/api/v1/history?balance_type=xendit', $this->headers('GET', '/api/v1/history?balance_type=xendit'));
        $xenditResponse->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.balance_type', 'xendit')
            ->assertJsonPath('data.items.0.properties.channel', 'Xendit Virtual Account BCA');

        $this->assertEquals(1500000, $xenditResponse->json('data.items.0.amount'));
    }

    public function test_xendit_synced_transactions_can_be_retrieved_with_client_code_all_or_xendit(): void
    {
        Activity::create([
            'log_name'    => 'external_finance',
            'event'       => 'invoice.paid',
            'description' => 'Pembayaran sebesar Rp 166.170 berhasil diterima via QRIS',
            'properties'  => [
                'source'              => 'xendit',
                'xendit_id'           => 'xen_live_qris_001',
                'reference_id'        => 'INV-992690875805',
                'subject_external_id' => 'INV-992690875805',
                'subject_type'        => 'Income',
                'client_code'         => 'XENDIT',
                'client_name'         => 'Xendit Gateway',
                'channel'             => 'QRIS',
                'amount'              => 166170.00,
                'balance_type'        => 'xendit',
            ],
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->getJson('/api/v1/history?client_code=ALL', $this->headers('GET', '/api/v1/history?client_code=ALL'));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $items = collect($response->json('data.items'));
        $xenditItem = $items->firstWhere('subject_external_id', 'INV-992690875805');
        $this->assertNotNull($xenditItem);
        $this->assertEquals(166170, $xenditItem['amount']);
        $this->assertEquals('XENDIT', $xenditItem['source_client_code']);
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
            'X-Key-ID'    => $credential->key_id,
            'X-Timestamp' => $timestamp,
            'X-Nonce'     => $nonce,
            'X-Signature' => base64_encode(hash_hmac('sha256', $canonical, $this->secret, true)),
        ];
    }
}
