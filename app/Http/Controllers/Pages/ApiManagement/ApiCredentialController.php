<?php

namespace App\Http\Controllers\Pages\ApiManagement;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\ApiCredential;
use App\Services\Api\ApiCredentialService;
use Illuminate\Http\Request;

class ApiCredentialController extends Controller
{
    public function __construct(private ApiCredentialService $credentialService) {}

    public function rotate(Request $request, $clientId)
    {
        $client = ApiClient::findOrFail($clientId);

        $request->validate([
            'mode' => 'required|in:overlap,immediate',
        ]);

        if ($client->status->value !== 'active') {
            return back()->withErrors(['mode' => 'Rotate secret hanya bisa dilakukan pada client berstatus active.']);
        }

        $secret = $this->credentialService->rotate(
            $client,
            immediate: $request->mode === 'immediate'
        );

        session(['provisioned_secret' => encrypt($secret)]);

        return redirect()
            ->route('api-management.provisioned', $client->id)
            ->with('success', 'Secret baru berhasil dibuat. Simpan sekarang, tidak akan ditampilkan lagi.');
    }

    public function revoke(Request $request, $credentialId)
    {
        $credential = ApiCredential::with('client')->findOrFail($credentialId);

        try {
            $this->credentialService->revoke($credential);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['revoke' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Kredential berhasil dicabut.']);
        }

        return redirect()
            ->route('api-management.show', $credential->api_client_id)
            ->with('success', 'Kredential berhasil dicabut.');
    }
}
