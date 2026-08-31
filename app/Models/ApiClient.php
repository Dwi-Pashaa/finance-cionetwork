<?php

namespace App\Models;

use App\Enums\ApiClientStatus;
use App\Enums\ApiCredentialStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApiClient extends Model
{
    protected $fillable = [
        'name',
        'code',
        'client_id',
        'status',
        'is_manual_balance_enabled',
        'is_xendit_balance_enabled',
        'description',
        'rate_limit_per_minute',
        'revoked_at',
    ];

    protected $casts = [
        'status' => ApiClientStatus::class,
        'is_manual_balance_enabled' => 'boolean',
        'is_xendit_balance_enabled' => 'boolean',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function credentials(): HasMany
    {
        return $this->hasMany(ApiCredential::class);
    }

    public function activeCredentials(): HasMany
    {
        return $this->hasMany(ApiCredential::class)->where('status', ApiCredentialStatus::Active->value);
    }

    public function balance(): HasOne
    {
        return $this->hasOne(ApiClientBalance::class);
    }

    public function isActive(): bool
    {
        return $this->status === ApiClientStatus::Active;
    }

    public function isManualBalanceEnabled(): bool
    {
        return (bool) ($this->is_manual_balance_enabled ?? true);
    }

    public function isXenditBalanceEnabled(): bool
    {
        return (bool) ($this->is_xendit_balance_enabled ?? true);
    }
}
