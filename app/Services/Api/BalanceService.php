<?php

namespace App\Services\Api;

use App\Models\ApiClient;
use App\Models\BalanceAdjustment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BalanceService
{
    public function adjust(ApiClient $client, string $type, float $amount, string $reason, ?int $userId = null): BalanceAdjustment
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Jumlah penyesuaian harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($client, $type, $amount, $reason, $userId) {
            $balance = $client->balance()->lockForUpdate()->first();

            if (! $balance) {
                throw new InvalidArgumentException('Data saldo client tidak ditemukan.');
            }

            $before = (float) $balance->balance;
            $after = match ($type) {
                'adjust_in' => $before + $amount,
                'adjust_out' => $before - $amount,
                default => throw new InvalidArgumentException('Tipe penyesuaian tidak valid.'),
            };

            if ($after < 0) {
                throw new InvalidArgumentException(
                    'Saldo tidak boleh negatif. Saldo saat ini: Rp '.number_format($before, 0, ',', '.')
                );
            }

            $balance->update(['balance' => $after]);

            return BalanceAdjustment::create([
                'api_client_id' => $client->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reason' => $reason,
                'adjusted_by' => $userId,
            ]);
        });
    }
}
