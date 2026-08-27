<?php

namespace Tests\Feature\Api;

use App\Models\ApiClient;
use App\Services\Api\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HmacAuthTest extends TestCase
{
    use RefreshDatabase;

    private string $secret;

    private string $keyId;

    private ApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $result = app(ApiClientService::class)->register([
            'name' => 'Website 2',
            'code' => 'WEB2',
            'rate_limit_per_minute' => 60,
        ]);

        $this->client = $result['client'];
        $this->secret = $result['secret'];
        $this->keyId = $this->client->activeCredentials()->first()->key_id;
    }

    private function headers(array $overrides = []): array
    {
        $timestamp = (string) now()->getTimestamp();
        $nonce = uniqid('nonce_', true);
        $body = '[]'; // getJson mengirim body '[]' untuk request tanpa payload

        $canonical = implode("\n", [
            'GET',
            '/api/v1/ping',
            $this->client->client_id,
            $this->credential()->key_id,
            $timestamp,
            $nonce,
            hash('sha256', $body),
        ]);

        return array_merge([
            'X-Client-ID' => $this->client->client_id,
            'X-Key-ID' => $this->keyId,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => base64_encode(hash_hmac('sha256', $canonical, $this->secret, true)),
        ], $overrides);
    }

    private function credential()
    {
        return $this->client->credentials()->where('key_id', $this->keyId)->first();
    }

    public function test_valid_signature_returns_200(): void
    {
        $response = $this->getJson('/api/v1/ping', $this->headers());

        if (! $response->isOk()) {
            fwrite(STDERR, "\nDEBUG BODY: ".$response->getContent()."\n");
        }

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.client_code', 'WEB2')
            ->assertJsonStructure(['success', 'message', 'data', 'request_id']);

        $this->client->refresh();
        $this->assertNotNull($this->client->last_used_at);
        $this->assertSame('127.0.0.1', $this->client->last_ip);
    }

    public function test_missing_headers_return_401(): void
    {
        $this->getJson('/api/v1/ping')->assertStatus(401)->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_wrong_signature_returns_401(): void
    {
        $headers = $this->headers(['X-Signature' => base64_encode(str_repeat('x', 32))]);

        $this->getJson('/api/v1/ping', $headers)
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'SIGNATURE_INVALID');
    }

    public function test_wrong_signature_does_not_consume_nonce(): void
    {
        $headers = $this->headers();
        $headers['X-Signature'] = base64_encode(str_repeat('x', 32));

        $this->getJson('/api/v1/ping', $headers)
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'SIGNATURE_INVALID');

        $canonical = implode("\n", [
            'GET',
            '/api/v1/ping',
            $this->client->client_id,
            $this->keyId,
            $headers['X-Timestamp'],
            $headers['X-Nonce'],
            hash('sha256', '[]'),
        ]);

        $headers['X-Signature'] = base64_encode(hash_hmac('sha256', $canonical, $this->secret, true));

        $this->getJson('/api/v1/ping', $headers)->assertOk();
    }

    public function test_stale_timestamp_returns_401(): void
    {
        $headers = $this->headers(['X-Timestamp' => (string) (now()->getTimestamp() - 3600)]);

        // signature dihitung ulang dengan timestamp basi agar hanya timestamp yang gagal
        $canonical = implode("\n", [
            'GET', '/api/v1/ping', $this->client->client_id, $this->keyId,
            (string) (now()->getTimestamp() - 3600), $headers['X-Nonce'], hash('sha256', ''),
        ]);
        $headers['X-Signature'] = base64_encode(hash_hmac('sha256', $canonical, $this->secret, true));

        $this->getJson('/api/v1/ping', $headers)
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'TIMESTAMP_INVALID');
    }

    public function test_replayed_nonce_returns_409(): void
    {
        $headers = $this->headers();

        $this->getJson('/api/v1/ping', $headers)->assertOk();
        $this->getJson('/api/v1/ping', $headers)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'NONCE_REUSED');
    }

    public function test_disabled_client_returns_403(): void
    {
        $this->client->update(['status' => 'inactive']);

        $this->getJson('/api/v1/ping', $this->headers())
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'CLIENT_DISABLED');
    }

    public function test_revoked_client_returns_403(): void
    {
        $this->client->update(['status' => 'revoked']);

        $this->getJson('/api/v1/ping', $this->headers())
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'CLIENT_REVOKED');
    }

    public function test_revoked_credential_returns_401(): void
    {
        $this->credential()->update(['status' => 'revoked']);

        $this->getJson('/api/v1/ping', $this->headers())
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'CREDENTIAL_INVALID');
    }

    public function test_health_endpoint_requires_no_auth(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('data.status', 'ok');
    }
}
