<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleDriveSetting extends Model
{
    protected $fillable = [
        'connected_account_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'root_folder_id',
        'connected_by',
        'connected_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
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
        return filled($this->refresh_token) || filled($this->access_token);
    }
}
