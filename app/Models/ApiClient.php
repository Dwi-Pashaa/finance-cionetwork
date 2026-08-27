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
        'description',
        'rate_limit_per_minute',
        'revoked_at',
    ];

    protected $casts = [
        'status' => ApiClientStatus::class,
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
}
