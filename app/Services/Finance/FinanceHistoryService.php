<?php

namespace App\Services\Finance;

use App\Models\ApiClient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity;

class FinanceHistoryService
{
    public function createFromApi(ApiClient $client, array $data): Activity
    {
        $properties = array_merge($data['properties'] ?? [], [
            'api_client_id' => $client->id,
            'client_id' => $client->client_id,
            'client_code' => $client->code,
            'subject_type' => $data['subject_type'],
            'subject_external_id' => $data['subject_external_id'] ?? null,
            'request_id' => request()->attributes->get('request_id'),
        ]);

        return activity('external_finance')
            ->event($data['event'])
            ->withProperties($properties)
            ->log($data['description']);
    }

    public function paginateForClient(ApiClient $client, array $filters = []): LengthAwarePaginator
    {
        return Activity::query()
            ->where('log_name', 'external_finance')
            ->where('properties->api_client_id', $client->id)
            ->when(Arr::get($filters, 'event'), fn ($query, $event) => $query->where('event', $event))
            ->when(Arr::get($filters, 'subject_type'), fn ($query, $type) => $query->where('properties->subject_type', $type))
            ->when(Arr::get($filters, 'date_from'), fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when(Arr::get($filters, 'date_to'), fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
