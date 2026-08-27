<?php

namespace App\Models;

use App\Enums\ApiCredentialStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiCredential extends Model
{
    protected $fillable = [
        'api_client_id',
        'key_id',
        'secret_hash',
        'secret_encrypted',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'status' => ApiCredentialStatus::class,
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== ApiCredentialStatus::Active) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
