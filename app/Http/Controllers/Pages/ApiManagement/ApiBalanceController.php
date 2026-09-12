<?php

namespace App\Http\Controllers\Pages\ApiManagement;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\BalanceAdjustment;
use App\Models\BalanceChannelSetting;
use App\Services\Api\BalanceService;
use App\Services\Xendit\XenditService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;

class ApiBalanceController extends Controller
{
    public function __construct(
        private BalanceService $balanceService,
        private XenditService $xenditService
    ) {}

    public function index(Request $request)
    {
        $search = $request->search ?? null;
        $balanceTypeFilter = $request->balance_type ?? null;

        $clients = ApiClient::query()
            ->with('balance')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%");
            })
            ->orderBy('id', 'DESC')
            ->get();

        $adjustments = BalanceAdjustment::query()
            ->with(['client', 'adjustedBy'])
            ->when($request->client_id, function ($query, $clientId) {
                $query->where('api_client_id', $clientId);
            })
            ->when($balanceTypeFilter, function ($query, $bt) {
                $query->where('balance_type', $bt);
            })
            ->orderBy('id', 'DESC')
            ->paginate(15);

        $xenditConfigured = $this->xenditService->isConfigured();

        return view('pages.api-management.balances.index', compact('clients', 'adjustments', 'xenditConfigured'));
    }

    public function toggleClientChannel(Request $request, $clientId)
    {
        $client = ApiClient::findOrFail($clientId);

        $request->validate([
            'channel' => 'required|in:manual,xendit',
            'is_active' => 'required|boolean',
        ]);

        $channel = $request->channel;
        $isActive = (bool) $request->is_active;

        if ($channel === 'manual') {
            $client->is_manual_balance_enabled = $isActive;
        } else {
            $client->is_xendit_balance_enabled = $isActive;
        }
        $client->save();

        $channelName = $channel === 'manual' ? 'Saldo Manual' : 'Saldo Xendit';
        $statusText = $isActive ? 'diaktifkan (ON)' : 'dinonaktifkan (OFF)';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'client_id' => $client->id,
                'channel' => $channel,
                'is_active' => $isActive,
                'is_manual_enabled' => $client->isManualBalanceEnabled(),
                'is_xendit_enabled' => $client->isXenditBalanceEnabled(),
                'message' => "{$channelName} untuk {$client->name} berhasil {$statusText}.",
            ]);
        }

        return back()->with('success', "{$channelName} untuk {$client->name} berhasil {$statusText}.");
    }

    public function adjust(Request $request, $clientId)
    {
        $client = ApiClient::findOrFail($clientId);

        $request->validate([
            'type' => 'required|in:adjust_in,adjust_out',
            'balance_type' => 'nullable|in:manual,xendit',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $balanceType = $request->input('balance_type', 'manual') ?: 'manual';

        try {
            $this->balanceService->adjust(
                $client,
                $request->type,
                (float) $request->amount,
                $request->reason,
                auth()->id(),
                $balanceType,
                'admin_adjustment'
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        $pocketLabel = $balanceType === 'xendit' ? 'Saldo Xendit' : 'Saldo Manual';

        return redirect()
            ->route('saldo-website.index')
            ->with('success', "{$pocketLabel} {$client->name} berhasil disesuaikan.");
    }

    public function createXenditTopup(Request $request, $clientId)
    {
        $client = ApiClient::findOrFail($clientId);

        if (! $client->isXenditBalanceEnabled()) {
            return back()->withErrors(['amount' => "Jalur penambahan Saldo Xendit untuk {$client->name} sedang dinonaktifkan (OFF)."])->withInput();
        }

        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'description' => 'nullable|string|max:255',
        ]);

        $amount = (float) $request->amount;
        $externalId = 'TOPUP-CID-' . $client->id . '-' . time();
        $description = $request->description ?: "Top Up Saldo Xendit untuk Website {$client->name}";

        try {
            $invoice = $this->xenditService->createInvoice(
                $externalId,
                $amount,
                $description,
                null,
                route('saldo-website.index')
            );

            // Simpan catatan riwayat awal (pending)
            BalanceAdjustment::create([
                'api_client_id' => $client->id,
                'type' => 'adjust_in',
                'balance_type' => 'xendit',
                'source' => 'xendit',
                'reference_id' => $externalId,
                'xendit_invoice_id' => $invoice['id'] ?? null,
                'payment_status' => 'pending',
                'amount' => $amount,
                'balance_before' => $client->balance?->balance_xendit ?? 0,
                'balance_after' => $client->balance?->balance_xendit ?? 0,
                'reason' => "Menunggu pembayaran Invoice Xendit: {$invoice['id']} ({$description})",
                'adjusted_by' => auth()->id(),
            ]);

            return redirect()
                ->route('saldo-website.index')
                ->with('invoice_data', [
                    'client_name' => $client->name,
                    'amount' => $amount,
                    'invoice_id' => $invoice['id'] ?? '',
                    'invoice_url' => $invoice['invoice_url'] ?? '#',
                    'expiry_date' => $invoice['expiry_date'] ?? null,
                ])
                ->with('success', "Invoice Top-Up Xendit sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dibuat. Silakan selesaikan pembayaran melalui tautan invoice.");
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => 'Gagal membuat Invoice Xendit: ' . $e->getMessage()])->withInput();
        }
    }

    public function clientLogs(Request $request, $id)
    {
        $client = ApiClient::with('balance')->findOrFail($id);

        $eventFilter = $request->event ?? null;
        $limit = min((int) ($request->limit ?? 50), 100);

        $activities = Activity::query()
            ->where(function ($query) use ($client) {
                $query->where('properties->api_client_id', $client->id)
                    ->orWhere('properties->client_id', $client->client_id)
                    ->orWhere('properties->client_code', $client->code)
                    ->orWhere(function ($q) use ($client) {
                        $q->where('subject_type', ApiClient::class)
                          ->where('subject_id', $client->id);
                    });
            })
            ->when($eventFilter, function ($query, $ev) {
                $query->where('event', $ev);
            })
            ->with(['causer'])
            ->latest('id')
            ->limit($limit)
            ->get();

        $manualBal = (float) ($client->balance?->balance_manual ?? 0);
        $xenditBal = (float) ($client->balance?->balance_xendit ?? 0);
        $totalBal  = (float) ($client->balance?->balance ?? ($manualBal + $xenditBal));

        $data = $activities->map(function ($act) {
            $props = $act->properties ? $act->properties->toArray() : [];
            $amount = isset($props['amount']) ? (float) $props['amount'] : null;
            $currentBal = isset($props['current_balance']) ? (float) $props['current_balance'] : (isset($props['balance_after']) ? (float) $props['balance_after'] : null);
            $prevBal = isset($props['previous_balance']) ? (float) $props['previous_balance'] : (isset($props['balance_before']) ? (float) $props['balance_before'] : null);

            return [
                'id' => $act->id,
                'created_at' => $act->created_at ? $act->created_at->format('d M Y, H:i') : '-',
                'created_at_time' => $act->created_at ? $act->created_at->format('H:i:s') : '-',
                'created_at_human' => $act->created_at ? $act->created_at->diffForHumans() : '-',
                'log_name' => $act->log_name,
                'event' => $act->event ?? 'activity',
                'description' => $act->description,
                'causer_name' => $act->causer?->name ?? 'API Client',
                'reference_id' => $props['reference_id'] ?? $props['subject_external_id'] ?? null,
                'request_id' => $props['request_id'] ?? null,
                'category' => $props['category'] ?? null,
                'note' => $props['note'] ?? null,
                'amount' => $amount,
                'amount_formatted' => $amount !== null ? 'Rp ' . number_format($amount, 0, ',', '.') : null,
                'balance_type' => $props['balance_type'] ?? null,
                'balance_before_formatted' => $prevBal !== null ? 'Rp ' . number_format($prevBal, 0, ',', '.') : null,
                'balance_after_formatted' => $currentBal !== null ? 'Rp ' . number_format($currentBal, 0, ',', '.') : null,
                'properties' => $props,
            ];
        });

        return response()->json([
            'success' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'code' => $client->code,
                'client_id' => $client->client_id,
                'status' => $client->status->value ?? 'active',
                'rate_limit' => $client->rate_limit_per_minute,
                'last_used_at' => $client->last_used_at ? $client->last_used_at->format('d M Y, H:i') : '-',
                'last_ip' => $client->last_ip ?? '-',
                'manual_balance' => $manualBal,
                'manual_balance_formatted' => 'Rp ' . number_format($manualBal, 0, ',', '.'),
                'xendit_balance' => $xenditBal,
                'xendit_balance_formatted' => 'Rp ' . number_format($xenditBal, 0, ',', '.'),
                'total_balance' => $totalBal,
                'total_balance_formatted' => 'Rp ' . number_format($totalBal, 0, ',', '.'),
            ],
            'activities' => $data,
        ]);
    }
}
