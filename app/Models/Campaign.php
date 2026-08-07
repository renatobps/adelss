<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Campaign extends Model
{
    public const STATUS_ATIVA = 'ativa';
    public const STATUS_ENCERRADA = 'encerrada';
    public const STATUS_CANCELADA = 'cancelada';

    protected $fillable = [
        'name',
        'description',
        'department_id',
        'goal_amount',
        'installment_amount',
        'installments_count',
        'first_due_date',
        'start_date',
        'end_date',
        'receipt_message',
        'status',
        'created_by',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'installments_count' => 'integer',
        'first_due_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(CampaignSponsor::class);
    }

    public function installments(): HasManyThrough
    {
        return $this->hasManyThrough(CampaignInstallment::class, CampaignSponsor::class);
    }

    public function totalRaised(): float
    {
        return (float) $this->installments()
            ->where('campaign_installments.status', CampaignInstallment::STATUS_PAGO)
            ->sum('campaign_installments.amount');
    }

    public function totalExpected(): float
    {
        return (float) $this->installments()
            ->where('campaign_installments.status', '!=', CampaignInstallment::STATUS_CANCELADO)
            ->sum('campaign_installments.amount');
    }

    public function totalPending(): float
    {
        return (float) $this->installments()
            ->where('campaign_installments.status', CampaignInstallment::STATUS_PENDENTE)
            ->sum('campaign_installments.amount');
    }

    public function totalOverdue(): float
    {
        return (float) $this->overdueInstallments()->sum('campaign_installments.amount');
    }

    public function progressPercentage(): float
    {
        $base = (float) ($this->goal_amount ?: 0);
        if ($base <= 0) {
            $base = $this->totalExpected();
        }
        if ($base <= 0) {
            return 0.0;
        }

        return round(min(100, ($this->totalRaised() / $base) * 100), 1);
    }

    public function overdueInstallments(): HasManyThrough
    {
        return $this->installments()
            ->where('campaign_installments.status', CampaignInstallment::STATUS_PENDENTE)
            ->whereNotNull('campaign_installments.due_date')
            ->whereDate('campaign_installments.due_date', '<', now()->toDateString());
    }

    /**
     * Próximo número de recibo sequencial da campanha (ex: 003/2026).
     * Baseado no maior número já emitido — nunca reutiliza um número,
     * mesmo que o pagamento correspondente tenha sido estornado.
     */
    public function nextReceiptNumber(): string
    {
        $max = CampaignInstallment::query()
            ->whereHas('sponsor', fn ($q) => $q->where('campaign_id', $this->id))
            ->whereNotNull('receipt_number')
            ->pluck('receipt_number')
            ->map(fn ($n) => (int) strtok((string) $n, '/'))
            ->max() ?? 0;

        return str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT) . '/' . now()->year;
    }
}
