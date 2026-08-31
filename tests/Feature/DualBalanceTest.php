<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\ApiClientBalance;
use App\Services\Api\BalanceService;
use Tests\TestCase;

class DualBalanceTest extends TestCase
{
    public function test_per_client_dual_balance_service_and_channel_toggle()
    {
        $client = ApiClient::first();
        if (! $client) {
            $this->assertTrue(true);
            return;
        }

        $balanceService = app(BalanceService::class);

        // 1. Set Per-Client Channels ON
        $client->update([
            'is_manual_balance_enabled' => true,
            'is_xendit_balance_enabled' => true,
        ]);
        $client->refresh();

        $balance = $client->balance;
        $initialManual = (float) ($balance->balance_manual ?? 0);
        $initialXendit = (float) ($balance->balance_xendit ?? 0);

        // 2. Adjust In Manual for this client
        $balanceService->adjust($client, 'adjust_in', 25000, 'Test Tambah Saldo Manual', null, 'manual');
        $client->refresh();
        $this->assertEquals($initialManual + 25000, (float) $client->balance->balance_manual);

        // 3. Adjust In Xendit for this client
        $balanceService->adjust($client, 'adjust_in', 50000, 'Test Tambah Saldo Xendit', null, 'xendit');
        $client->refresh();
        $this->assertEquals($initialXendit + 50000, (float) $client->balance->balance_xendit);
        $this->assertEquals($client->balance->balance_manual + $client->balance->balance_xendit, (float) $client->balance->balance);

        // 4. Test Client Manual Disabled Rejection
        $client->update(['is_manual_balance_enabled' => false]);
        $client->refresh();
        $this->expectException(\InvalidArgumentException::class);
        $balanceService->adjust($client, 'adjust_in', 10000, 'Harus Gagal Karena Client Manual OFF', null, 'manual');
    }

    public function test_api_balance_controller_deduct_and_refund_responses()
    {
        $client = ApiClient::first();
        if (! $client) {
            $this->assertTrue(true);
            return;
        }

        $client->update([
            'is_manual_balance_enabled' => true,
            'is_xendit_balance_enabled' => true,
        ]);
        $client->refresh();

        $balance = ApiClientBalance::where('api_client_id', $client->id)->first();
        $balance->balance_manual = 200000;
        $balance->balance_xendit = 100000;
        $balance->recalculateTotal();
        $balance->save();

        // 1. Test Deduct from Xendit
        $request = \Illuminate\Http\Request::create('/api/v1/balance/deduct', 'POST', [
            'amount' => 30000,
            'balance_type' => 'xendit',
            'reference_id' => 'TEST-DEDUCT-XENDIT-' . uniqid(),
            'description' => 'Test Deduct Xendit',
        ]);
        $request->attributes->set('api_client', $client);
        $request->attributes->set('request_id', 'REQ-TEST-1');

        $controller = app(\App\Http\Controllers\Api\V1\BalanceController::class);
        $response = $controller->deduct($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('xendit', $data['data']['balance_type']);
        $this->assertEquals('70000.00', $data['data']['balance_xendit']);
        $this->assertEquals('200000.00', $data['data']['balance_manual']);

        // 2. Test Refund to Manual
        $refId = 'TEST-REFUND-MANUAL-' . uniqid();
        $refundRequest = \Illuminate\Http\Request::create('/api/v1/balance/refund', 'POST', [
            'amount' => 15000,
            'balance_type' => 'manual',
            'reference_id' => $refId,
            'description' => 'Test Refund Manual',
        ]);
        $refundRequest->attributes->set('api_client', $client);
        $refundRequest->attributes->set('request_id', 'REQ-TEST-2');

        $refundResponse = $controller->refund($refundRequest);
        $this->assertEquals(200, $refundResponse->getStatusCode());
        $refundData = json_decode($refundResponse->getContent(), true);
        $this->assertEquals('manual', $refundData['data']['balance_type']);
        $this->assertEquals('215000.00', $refundData['data']['balance_manual']);
        $this->assertEquals('70000.00', $refundData['data']['balance_xendit']);

        // 3. Test GET Balance API Response (Per-Client Channel Status)
        $indexRequest = \Illuminate\Http\Request::create('/api/v1/balance', 'GET');
        $indexRequest->attributes->set('api_client', $client);
        $indexResponse = $controller->index($indexRequest);
        $this->assertEquals(200, $indexResponse->getStatusCode());
        $indexData = json_decode($indexResponse->getContent(), true);
        $this->assertArrayHasKey('balance_manual', $indexData['data']);
        $this->assertArrayHasKey('balance_xendit', $indexData['data']);
        $this->assertArrayHasKey('total_balance', $indexData['data']);
        $this->assertArrayHasKey('channel_status', $indexData['data']);
        $this->assertTrue($indexData['data']['channel_status']['manual']);
        $this->assertTrue($indexData['data']['channel_status']['xendit']);
    }
}
