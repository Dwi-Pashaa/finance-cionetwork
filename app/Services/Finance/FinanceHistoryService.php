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
            'api_client_id'       => $client->id,
            'client_id'           => $client->client_id,
            'client_code'         => $client->code,
            'client_name'         => $client->name,
            'subject_type'        => $data['subject_type'],
            'subject_external_id' => $data['subject_external_id'] ?? null,
            'amount'              => isset($data['properties']['amount']) ? (float) $data['properties']['amount'] : null,
            'balance_type'        => $data['properties']['balance_type'] ?? 'manual',
            'request_id'          => request()->attributes->get('request_id'),
        ]);

        return activity('external_finance')
            ->event($data['event'])
            ->withProperties($properties)
            ->log($data['description']);
    }

    public function paginateForClient(ApiClient $client, array $filters = []): LengthAwarePaginator
    {
        $clientCode = Arr::get($filters, 'client_code');
        $isAllClients = strtoupper((string) $clientCode) === 'ALL';

        $startDate = Arr::get($filters, 'start_date') ?: Arr::get($filters, 'date_from');
        $endDate = Arr::get($filters, 'end_date') ?: Arr::get($filters, 'date_to');
        $search = Arr::get($filters, 'search');
        $balanceType = Arr::get($filters, 'balance_type');
        $subjectType = Arr::get($filters, 'subject_type');
        $event = Arr::get($filters, 'event');

        return Activity::query()
            ->whereIn('log_name', ['external_finance', 'finance'])
            ->when(! $isAllClients && ! empty($clientCode), function ($query) use ($clientCode) {
                $query->where('properties->client_code', $clientCode);
            })
            ->when(! $isAllClients && empty($clientCode), function ($query) use ($client) {
                $query->where(function ($q) use ($client) {
                    $q->where('properties->api_client_id', $client->id)
                      ->orWhere('properties->client_code', $client->code);
                });
            })
            ->when($event, fn ($query, $e) => $query->where('event', $e))
            ->when($subjectType, function ($query, $type) {
                $query->where(function ($q) use ($type) {
                    $q->where('properties->subject_type', $type)
                      ->orWhere('properties->subject_type', ucfirst(strtolower($type)));
                });
            })
            ->when($balanceType, fn ($query, $bType) => $query->where('properties->balance_type', strtolower($bType)))
            ->when($search, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('description', 'like', "%{$keyword}%")
                      ->orWhere('properties->subject_external_id', 'like', "%{$keyword}%")
                      ->orWhere('properties->reference_id', 'like', "%{$keyword}%");
                });
            })
            ->when($startDate, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($endDate, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
