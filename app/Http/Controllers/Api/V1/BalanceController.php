<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiClientBalance;
use App\Models\BalanceChannelSetting;
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

        $manualBalance = $balance ? (float) $balance->balance_manual : 0.0;
        $xenditBalance = $balance ? (float) $balance->balance_xendit : 0.0;
        $totalBalance  = $balance ? (float) $balance->balance : ($manualBalance + $xenditBalance);

        return ApiResponse::success('Balance retrieved successfully', [
            'client_code' => $client->code,
            'client_name' => $client->name,
            'balance' => number_format($totalBalance, 2, '.', ''),
            'balance_manual' => number_format($manualBalance, 2, '.', ''),
            'balance_xendit' => number_format($xenditBalance, 2, '.', ''),
            'total_balance' => number_format($totalBalance, 2, '.', ''),
            'channel_status' => [
                'manual' => $client->isManualBalanceEnabled(),
                'xendit' => $client->isXenditBalanceEnabled(),
            ],
            'retrieved_at' => now()->toIso8601String(),
        ]);
    }

    public function deduct(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'balance_type' => ['nullable', 'string', 'in:manual,xendit,auto'],
            'reference_id' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $client = $request->attributes->get('api_client');
        $amount = (float) $validated['amount'];
        $balanceType = strtolower($validated['balance_type'] ?? 'manual');

        $result = DB::transaction(function () use ($client, $amount, $balanceType, $validated) {
            $balanceRecord = ApiClientBalance::where('api_client_id', $client->id)
                ->lockForUpdate()
                ->first();

            if (! $balanceRecord) {
                $balanceRecord = ApiClientBalance::create([
                    'api_client_id' => $client->id,
                    'balance_manual' => 0,
                    'balance_xendit' => 0,
                    'balance' => 0,
                ]);
            }

            $currentManual = (float) $balanceRecord->balance_manual;
            $currentXendit = (float) $balanceRecord->balance_xendit;
            $currentTotal  = (float) $balanceRecord->balance;

            // Pemotongan berdasarkan tipe saldo yang diminta
            if ($balanceType === 'xendit') {
                if ($currentXendit < $amount) {
                    return [
                        'success' => false,
                        'message' => 'Saldo Xendit tidak mencukupi. Sisa saldo Xendit: Rp ' . number_format($currentXendit, 0, ',', '.'),
                        'error_code' => 'INSUFFICIENT_XENDIT_BALANCE',
                        'current_balance' => $currentXendit,
                    ];
                }
                $balanceRecord->balance_xendit = $currentXendit - $amount;
            } elseif ($balanceType === 'auto') {
                // Auto: potong dari Xendit dahulu, sisanya dari Manual
                if ($currentTotal < $amount) {
                    return [
                        'success' => false,
                        'message' => 'Total saldo tidak mencukupi. Sisa total saldo: Rp ' . number_format($currentTotal, 0, ',', '.'),
                        'error_code' => 'INSUFFICIENT_TOTAL_BALANCE',
                        'current_balance' => $currentTotal,
                    ];
                }
                if ($currentXendit >= $amount) {
                    $balanceRecord->balance_xendit = $currentXendit - $amount;
                } else {
                    $remainder = $amount - $currentXendit;
                    $balanceRecord->balance_xendit = 0;
                    $balanceRecord->balance_manual = $currentManual - $remainder;
                }
            } else {
                // Default: Saldo Manual
                if ($currentManual < $amount) {
                    return [
                        'success' => false,
                        'message' => 'Saldo Manual tidak mencukupi. Sisa saldo Manual: Rp ' . number_format($currentManual, 0, ',', '.'),
                        'error_code' => 'INSUFFICIENT_MANUAL_BALANCE',
                        'current_balance' => $currentManual,
                    ];
                }
                $balanceRecord->balance_manual = $currentManual - $amount;
            }

            $balanceRecord->recalculateTotal();
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
                    'balance_type' => $balanceType,
                    'previous_manual' => $currentManual,
                    'previous_xendit' => $currentXendit,
                    'previous_balance' => $currentTotal,
                    'current_manual' => (float) $balanceRecord->balance_manual,
                    'current_xendit' => (float) $balanceRecord->balance_xendit,
                    'current_balance' => (float) $balanceRecord->balance,
                    'reference_id' => $validated['reference_id'] ?? null,
                    'category' => $validated['category'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'request_id' => request()->attributes->get('request_id'),
                ])
                ->log($validated['description']);

            return [
                'success' => true,
                'balance_type' => $balanceType,
                'balance_manual' => (float) $balanceRecord->balance_manual,
                'balance_xendit' => (float) $balanceRecord->balance_xendit,
                'current_balance' => (float) $balanceRecord->balance,
            ];
        });

        if (! $result['success']) {
            return ApiResponse::error(
                $result['message'],
                $result['error_code'],
                400,
                [
                    'current_balance' => $result['current_balance'],
                    'required_amount' => $amount,
                ]
            );
        }

        return ApiResponse::success('Saldo berhasil dipotong', [
            'client_code' => $client->code,
            'balance_type' => $result['balance_type'],
            'amount_deducted' => $amount,
            'balance_manual' => number_format($result['balance_manual'], 2, '.', ''),
            'balance_xendit' => number_format($result['balance_xendit'], 2, '.', ''),
            'current_balance' => number_format($result['current_balance'], 2, '.', ''),
            'reference_id' => $validated['reference_id'] ?? null,
            'transaction_at' => now()->toIso8601String(),
        ], 200);
    }

    public function refund(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'gt:0'],
            'balance_type' => ['nullable', 'string', 'in:manual,xendit'],
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
        $balanceType = strtolower($validated['balance_type'] ?? 'manual');

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

        $result = DB::transaction(function () use ($client, $amount, $balanceType, $validated) {
            $balanceRecord = ApiClientBalance::where('api_client_id', $client->id)
                ->lockForUpdate()
                ->first();

            if (! $balanceRecord) {
                $balanceRecord = ApiClientBalance::create([
                    'api_client_id' => $client->id,
                    'balance_manual' => 0,
                    'balance_xendit' => 0,
                    'balance' => 0,
                ]);
            }

            $currentManual = (float) $balanceRecord->balance_manual;
            $currentXendit = (float) $balanceRecord->balance_xendit;
            $currentTotal  = (float) $balanceRecord->balance;

            if ($balanceType === 'xendit') {
                $balanceRecord->balance_xendit = $currentXendit + $amount;
            } else {
                $balanceRecord->balance_manual = $currentManual + $amount;
            }

            $balanceRecord->recalculateTotal();
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
                    'balance_type' => $balanceType,
                    'previous_manual' => $currentManual,
                    'previous_xendit' => $currentXendit,
                    'previous_balance' => $currentTotal,
                    'current_manual' => (float) $balanceRecord->balance_manual,
                    'current_xendit' => (float) $balanceRecord->balance_xendit,
                    'current_balance' => (float) $balanceRecord->balance,
                    'reference_id' => $validated['reference_id'],
                    'reason' => $validated['reason'] ?? null,
                    'request_id' => request()->attributes->get('request_id'),
                ])
                ->log($validated['description']);

            return [
                'balance_type' => $balanceType,
                'balance_manual' => (float) $balanceRecord->balance_manual,
                'balance_xendit' => (float) $balanceRecord->balance_xendit,
                'current_balance' => (float) $balanceRecord->balance,
            ];
        });

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Saldo berhasil dikembalikan',
            'data' => [
                'reference_id' => $validated['reference_id'],
                'balance_type' => $result['balance_type'],
                'amount' => $amount,
                'balance_manual' => number_format($result['balance_manual'], 2, '.', ''),
                'balance_xendit' => number_format($result['balance_xendit'], 2, '.', ''),
                'balance_after' => number_format($result['current_balance'], 2, '.', ''),
                'refunded_at' => now()->toIso8601String(),
            ],
            'request_id' => $request->attributes->get('request_id'),
        ], 200);
    }
}
