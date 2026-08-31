<?php

namespace App\Services\Api;

use App\Models\ApiClient;
use App\Models\ApiClientBalance;
use App\Models\BalanceAdjustment;
use App\Models\BalanceChannelSetting;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BalanceService
{
    /**
     * Penyesuaian saldo client (Manual atau Xendit).
     *
     * @param  ApiClient   $client
     * @param  string      $type         'adjust_in' | 'adjust_out'
     * @param  float       $amount
     * @param  string      $reason
     * @param  int|null    $userId
     * @param  string      $balanceType  'manual' | 'xendit'
     * @param  string      $source       'manual' | 'xendit' | 'admin_adjustment'
     * @return BalanceAdjustment
     */
    public function adjust(
        ApiClient $client,
        string $type,
        float $amount,
        string $reason,
        ?int $userId = null,
        string $balanceType = 'manual',
        string $source = 'manual'
    ): BalanceAdjustment {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Jumlah penyesuaian harus lebih besar dari nol.');
        }

        $balanceType = strtolower($balanceType) === 'xendit' ? 'xendit' : 'manual';

        // Validasi status channel jika penambahan saldo (adjust_in)
        if ($type === 'adjust_in') {
            if ($balanceType === 'manual' && ! $client->isManualBalanceEnabled()) {
                throw new InvalidArgumentException("Jalur penambahan Saldo Manual untuk client {$client->name} saat ini sedang dinonaktifkan (OFF).");
            }
            if ($balanceType === 'xendit' && ! $client->isXenditBalanceEnabled()) {
                throw new InvalidArgumentException("Jalur penambahan Saldo Xendit untuk client {$client->name} saat ini sedang dinonaktifkan (OFF).");
            }
        }

        return DB::transaction(function () use ($client, $type, $amount, $reason, $userId, $balanceType, $source) {
            $balance = $client->balance()->lockForUpdate()->first();

            if (! $balance) {
                $balance = ApiClientBalance::create([
                    'api_client_id' => $client->id,
                    'balance_manual' => 0,
                    'balance_xendit' => 0,
                    'balance' => 0,
                ]);
            }

            $pocketField = $balanceType === 'xendit' ? 'balance_xendit' : 'balance_manual';
            $beforePocket = (float) $balance->{$pocketField};

            $afterPocket = match ($type) {
                'adjust_in' => $beforePocket + $amount,
                'adjust_out' => $beforePocket - $amount,
                default => throw new InvalidArgumentException('Tipe penyesuaian tidak valid.'),
            };

            if ($afterPocket < 0) {
                $pocketName = $balanceType === 'xendit' ? 'Saldo Xendit' : 'Saldo Manual';
                throw new InvalidArgumentException(
                    "{$pocketName} tidak mencukupi untuk pengurangan ini. Sisa {$pocketName}: Rp " . number_format($beforePocket, 0, ',', '.')
                );
            }

            $balance->{$pocketField} = $afterPocket;
            $balance->recalculateTotal();
            $balance->save();

            return BalanceAdjustment::create([
                'api_client_id' => $client->id,
                'type' => $type,
                'balance_type' => $balanceType,
                'source' => $source,
                'amount' => $amount,
                'balance_before' => $beforePocket,
                'balance_after' => $afterPocket,
                'reason' => $reason,
                'adjusted_by' => $userId,
            ]);
        });
    }

    /**
     * Top-up otomatis via Xendit webhook setelah pembayaran berhasil.
     */
    public function topupViaXendit(
        ApiClient $client,
        float $amount,
        string $invoiceId,
        ?string $paymentMethod = null,
        ?string $referenceId = null,
        ?string $description = null
    ): BalanceAdjustment {
        if (! $client->isXenditBalanceEnabled()) {
            throw new InvalidArgumentException("Jalur penambahan Saldo Xendit untuk client {$client->name} sedang dinonaktifkan (OFF).");
        }

        return DB::transaction(function () use ($client, $amount, $invoiceId, $paymentMethod, $referenceId, $description) {
            $balance = $client->balance()->lockForUpdate()->first();

            if (! $balance) {
                $balance = ApiClientBalance::create([
                    'api_client_id' => $client->id,
                    'balance_manual' => 0,
                    'balance_xendit' => 0,
                    'balance' => 0,
                ]);
            }

            $before = (float) $balance->balance_xendit;
            $after = $before + $amount;

            $balance->balance_xendit = $after;
            $balance->recalculateTotal();
            $balance->save();

            $desc = $description ?? "Top Up Saldo via Xendit Invoice {$invoiceId} (" . ($paymentMethod ?? 'Gateway') . ")";

            $adj = BalanceAdjustment::create([
                'api_client_id' => $client->id,
                'type' => 'adjust_in',
                'balance_type' => 'xendit',
                'source' => 'xendit',
                'reference_id' => $referenceId,
                'xendit_invoice_id' => $invoiceId,
                'payment_status' => 'completed',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reason' => $desc,
                'adjusted_by' => null,
            ]);

            activity('external_finance')
                ->event('xendit_topup')
                ->withProperties([
                    'api_client_id' => $client->id,
                    'client_id' => $client->client_id,
                    'client_code' => $client->code,
                    'client_name' => $client->name,
                    'subject_type' => 'Income',
                    'subject_external_id' => $invoiceId,
                    'amount' => $amount,
                    'balance_type' => 'xendit',
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'total_balance' => $balance->balance,
                    'reference_id' => $referenceId,
                    'payment_method' => $paymentMethod,
                ])
                ->log($desc);

            return $adj;
        });
    }
}
