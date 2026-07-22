<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledPost extends Model
{
    public const STATUS_SCHEDULED = 'agendado';
    public const STATUS_PUBLISHING = 'publicando';
    public const STATUS_DONE = 'concluido';
    public const STATUS_PARTIAL = 'erro_parcial';
    public const STATUS_ERROR = 'erro';

    /** @deprecated use STATUS_DONE */
    public const STATUS_PUBLISHED = 'concluido';

    public const STATUSES = [
        self::STATUS_SCHEDULED => 'Agendado',
        self::STATUS_PUBLISHING => 'Publicando',
        self::STATUS_DONE => 'Concluído',
        self::STATUS_PARTIAL => 'Erro parcial',
        self::STATUS_ERROR => 'Erro',
    ];

    public const KIND_PHOTO = 'foto';
    public const KIND_VIDEO = 'video';

    protected $fillable = [
        'media_file_id',
        'image_path',
        'caption',
        'media_kind',
        'scheduled_for',
        'status',
        'created_by',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
    ];

    public function mediaFile(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(ScheduledPostDestination::class, 'scheduled_post_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isVideo(): bool
    {
        return $this->media_kind === self::KIND_VIDEO;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [
            self::STATUS_SCHEDULED,
            self::STATUS_ERROR,
            self::STATUS_PARTIAL,
        ], true);
    }

    public function recalculateStatus(): string
    {
        $destinations = $this->destinations()->get();
        if ($destinations->isEmpty()) {
            $this->update(['status' => self::STATUS_SCHEDULED]);

            return self::STATUS_SCHEDULED;
        }

        $published = $destinations->where('status', ScheduledPostDestination::STATUS_PUBLISHED)->count();
        $errors = $destinations->where('status', ScheduledPostDestination::STATUS_ERROR)->count();
        $publishing = $destinations->where('status', ScheduledPostDestination::STATUS_PUBLISHING)->count();
        $pending = $destinations->where('status', ScheduledPostDestination::STATUS_PENDING)->count();
        $total = $destinations->count();

        if ($publishing > 0 || ($pending > 0 && $published + $errors > 0 && $published + $errors < $total)) {
            // ainda há trabalho em andamento
            if ($publishing > 0) {
                $status = self::STATUS_PUBLISHING;
            } elseif ($pending > 0 && $published === 0 && $errors === 0) {
                $status = self::STATUS_SCHEDULED;
            } else {
                $status = self::STATUS_PUBLISHING;
            }
        } elseif ($published === $total) {
            $status = self::STATUS_DONE;
        } elseif ($errors === $total) {
            $status = self::STATUS_ERROR;
        } elseif ($published > 0 && $errors > 0 && $pending === 0) {
            $status = self::STATUS_PARTIAL;
        } elseif ($pending === $total) {
            $status = self::STATUS_SCHEDULED;
        } else {
            $status = self::STATUS_PUBLISHING;
        }

        $this->update(['status' => $status]);

        return $status;
    }
}
