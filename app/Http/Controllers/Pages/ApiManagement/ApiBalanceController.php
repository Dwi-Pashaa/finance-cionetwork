<?php

namespace App\Http\Controllers\Pages\ApiManagement;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\BalanceAdjustment;
use App\Services\Api\BalanceService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ApiBalanceController extends Controller
{
    public function __construct(private BalanceService $balanceService) {}

    public function index(Request $request)
    {
        $search = $request->search ?? null;

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
            ->orderBy('id', 'DESC')
            ->paginate(10);

        return view('pages.api-management.balances.index', compact('clients', 'adjustments'));
    }

    public function adjust(Request $request, $clientId)
    {
        $client = ApiClient::findOrFail($clientId);

        $request->validate([
            'type' => 'required|in:adjust_in,adjust_out',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $this->balanceService->adjust(
                $client,
                $request->type,
                (float) $request->amount,
                $request->reason,
                auth()->id()
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()
            ->route('saldo-website.index')
            ->with('success', "Saldo {$client->name} berhasil disesuaikan.");
    }
}
