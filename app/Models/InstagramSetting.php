<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramSetting extends Model
{
    protected $fillable = [
        'instagram_business_account_id',
        'facebook_page_id',
        'access_token',
        'token_expires_at',
        'connected_by',
        'connected_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
    ];

    public function connectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public static function current(): self
    {
        return static::firstOrCreate([]);
    }

    public function isConnected(): bool
    {
        return filled($this->access_token) && filled($this->instagram_business_account_id);
    }

    public function isTokenExpiringSoon(int $days = 7): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->lte(now()->addDays($days));
    }
}
