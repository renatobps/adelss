<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPost extends Model
{
    public const STATUS_SCHEDULED = 'agendado';
    public const STATUS_PUBLISHING = 'publicando';
    public const STATUS_PUBLISHED = 'publicado';
    public const STATUS_ERROR = 'erro';

    public const STATUSES = [
        self::STATUS_SCHEDULED => 'Agendado',
        self::STATUS_PUBLISHING => 'Publicando',
        self::STATUS_PUBLISHED => 'Publicado',
        self::STATUS_ERROR => 'Erro',
    ];

    protected $fillable = [
        'media_file_id',
        'image_path',
        'caption',
        'scheduled_for',
        'status',
        'instagram_media_id',
        'error_message',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_ERROR], true);
    }

    public function canRetry(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }
}
