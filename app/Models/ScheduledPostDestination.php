<?php

namespace App\Models;

use Carbon\CarbonInterface;
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

    public const REMOVAL_NONE = 'nao_agendado';
    public const REMOVAL_SCHEDULED = 'agendado';
    public const REMOVAL_DONE = 'removido';
    public const REMOVAL_ERROR = 'erro_remocao';

    public const REMOVAL_STATUSES = [
        self::REMOVAL_NONE => 'Sem remoção automática',
        self::REMOVAL_SCHEDULED => 'Remoção agendada',
        self::REMOVAL_DONE => 'Removido',
        self::REMOVAL_ERROR => 'Erro na remoção',
    ];

    protected $fillable = [
        'scheduled_post_id',
        'destination',
        'status',
        'instagram_media_id',
        'error_message',
        'published_at',
        'remove_after_days',
        'remove_at',
        'removed_at',
        'removal_status',
        'removal_error',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'remove_at' => 'datetime',
        'removed_at' => 'datetime',
        'remove_after_days' => 'integer',
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

    public function isStories(): bool
    {
        return $this->destination === self::DEST_STORIES;
    }

    public function isPermanentDestination(): bool
    {
        return in_array($this->destination, [self::DEST_FEED, self::DEST_REELS], true);
    }

    /**
     * Calcula remove_at / removal_status no momento da publicação bem-sucedida.
     *
     * @return array{remove_at: ?\Carbon\CarbonInterface, removal_status: string, removal_error: null}
     */
    public function removalPayloadForPublished(CarbonInterface $publishedAt): array
    {
        if ($this->isStories()) {
            return [
                'remove_at' => $publishedAt->copy()->addHours(24),
                'removal_status' => self::REMOVAL_SCHEDULED,
                'removal_error' => null,
            ];
        }

        if ($this->isPermanentDestination()
            && $this->remove_after_days !== null
            && (int) $this->remove_after_days > 0) {
            return [
                'remove_at' => $publishedAt->copy()->addDays((int) $this->remove_after_days),
                'removal_status' => self::REMOVAL_SCHEDULED,
                'removal_error' => null,
            ];
        }

        return [
            'remove_at' => null,
            'removal_status' => self::REMOVAL_NONE,
            'removal_error' => null,
        ];
    }

    /** Texto curto para a listagem (por destino). */
    public function removalSummaryLabel(): string
    {
        if ($this->removal_status === self::REMOVAL_DONE && $this->removed_at) {
            return 'Removido em ' . $this->removed_at->format('d/m/Y');
        }

        if ($this->removal_status === self::REMOVAL_ERROR) {
            return 'Falha na remoção automática';
        }

        if ($this->status !== self::STATUS_PUBLISHED) {
            return '';
        }

        if ($this->isStories()) {
            if (!$this->remove_at) {
                return 'expira em 24h';
            }
            if ($this->remove_at->isPast()) {
                return 'expirada (24h)';
            }

            $hours = (int) max(1, ceil($this->remove_at->diffInMinutes(now()) / 60));

            return 'expira em ' . $hours . 'h';
        }

        if ($this->removal_status === self::REMOVAL_SCHEDULED && $this->remove_at) {
            if ($this->remove_at->isPast()) {
                return 'remoção pendente';
            }
            $days = (int) max(1, ceil($this->remove_at->diffInHours(now()) / 24));

            return 'remove em ' . $days . ' ' . ($days === 1 ? 'dia' : 'dias');
        }

        return 'sem remoção automática';
    }
}
