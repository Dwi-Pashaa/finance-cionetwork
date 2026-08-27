<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Support\ApiResponse;

class HealthController extends Controller
{
    public function index()
    {
        return ApiResponse::success('Service is healthy', [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
