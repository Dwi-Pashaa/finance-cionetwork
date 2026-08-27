<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonceCache extends Model
{
    public $timestamps = false;

    protected $table = 'nonce_cache';

    protected $fillable = [
        'client_id',
        'nonce',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
