<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Finance\AnalyticsService;
use App\Services\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * GET /api/v1/analytics/overview
     * Ringkasan Eksekutif & Saldo Konsolidasi Multi-Web + Xendit
     */
    public function overview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_code' => ['nullable', 'string', 'max:50'],
            'refresh'     => ['nullable', 'boolean'],
        ]);

        $clientCode = $validated['client_code'] ?? null;
        $useCache = ! ($request->boolean('refresh'));

        $data = $this->analyticsService->getOverview($clientCode, $useCache);

        return ApiResponse::success('Overview analytics retrieved successfully', $data);
    }

    /**
     * GET /api/v1/analytics/chart
     * Dataset Time-Series Grafik Multi-Web + Xendit
     */
    public function chart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'range'       => ['nullable', 'string', 'in:7d,30d,90d,ytd,1y'],
            'interval'    => ['nullable', 'string', 'in:daily,weekly,monthly'],
            'client_code' => ['nullable', 'string', 'max:50'],
            'refresh'     => ['nullable', 'boolean'],
        ]);

        $range = $validated['range'] ?? '7d';
        $interval = $validated['interval'] ?? 'daily';
        $clientCode = $validated['client_code'] ?? null;
        $useCache = ! ($request->boolean('refresh'));

        $data = $this->analyticsService->getChart($range, $interval, $clientCode, $useCache);

        return ApiResponse::success('Chart time-series retrieved successfully', $data);
    }

    /**
     * GET /api/v1/analytics/growth
     * Indikator Pertumbuhan & Valuasi Bisnis
     */
    public function growth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_code' => ['nullable', 'string', 'max:50'],
            'refresh'     => ['nullable', 'boolean'],
        ]);

        $clientCode = $validated['client_code'] ?? null;
        $useCache = ! ($request->boolean('refresh'));

        $data = $this->analyticsService->getGrowth($clientCode, $useCache);

        return ApiResponse::success('Company growth metrics calculated successfully', $data);
    }
}
