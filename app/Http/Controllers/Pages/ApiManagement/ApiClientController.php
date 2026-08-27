<?php

namespace App\Http\Controllers\Pages\ApiManagement;

use App\Enums\ApiClientStatus;
use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Services\Api\ApiClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ApiClientController extends Controller
{
    public function __construct(private ApiClientService $clientService) {}

    public function index(Request $request)
    {
        $sort = $request->sort ?? 10;
        $search = $request->search ?? null;

        $clients = ApiClient::query()
            ->with('balance')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%")
                    ->orWhere('client_id', 'like', "%$search%");
            })
            ->orderBy('id', 'DESC')
            ->paginate($sort);

        return view('pages.api-management.index', compact('clients'));
    }

    public function create()
    {
        return view('pages.api-management.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|alpha_dash|unique:api_clients,code',
            'description' => 'nullable|string|max:500',
            'rate_limit_per_minute' => 'required|integer|min:1|max:10000',
        ]);

        $result = $this->clientService->register($request->only([
            'name', 'code', 'description', 'rate_limit_per_minute',
        ]));

        Session::put('provisioned_secret', encrypt($result['secret']));

        return redirect()
            ->route('api-management.provisioned', $result['client']->id)
            ->with('success', 'Client berhasil didaftarkan. Simpan secret sekarang, tidak akan ditampilkan lagi.');
    }

    public function show($id)
    {
        $client = ApiClient::with(['credentials', 'balance'])->findOrFail($id);

        return view('pages.api-management.show', compact('client'));
    }

    public function provisioned($id)
    {
        $client = ApiClient::findOrFail($id);
        $encryptedSecret = Session::pull('provisioned_secret');

        if (! $encryptedSecret) {
            return redirect()
                ->route('api-management.show', $client->id)
                ->withErrors(['secret' => 'Secret sudah tidak tersedia. Lakukan rotate secret untuk membuat yang baru.']);
        }

        $secret = decrypt($encryptedSecret);

        return view('pages.api-management.provisioned', compact('client', 'secret'));
    }

    public function edit($id)
    {
        $client = ApiClient::findOrFail($id);

        return view('pages.api-management.edit', compact('client'));
    }

    public function update(Request $request, $id)
    {
        $client = ApiClient::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'rate_limit_per_minute' => 'required|integer|min:1|max:10000',
        ]);

        $this->clientService->update($client, $request->only([
            'name', 'description', 'rate_limit_per_minute',
        ]));

        return redirect()
            ->route('api-management.show', $client->id)
            ->with('success', 'Client berhasil diperbarui.');
    }

    public function disable($id)
    {
        $client = ApiClient::findOrFail($id);
        $this->clientService->setStatus($client, ApiClientStatus::Inactive);

        return redirect()->route('api-management.show', $client->id)->with('success', 'Client berhasil dinonaktifkan.');
    }

    public function enable($id)
    {
        $client = ApiClient::findOrFail($id);
        $this->clientService->setStatus($client, ApiClientStatus::Active);

        return redirect()->route('api-management.show', $client->id)->with('success', 'Client berhasil diaktifkan.');
    }

    public function revoke(Request $request, $id)
    {
        $client = ApiClient::findOrFail($id);

        $request->validate([
            'confirmation_code' => ['required', function ($attribute, $value, $fail) use ($client) {
                if (strtoupper($value) !== $client->code) {
                    $fail('Kode konfirmasi tidak sesuai.');
                }
            }],
        ]);

        $this->clientService->setStatus($client, ApiClientStatus::Revoked);

        return redirect()->route('api-management.index')->with('success', 'Client berhasil dicabut permanen.');
    }
}
