<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignSponsor extends Model
{
    protected $fillable = [
        'campaign_id',
        'member_id',
        'name',
        'phone',
        'notes',
        'created_by',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(CampaignInstallment::class)->orderBy('installment_number');
    }

    public function paidInstallments(): HasMany
    {
        return $this->hasMany(CampaignInstallment::class)
            ->where('status', CampaignInstallment::STATUS_PAGO);
    }

    public function totalPaid(): float
    {
        return (float) $this->installments()
            ->where('status', CampaignInstallment::STATUS_PAGO)
            ->sum('amount');
    }

    public function totalCommitted(): float
    {
        return (float) $this->installments()
            ->where('status', '!=', CampaignInstallment::STATUS_CANCELADO)
            ->sum('amount');
    }

    /** Situação para prestação de contas: quitado | em_atraso | em_dia */
    public function situacao(): string
    {
        $pendentes = $this->installments()
            ->where('status', CampaignInstallment::STATUS_PENDENTE)
            ->get();

        if ($pendentes->isEmpty()) {
            return 'quitado';
        }

        $temAtraso = $pendentes->contains(
            fn ($i) => $i->due_date !== null && $i->due_date->isPast() && !$i->due_date->isToday()
        );

        return $temAtraso ? 'em_atraso' : 'em_dia';
    }

    /**
     * Gera as N parcelas conforme a configuração da campanha.
     */
    public function generateInstallments(): void
    {
        $campaign = $this->campaign;
        for ($n = 1; $n <= max(1, (int) $campaign->installments_count); $n++) {
            $this->installments()->create([
                'installment_number' => $n,
                'amount' => $campaign->installment_amount,
                'due_date' => $campaign->first_due_date?->copy()->addMonthsNoOverflow($n - 1),
                'status' => CampaignInstallment::STATUS_PENDENTE,
            ]);
        }
    }
}
