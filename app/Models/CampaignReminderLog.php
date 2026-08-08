<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Histórico de lembretes: dá à liderança rastreabilidade sobre quem já foi
 * cobrado, quantas vezes, e por que alguém deixou de receber.
 */
class CampaignReminderLog extends Model
{
    public const TYPE_ATRASO = 'atraso';
    public const TYPE_CORTESIA = 'cortesia';
    public const TYPE_TESTE = 'teste';

    public const STATUS_ENVIADO = 'enviado';
    public const STATUS_FALHOU = 'falhou';
    public const STATUS_PULADO = 'pulado';

    public const TRIGGER_AUTOMATICO = 'automatico';
    public const TRIGGER_MANUAL = 'manual';

    public const TYPE_LABELS = [
        self::TYPE_ATRASO => 'Atraso',
        self::TYPE_CORTESIA => 'Cortesia',
        self::TYPE_TESTE => 'Teste',
    ];

    public const STATUS_LABELS = [
        self::STATUS_ENVIADO => 'Enviado',
        self::STATUS_FALHOU => 'Falhou',
        self::STATUS_PULADO => 'Pulado',
    ];

    protected $fillable = [
        'campaign_id',
        'campaign_sponsor_id',
        'campaign_installment_id',
        'type',
        'template_used',
        'status',
        'trigger',
        'user_id',
        'reason',
        'message',
        'overdue_count',
        'overdue_amount',
        'days_overdue',
        'pdf_attached',
        'sent_at',
    ];

    protected $casts = [
        'overdue_amount' => 'decimal:2',
        'overdue_count' => 'integer',
        'days_overdue' => 'integer',
        'pdf_attached' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(CampaignSponsor::class, 'campaign_sponsor_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(CampaignInstallment::class, 'campaign_installment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
