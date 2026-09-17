<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Services\Finance\FinanceHistoryService;
use App\Services\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceHistoryController extends Controller
{
    public function __construct(
        private FinanceHistoryService $historyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'event'        => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', 'max:100'],
            'client_code'  => ['nullable', 'string', 'max:100'],
            'balance_type' => ['nullable', 'string', 'in:manual,xendit,auto'],
            'search'       => ['nullable', 'string', 'max:150'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date'],
            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
        ]);

        /** @var ApiClient $client */
        $client = $request->attributes->get('api_client');
        $history = $this->historyService->paginateForClient($client, $request->only([
            'event',
            'subject_type',
            'client_code',
            'balance_type',
            'search',
            'start_date',
            'end_date',
            'date_from',
            'date_to',
            'per_page',
        ]));

        $items = $history->getCollection()->map(function ($activity) {
            $rawProperties = $activity->properties?->toArray() ?? [];

            // Bersihkan properties dari atribut standar level atas
            $extraProperties = array_diff_key($rawProperties, array_flip([
                'api_client_id',
                'client_id',
                'client_code',
                'client_name',
                'subject_type',
                'subject_external_id',
                'amount',
                'balance_type',
                'request_id',
            ]));

            $sourceCode = $activity->getExtraProperty('client_code')
                ?? ($activity->causer instanceof ApiClient ? $activity->causer->code : null)
                ?? 'CIO_FINANCE';

            $sourceName = $activity->getExtraProperty('client_name')
                ?? ($activity->causer instanceof ApiClient ? $activity->causer->name : null)
                ?? 'CIO Finance';

            $subjectType = $activity->getExtraProperty('subject_type')
                ?? ($activity->subject_type ? class_basename($activity->subject_type) : null);

            $subjectExternalId = $activity->getExtraProperty('subject_external_id')
                ?? $activity->getExtraProperty('reference_id');

            $amount = (float) ($activity->getExtraProperty('amount') ?? 0);
            $balanceType = $activity->getExtraProperty('balance_type') ?? 'manual';

            return [
                'id'                  => $activity->id,
                'source_client_code'  => $sourceCode,
                'source_client_name'  => $sourceName,
                'event'               => $activity->event ?? 'created',
                'subject_type'        => $subjectType,
                'subject_external_id' => $subjectExternalId,
                'amount'              => $amount,
                'balance_type'        => $balanceType,
                'description'         => $activity->description,
                'properties'          => ! empty($extraProperties) ? (object) $extraProperties : (object) [],
                'created_at'          => $activity->created_at?->timezone('Asia/Jakarta')->toIso8601String(),
            ];
        })->values();

        return ApiResponse::success('History retrieved successfully', [
            'items'      => $items,
            'pagination' => [
                'current_page' => $history->currentPage(),
                'last_page'    => $history->lastPage(),
                'per_page'     => $history->perPage(),
                'total'        => $history->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event'               => ['required', 'string', 'max:100'],
            'subject_type'        => ['required', 'string', 'max:100'],
            'subject_external_id' => ['nullable', 'string', 'max:150'],
            'description'         => ['required', 'string', 'max:255'],
            'properties'          => ['nullable', 'array'],
        ]);

        /** @var ApiClient $client */
        $client = $request->attributes->get('api_client');
        $activity = $this->historyService->createFromApi($client, $validated);

        return ApiResponse::success('History created successfully', [
            'id'          => $activity->id,
            'event'       => $activity->event,
            'description' => $activity->description,
            'created_at'  => $activity->created_at?->timezone('Asia/Jakarta')->toIso8601String(),
        ], 201);
    }
}
