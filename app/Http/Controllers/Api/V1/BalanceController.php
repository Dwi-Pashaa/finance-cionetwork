<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiClientBalance;
use App\Services\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Models\Activity;

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

    public function deduct(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reference_id' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $client = $request->attributes->get('api_client');
        $amount = (float) $validated['amount'];

        $result = DB::transaction(function () use ($client, $amount, $validated) {
            $balanceRecord = ApiClientBalance::where('api_client_id', $client->id)
                ->lockForUpdate()
                ->first();

            if (! $balanceRecord) {
                $balanceRecord = ApiClientBalance::create([
                    'api_client_id' => $client->id,
                    'balance' => 0,
                ]);
            }

            $currentBalance = (float) $balanceRecord->balance;

            if ($currentBalance < $amount) {
                return [
                    'success' => false,
                    'current_balance' => $currentBalance,
                ];
            }

            $newBalance = $currentBalance - $amount;
            $balanceRecord->balance = $newBalance;
            $balanceRecord->save();

            activity('external_finance')
                ->event('deduct_balance')
                ->withProperties([
                    'api_client_id' => $client->id,
                    'client_id' => $client->client_id,
                    'client_code' => $client->code,
                    'client_name' => $client->name,
                    'subject_type' => 'Expense',
                    'subject_external_id' => $validated['reference_id'] ?? null,
                    'amount' => $amount,
                    'previous_balance' => $currentBalance,
                    'current_balance' => $newBalance,
                    'reference_id' => $validated['reference_id'] ?? null,
                    'category' => $validated['category'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'request_id' => request()->attributes->get('request_id'),
                ])
                ->log($validated['description']);

            return [
                'success' => true,
                'current_balance' => $newBalance,
            ];
        });

        if (! $result['success']) {
            $formattedBalance = number_format($result['current_balance'], 0, ',', '.');
            return ApiResponse::error(
                "Saldo tidak mencukupi. Sisa saldo: Rp {$formattedBalance}",
                'INSUFFICIENT_BALANCE',
                400,
                [
                    'current_balance' => $result['current_balance'],
                    'required_amount' => $amount,
                ]
            );
        }

        return ApiResponse::success('Saldo berhasil dipotong', [
            'client_code' => $client->code,
            'amount_deducted' => $amount,
            'current_balance' => $result['current_balance'],
            'reference_id' => $validated['reference_id'] ?? null,
            'transaction_at' => now()->toIso8601String(),
        ], 200);
    }

    public function refund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference_id' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'amount.required' => 'Amount wajib diisi',
            'amount.numeric' => 'Amount harus berupa angka',
            'amount.gt' => 'Amount harus lebih dari 0',
            'reference_id.required' => 'Reference ID wajib diisi',
            'description.required' => 'Description wajib diisi',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR',
                'request_id' => $request->attributes->get('request_id'),
            ], 422);
        }

        $validated = $validator->validated();

        // Cek duplikasi reference_id di riwayat transaksi / activity_log
        $isDuplicate = Activity::where('log_name', 'external_finance')
            ->where(function ($query) use ($validated) {
                $query->where('properties->reference_id', $validated['reference_id'])
                    ->orWhere('properties->subject_external_id', $validated['reference_id']);
            })
            ->exists();

        if ($isDuplicate) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => [
                    'reference_id' => ['Reference ID sudah digunakan'],
                ],
                'error_code' => 'VALIDATION_ERROR',
                'request_id' => $request->attributes->get('request_id'),
            ], 422);
        }

        $client = $request->attributes->get('api_client');
        $amount = (float) $validated['amount'];

        $result = DB::transaction(function () use ($client, $amount, $validated) {
            $balanceRecord = ApiClientBalance::where('api_client_id', $client->id)
                ->lockForUpdate()
                ->first();

            if (! $balanceRecord) {
                $balanceRecord = ApiClientBalance::create([
                    'api_client_id' => $client->id,
                    'balance' => 0,
                ]);
            }

            $currentBalance = (float) $balanceRecord->balance;
            $newBalance = $currentBalance + $amount;

            $balanceRecord->balance = $newBalance;
            $balanceRecord->save();

            activity('external_finance')
                ->event('refund_balance')
                ->withProperties([
                    'api_client_id' => $client->id,
                    'client_id' => $client->client_id,
                    'client_code' => $client->code,
                    'client_name' => $client->name,
                    'subject_type' => 'Income',
                    'subject_external_id' => $validated['reference_id'],
                    'amount' => $amount,
                    'previous_balance' => $currentBalance,
                    'current_balance' => $newBalance,
                    'reference_id' => $validated['reference_id'],
                    'reason' => $validated['reason'] ?? null,
                    'request_id' => request()->attributes->get('request_id'),
                ])
                ->log($validated['description']);

            return [
                'current_balance' => $newBalance,
            ];
        });

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Saldo berhasil dikembalikan',
            'data' => [
                'reference_id' => $validated['reference_id'],
                'amount' => $amount,
                'balance_after' => $result['current_balance'],
                'refunded_at' => now()->toIso8601String(),
            ],
            'request_id' => $request->attributes->get('request_id'),
        ], 200);
    }
}


