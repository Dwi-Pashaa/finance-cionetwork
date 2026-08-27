<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceHistoryService;
use App\Services\Support\ApiResponse;
use Illuminate\Http\Request;

class FinanceHistoryController extends Controller
{
    public function __construct(private FinanceHistoryService $historyService) {}

    public function index(Request $request)
    {
        $request->validate([
            'event' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $client = $request->attributes->get('api_client');
        $history = $this->historyService->paginateForClient($client, $request->only([
            'event', 'subject_type', 'date_from', 'date_to', 'per_page',
        ]));

        return ApiResponse::success('History retrieved successfully', [
            'items' => $history->getCollection()->map(fn ($activity) => [
                'id' => $activity->id,
                'event' => $activity->event,
                'description' => $activity->description,
                'subject_type' => $activity->getExtraProperty('subject_type'),
                'subject_external_id' => $activity->getExtraProperty('subject_external_id'),
                'properties' => $activity->properties,
                'created_at' => $activity->created_at?->toIso8601String(),
            ])->values(),
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event' => ['required', 'string', 'max:100'],
            'subject_type' => ['required', 'string', 'max:100'],
            'subject_external_id' => ['nullable', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:255'],
            'properties' => ['nullable', 'array'],
        ]);

        $client = $request->attributes->get('api_client');
        $activity = $this->historyService->createFromApi($client, $validated);

        return ApiResponse::success('History created successfully', [
            'id' => $activity->id,
            'event' => $activity->event,
            'description' => $activity->description,
            'created_at' => $activity->created_at?->toIso8601String(),
        ], 201);
    }
}
