<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Support\ApiResponse;
use Illuminate\Http\Request;

class PingController extends Controller
{
    public function index(Request $request)
    {
        $client = $request->attributes->get('api_client');

        return ApiResponse::success('Pong', [
            'client_code' => $client->code,
            'server_time' => now()->getTimestamp(),
        ]);
    }
}
