<?php

namespace Tests\Feature\ApiManagement;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ApiClient;
use App\Models\BalanceAdjustment;
use App\Models\User;
use App\Services\Api\ApiClientService;
use App\Services\Api\BalanceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private BalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->seed(DatabaseSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['Api Management', 'lihat saldo', 'buat saldo']);
        $this->service = app(BalanceService::class);
    }

    private function client(string $code = 'WEB2'): ApiClient
    {
        return app(ApiClientService::class)
            ->register(['name' => "Website {$code}", 'code' => $code])['client'];
    }

    public function test_adjust_in_increases_balance_and_records_history(): void
    {
        $client = $this->client();

        $this->actingAs($this->admin)->post(route('saldo-website.adjust', $client->id), [
            'type' => 'adjust_in',
            'amount' => 5000000,
            'reason' => 'Top up modal awal',
        ])->assertRedirect()->assertSessionHas('success');

        $client->refresh();
        $this->assertSame('5000000.00', (string) $client->balance->balance);
        $this->assertDatabaseHas('balance_adjustments', [
            'api_client_id' => $client->id,
            'type' => 'adjust_in',
            'amount' => '5000000.00',
            'balance_before' => '0.00',
            'balance_after' => '5000000.00',
            'adjusted_by' => $this->admin->id,
        ]);
    }

    public function test_adjust_out_decreases_balance(): void
    {
        $client = $this->client();
        $this->service->adjust($client, 'adjust_in', 1000000, 'seed', $this->admin->id);

        $this->actingAs($this->admin)->post(route('saldo-website.adjust', $client->id), [
            'type' => 'adjust_out',
            'amount' => 400000,
            'reason' => 'Koreksi',
        ])->assertRedirect();

        $client->refresh();
        $this->assertSame('600000.00', (string) $client->balance->balance);
    }

    public function test_adjust_out_below_zero_is_rejected(): void
    {
        $client = $this->client();

        $response = $this->actingAs($this->admin)
            ->from(route('saldo-website.index'))
            ->post(route('saldo-website.adjust', $client->id), [
                'type' => 'adjust_out',
                'amount' => 1000,
                'reason' => 'Kurang',
            ]);

        $response->assertRedirect()->assertSessionHasErrors(['amount']);
        $this->assertSame('0.00', (string) $client->fresh()->balance->balance);
        $this->assertSame(0, BalanceAdjustment::count());
    }

    public function test_validation_requires_reason_and_positive_amount(): void
    {
        $client = $this->client();

        $this->actingAs($this->admin)
            ->post(route('saldo-website.adjust', $client->id), [
                'type' => 'adjust_in',
                'amount' => -5,
            ])
            ->assertSessionHasErrors(['amount', 'reason']);
    }

    public function test_balance_page_requires_lihat_saldo_permission(): void
    {
        $user = User::factory()->create(); // tanpa permission

        $this->actingAs($user)
            ->get(route('saldo-website.index'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('saldo-website.index'))
            ->assertOk()
            ->assertSee('Pengaturan Saldo Website');
    }

    public function test_adjust_requires_buat_saldo_permission(): void
    {
        $client = $this->client();

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('lihat saldo');

        $this->actingAs($viewer)
            ->post(route('saldo-website.adjust', $client->id), [
                'type' => 'adjust_in',
                'amount' => 1000,
                'reason' => 'Tidak boleh',
            ])
            ->assertForbidden();

        $this->assertSame(0, BalanceAdjustment::count());
    }
}
