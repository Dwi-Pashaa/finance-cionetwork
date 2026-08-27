<?php

namespace App\Services\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?: null,
            'request_id' => request()->attributes->get('request_id'),
        ], $status);
    }

    public static function error(string $message, string $errorCode, int $status, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'request_id' => request()->attributes->get('request_id'),
        ] + ($data ? ['errors' => $data] : []), $status);
    }
}
