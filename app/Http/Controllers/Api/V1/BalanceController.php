<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Support\ApiResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $client = $request->attributes->get('api_client');
        $balance = $client->balance()->first();

        return ApiResponse::success('Balance retrieved successfully', [
            'client_code' => $client->code,
            'client_name' => $client->name,
            'balance' => $balance ? (string) $balance->balance : '0.00',
            'retrieved_at' => now()->toIso8601String(),
        ]);
    }
}
