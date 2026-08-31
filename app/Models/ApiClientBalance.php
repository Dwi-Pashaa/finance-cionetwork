<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiClientBalance extends Model
{
    protected $fillable = [
        'api_client_id',
        'balance_manual',
        'balance_xendit',
        'balance',
    ];

    protected $casts = [
        'balance_manual' => 'decimal:2',
        'balance_xendit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }

    /**
     * Hitung dan sinkronkan total saldo dari balance_manual + balance_xendit.
     */
    public function recalculateTotal(): void
    {
        $this->balance = (float) $this->balance_manual + (float) $this->balance_xendit;
    }
}
