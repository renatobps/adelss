<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPostDestination extends Model
{
    public const DEST_FEED = 'feed';
    public const DEST_REELS = 'reels';
    public const DEST_STORIES = 'stories';

    public const DESTINATIONS = [
        self::DEST_FEED => 'Feed',
        self::DEST_REELS => 'Reels',
        self::DEST_STORIES => 'Stories',
    ];

    public const STATUS_PENDING = 'pendente';
    public const STATUS_PUBLISHING = 'publicando';
    public const STATUS_PUBLISHED = 'publicado';
    public const STATUS_ERROR = 'erro';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_PUBLISHING => 'Publicando',
        self::STATUS_PUBLISHED => 'Publicado',
        self::STATUS_ERROR => 'Erro',
    ];

    protected $fillable = [
        'scheduled_post_id',
        'destination',
        'status',
        'instagram_media_id',
        'error_message',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(ScheduledPost::class, 'scheduled_post_id');
    }

    public function getDestinationLabelAttribute(): string
    {
        return self::DESTINATIONS[$this->destination] ?? $this->destination;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function canRetry(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }
}
