<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\BalanceAdjustment;
use App\Models\BalanceChannelSetting;
use App\Services\Api\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class XenditWebhookController extends Controller
{
    public function __construct(private BalanceService $balanceService) {}

    public function handleInvoice(Request $request)
    {
        $payload = $request->all();
        Log::info('[Xendit Webhook] Received invoice callback', $payload);

        $status = strtoupper($payload['status'] ?? '');
        $externalId = $payload['external_id'] ?? '';
        $amount = (float) ($payload['amount'] ?? 0);
        $invoiceId = $payload['id'] ?? '';
        $paymentChannel = $payload['payment_channel'] ?? $payload['payment_method'] ?? 'Xendit';

        if (! in_array($status, ['PAID', 'SETTLED'])) {
            return response()->json(['message' => 'Ignored non-paid invoice status: ' . $status], 200);
        }

        // Cek apakah invoice sudah pernah diproses untuk mencegah double topup
        $existing = BalanceAdjustment::where('xendit_invoice_id', $invoiceId)
            ->where('payment_status', 'completed')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Invoice already processed'], 200);
        }

        // Cari client berdasarkan external_id (contoh pattern: TOPUP-{CLIENT_ID}-{TIME} atau TOPUP-CODE-{CODE}-{TIME})
        $client = null;
        if (preg_match('/^TOPUP-CID-(\d+)-/', $externalId, $matches)) {
            $client = ApiClient::find($matches[1]);
        } elseif (preg_match('/^TOPUP-CODE-([A-Za-z0-9_-]+)-/', $externalId, $matches)) {
            $client = ApiClient::where('code', $matches[1])->first();
        }

        if (! $client) {
            // Catat sebagai transaksi pembayaran umum (QRIS, E-Commerce, dsb.) ke activity log
            $existingActivity = Activity::query()
                ->where('log_name', 'external_finance')
                ->where(function ($q) use ($invoiceId, $externalId) {
                    $q->where('properties->xendit_id', $invoiceId);
                    if (! empty($externalId)) {
                        $q->orWhere('properties->reference_id', $externalId)
                          ->orWhere('properties->subject_external_id', $externalId);
                    }
                })
                ->first();

            if (! $existingActivity) {
                $formattedAmount = 'Rp ' . number_format($amount, 0, ',', '.');
                $refStr = $externalId ? " (Ref: {$externalId})" : '';
                $desc = "Pembayaran sebesar {$formattedAmount} berhasil diterima via {$paymentChannel}{$refStr}";

                Activity::create([
                    'log_name'    => 'external_finance',
                    'event'       => 'invoice.paid',
                    'description' => $desc,
                    'properties'  => [
                        'source'              => 'xendit',
                        'xendit_id'           => $invoiceId,
                        'reference_id'        => $externalId,
                        'subject_external_id' => $externalId ?: $invoiceId,
                        'subject_type'        => 'Income',
                        'client_code'         => 'XENDIT',
                        'client_name'         => 'Xendit Gateway',
                        'channel'             => $paymentChannel,
                        'raw_channel'         => $paymentChannel,
                        'amount'              => $amount,
                        'balance_type'        => 'xendit',
                        'status'              => $status,
                    ],
                    'created_at'  => now('Asia/Jakarta'),
                    'updated_at'  => now('Asia/Jakarta'),
                ]);
            }

            Log::info('[Xendit Webhook] General payment recorded in activity log: ' . $externalId);

            return response()->json(['status' => 'success', 'message' => 'General invoice payment recorded in activity log'], 200);
        }

        if (! BalanceChannelSetting::isXenditActive()) {
            Log::warning('[Xendit Webhook] Channel Saldo Xendit is disabled. Top-up rejected for Client: ' . $client->name);
            return response()->json(['message' => 'Channel Saldo Xendit is disabled'], 400);
        }

        try {
            $this->balanceService->topupViaXendit(
                $client,
                $amount,
                $invoiceId,
                $paymentChannel,
                $externalId,
                "Top-Up Saldo otomatis via Xendit ({$paymentChannel}) [{$externalId}]"
            );

            return response()->json(['status' => 'success', 'message' => 'Balance successfully credited'], 200);
        } catch (\Throwable $e) {
            Log::error('[Xendit Webhook] Error processing top-up: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
