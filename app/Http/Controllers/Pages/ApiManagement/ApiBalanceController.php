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
            'balance_type' => 'required|in:manual,xendit',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->balanceService->adjust(
                $client,
                $request->type,
                (float) $request->amount,
                $request->reason,
                auth()->id(),
                $request->balance_type,
                'admin_adjustment'
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        $pocketLabel = $request->balance_type === 'xendit' ? 'Saldo Xendit' : 'Saldo Manual';

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
}
