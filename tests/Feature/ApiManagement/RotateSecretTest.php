<?php

namespace Tests\Feature\ApiManagement;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Services\Api\ApiClientService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotateSecretTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->seed(DatabaseSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('Api Management');
    }

    public function test_rotate_overlap_keeps_old_credential_active_with_expiry(): void
    {
        $result = app(ApiClientService::class)->register(['name' => 'Website 2', 'code' => 'WEB2']);
        $client = $result['client'];

        $oldKeyId = $client->activeCredentials()->first()->key_id;

        $response = $this->actingAs($this->admin)
            ->post(route('api-management.credentials.rotate', $client->id), ['mode' => 'overlap']);

        $response->assertRedirect();

        $credentials = $client->credentials()->where('status', 'active')->get();
        $this->assertSame(2, $credentials->count());

        $old = $credentials->firstWhere('key_id', $oldKeyId);
        $this->assertNotNull($old);
        $this->assertNotNull($old->expires_at);
    }

    public function test_rotate_immediate_revokes_old_credential(): void
    {
        $service = app(ApiClientService::class);
        $result = $service->register(['name' => 'Website 3', 'code' => 'WEB3']);
        $client = $result['client'];

        $oldKeyId = $client->activeCredentials()->first()->key_id;

        $this->actingAs($this->admin)
            ->post(route('api-management.credentials.rotate', $client->id), ['mode' => 'immediate'])
            ->assertRedirect();

        $client = $client->fresh();
        $this->assertSame(1, $client->activeCredentials()->count());
        $this->assertNotSame($oldKeyId, $client->activeCredentials()->first()->key_id);
        $this->assertSame('revoked', $client->credentials()->where('key_id', $oldKeyId)->first()->status->value);
    }

    public function test_provisioned_page_shows_secret_only_once(): void
    {
        $result = app(ApiClientService::class)->register(['name' => 'Website 4', 'code' => 'WEB4']);
        $client = $result['client'];

        session(['provisioned_secret' => encrypt($result['secret'])]);

        $this->actingAs($this->admin)
            ->get(route('api-management.provisioned', $client->id))
            ->assertOk()
            ->assertSee($result['secret'], false);

        // kedua kali: secret sudah tidak ada di session
        $this->actingAs($this->admin)
            ->get(route('api-management.provisioned', $client->id))
            ->assertRedirect(route('api-management.show', $client->id));
    }

    public function test_route_requires_api_management_permission(): void
    {
        $user = User::factory()->create(); // tanpa permission

        $this->actingAs($user)
            ->get(route('api-management.index'))
            ->assertForbidden();
    }
}
