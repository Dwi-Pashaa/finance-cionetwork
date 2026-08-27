<?php

namespace Tests\Feature\ApiManagement;

use App\Enums\ApiClientStatus;
use App\Models\ApiClient;
use App\Services\Api\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private ApiClientService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApiClientService::class);
    }

    public function test_register_generates_unique_client_id_and_balance_row(): void
    {
        [$client] = $this->register('WEB2');

        $this->assertDatabaseHas('api_clients', ['code' => 'WEB2', 'status' => 'active']);
        $this->assertStringStartsWith('web2_', $client->client_id);
        $this->assertDatabaseHas('api_client_balances', [
            'api_client_id' => $client->id,
            'balance' => '0.00',
        ]);

        [$second] = $this->register('WEB3');
        $this->assertNotSame($client->client_id, $second->client_id);
    }

    public function test_secret_is_never_stored_as_plaintext(): void
    {
        [, $secret] = $this->register('WEB2');

        $this->assertSame(64, strlen($secret));

        $credential = ApiClient::where('code', 'WEB2')->first()->activeCredentials()->first();

        $this->assertSame(hash('sha256', $secret), $credential->secret_hash);
        $this->assertStringNotContainsString($secret, $credential->secret_encrypted);
        $this->assertSame($secret, Crypt::decryptString($credential->secret_encrypted));
    }

    public function test_revoke_client_revokes_all_active_credentials(): void
    {
        [$client] = $this->register('WEB2');

        $this->service->setStatus($client->fresh(), ApiClientStatus::Revoked);

        $client = $client->fresh();
        $this->assertSame('revoked', $client->status->value);
        $this->assertNotNull($client->revoked_at);
        $this->assertSame(0, $client->activeCredentials()->count());
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->register('WEB2');
        $this->service->register(['name' => 'Dup', 'code' => 'WEB2']);
    }

    private function register(string $code): array
    {
        $result = $this->service->register([
            'name' => "Website {$code}",
            'code' => $code,
            'rate_limit_per_minute' => 60,
        ]);

        return [$result['client'], $result['secret']];
    }
}
