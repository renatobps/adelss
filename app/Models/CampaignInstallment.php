<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignInstallment extends Model
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_PAGO = 'pago';
    public const STATUS_CANCELADO = 'cancelado';

    public const PAYMENT_METHODS = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'PIX',
        'cartao' => 'Cartão',
        'outro' => 'Outro',
    ];

    protected $fillable = [
        'campaign_sponsor_id',
        'installment_number',
        'amount',
        'due_date',
        'status',
        'paid_at',
        'payment_method',
        'receipt_number',
        'receipt_sent_at',
        'paid_by',
        'reversed_at',
        'reversed_by',
        'reminder_sent_on',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'receipt_sent_at' => 'datetime',
        'reversed_at' => 'datetime',
        'reminder_sent_on' => 'date',
        'installment_number' => 'integer',
    ];

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(CampaignSponsor::class, 'campaign_sponsor_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAGO;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_PENDENTE
            && $this->due_date !== null
            && $this->due_date->isPast()
            && !$this->due_date->isToday();
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ($this->payment_method ?: '—');
    }
}
