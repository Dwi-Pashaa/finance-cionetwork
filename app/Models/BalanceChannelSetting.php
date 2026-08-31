<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceChannelSetting extends Model
{
    protected $fillable = [
        'channel',
        'name',
        'is_active',
        'description',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function isChannelActive(string $channel): bool
    {
        $setting = static::where('channel', strtolower($channel))->first();
        return $setting ? (bool) $setting->is_active : true;
    }

    public static function isManualActive(): bool
    {
        return static::isChannelActive('manual');
    }

    public static function isXenditActive(): bool
    {
        return static::isChannelActive('xendit');
    }
}
