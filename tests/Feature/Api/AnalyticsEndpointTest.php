<?php

namespace Tests\Feature\Api;

use App\Models\ApiClient;
use App\Models\ApiClientBalance;
use App\Models\Expense;
use App\Models\FinanceCategory;
use App\Models\Income;
use App\Services\Api\ApiClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AnalyticsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private ApiClient $client;
    private string $secret;
    private FinanceCategory $incomeCategory;
    private FinanceCategory $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $result = app(ApiClientService::class)->register([
            'name'                  => 'CIO Saham & Portofolio Ekuitas',
            'code'                  => 'CIO_SAHAM',
            'rate_limit_per_minute' => 60,
        ]);

        $this->client = $result['client'];
        $this->secret = $result['secret'];

        $this->incomeCategory = FinanceCategory::create([
            'type'      => 'income',
            'name'      => 'Revenue Portofolio',
            'is_active' => true,
        ]);

        $this->expenseCategory = FinanceCategory::create([
            'type'      => 'expense',
            'name'      => 'Biaya Operasional',
            'is_active' => true,
        ]);

        // Setup Balance
        $bal = $this->client->balance ?: ApiClientBalance::create(['api_client_id' => $this->client->id]);
        $bal->update([
            'balance_manual' => 45400000.00,
            'balance_xendit' => 80000000.00,
            'balance'        => 125400000.00,
        ]);

        // Setup Incomes & Expenses for current month
        Income::create([
            'finance_category_id' => $this->incomeCategory->id,
            'transaction_date'    => now()->toDateString(),
            'amount'              => 48500000.00,
            'description'         => 'Pendapatan Transaksi Web Client',
        ]);

        Expense::create([
            'finance_category_id' => $this->expenseCategory->id,
            'transaction_date'    => now()->toDateString(),
            'amount'              => 12000000.00,
            'has_admin_fee'       => true,
            'admin_fee_amount'    => 200000.00,
            'description'         => 'Pengeluaran Server & Pemeliharaan',
        ]);

        // Setup Previous Month Incomes & Expenses
        Income::create([
            'finance_category_id' => $this->incomeCategory->id,
            'transaction_date'    => now()->subMonth()->startOfMonth()->toDateString(),
            'amount'              => 39000000.00,
            'description'         => 'Pendapatan Bulan Lalu',
        ]);

        Expense::create([
            'finance_category_id' => $this->expenseCategory->id,
            'transaction_date'    => now()->subMonth()->startOfMonth()->toDateString(),
            'amount'              => 11000000.00,
            'has_admin_fee'       => false,
            'admin_fee_amount'    => 0,
            'description'         => 'Pengeluaran Bulan Lalu',
        ]);

        // External activity log
        Activity::create([
            'log_name'    => 'external_finance',
            'event'       => 'deduct_balance',
            'description' => 'Pemotongan Saldo API',
            'properties'  => [
                'api_client_id' => $this->client->id,
                'client_code'   => $this->client->code,
                'client_name'   => $this->client->name,
                'subject_type'  => 'Expense',
                'amount'        => 500000.00,
                'balance_type'  => 'manual',
            ],
        ]);
    }

    public function test_analytics_endpoints_require_hmac_auth(): void
    {
        $this->getJson('/api/v1/analytics/overview')->assertUnauthorized();
        $this->getJson('/api/v1/analytics/chart')->assertUnauthorized();
        $this->getJson('/api/v1/analytics/growth')->assertUnauthorized();
    }

    public function test_overview_endpoint_returns_consolidated_metrics(): void
    {
        $path = '/api/v1/analytics/overview';
        $response = $this->getJson($path, $this->headers('GET', $path));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Overview analytics retrieved successfully')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'balance' => [
                        'total',
                        'manual',
                        'xendit',
                    ],
                    'current_month' => [
                        'revenue',
                        'expenses',
                        'net_profit',
                        'profit_margin',
                    ],
                    'previous_month' => [
                        'revenue',
                        'expenses',
                        'net_profit',
                    ],
                    'active_clients_count',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(125400000.00, $data['balance']['total']);
        $this->assertEquals(45400000.00, $data['balance']['manual']);
        $this->assertEquals(80000000.00, $data['balance']['xendit']);
        $this->assertGreaterThan(0, $data['current_month']['revenue']);
        $this->assertGreaterThan(0, $data['current_month']['expenses']);
        $this->assertEquals(1, $data['active_clients_count']);
    }

    public function test_overview_endpoint_supports_client_code_filter(): void
    {
        $path = '/api/v1/analytics/overview?client_code=CIO_SAHAM';
        $response = $this->getJson($path, $this->headers('GET', $path));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_clients_count', 1);

        $this->assertEquals(125400000, $response->json('data.balance.total'));
    }

    public function test_chart_endpoint_returns_timeseries_dataset(): void
    {
        $path = '/api/v1/analytics/chart?range=7d&interval=daily';
        $response = $this->getJson($path, $this->headers('GET', $path));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Chart time-series retrieved successfully')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'categories',
                    'series' => [
                        '*' => [
                            'name',
                            'data',
                        ],
                    ],
                    'summary' => [
                        'total_inflow',
                        'total_outflow',
                        'net_profit',
                        'profit_margin_pct',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(7, $data['categories']);
        $this->assertCount(3, $data['series']);
        $this->assertEquals('Pemasukan (Inflow)', $data['series'][0]['name']);
        $this->assertEquals('Pengeluaran (Outflow)', $data['series'][1]['name']);
        $this->assertEquals('Laba Bersih (Net Profit)', $data['series'][2]['name']);
    }

    public function test_chart_endpoint_supports_various_ranges(): void
    {
        foreach (['30d', '90d', 'ytd', '1y'] as $range) {
            $path = "/api/v1/analytics/chart?range={$range}";
            $response = $this->getJson($path, $this->headers('GET', $path));

            $response->assertOk()
                ->assertJsonPath('success', true);

            $this->assertNotEmpty($response->json('data.categories'));
        }
    }

    public function test_growth_endpoint_returns_growth_and_valuation_metrics(): void
    {
        $path = '/api/v1/analytics/growth';
        $response = $this->getJson($path, $this->headers('GET', $path));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Company growth metrics calculated successfully')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'growth_rate_mom',
                    'growth_rate_yoy',
                    'financial_health_score',
                    'status',
                    'profitability' => [
                        'gross_margin',
                        'net_margin',
                        'monthly_burn_rate',
                        'runway_months',
                    ],
                    'estimated_company_valuation',
                ],
            ]);

        $data = $response->json('data');
        $this->assertIsNumeric($data['growth_rate_mom']);
        $this->assertIsNumeric($data['growth_rate_yoy']);
        $this->assertGreaterThanOrEqual(0, $data['financial_health_score']);
        $this->assertLessThanOrEqual(100, $data['financial_health_score']);
        $this->assertNotEmpty($data['status']);
        $this->assertGreaterThan(0, $data['estimated_company_valuation']);
    }

    public function test_chart_endpoint_includes_synced_xendit_transactions_across_multi_day_buckets(): void
    {
        // Add synced Xendit activity 15 days ago
        Activity::create([
            'log_name'    => 'external_finance',
            'event'       => 'invoice.paid',
            'description' => 'Pembayaran QRIS Xendit 15 Hari Lalu',
            'properties'  => [
                'source'              => 'xendit',
                'xendit_id'           => 'xen_trx_test_15d',
                'subject_external_id' => 'INV-TEST-15D',
                'subject_type'        => 'Income',
                'client_code'         => 'XENDIT',
                'amount'              => 5000000.00,
                'balance_type'        => 'xendit',
            ],
            'created_at'  => now()->subDays(15),
            'updated_at'  => now()->subDays(15),
        ]);

        $path = '/api/v1/analytics/chart?range=30d&interval=daily';
        $response = $this->getJson($path, $this->headers('GET', $path));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $summary = $response->json('data.summary');
        $this->assertGreaterThanOrEqual(5000000.00, $summary['total_inflow']);
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
