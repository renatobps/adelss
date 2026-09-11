<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleReminderLog extends Model
{
    public const TYPE_MONTH = 'month';
    public const TYPE_WEEK = 'week';
    public const TYPE_DAY = 'day';

    protected $fillable = [
        'type',
        'period_key',
        'volunteer_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public static function alreadySent(string $type, string $periodKey, int $volunteerId): bool
    {
        return static::query()
            ->where('type', $type)
            ->where('period_key', $periodKey)
            ->where('volunteer_id', $volunteerId)
            ->exists();
    }

    public static function markSent(string $type, string $periodKey, int $volunteerId): void
    {
        static::query()->firstOrCreate(
            [
                'type' => $type,
                'period_key' => $periodKey,
                'volunteer_id' => $volunteerId,
            ],
            [
                'sent_at' => now(),
            ]
        );
    }
}
