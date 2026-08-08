<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignSponsor extends Model
{
    /** Situações usadas nos filtros e na identificação visual da listagem. */
    public const SITUACOES = [
        'em_atraso' => 'Em atraso',
        'em_dia' => 'Em dia',
        'quitado' => 'Quitado',
        'nao_iniciado' => 'Não iniciado',
    ];

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
     * Totais das parcelas resolvidos em SQL (subselects), para que a listagem
     * possa filtrar, ordenar e exibir os números sem carregar as parcelas.
     */
    public function scopeWithSummary(Builder $query): Builder
    {
        $ativas = fn () => CampaignInstallment::query()
            ->whereColumn('campaign_installments.campaign_sponsor_id', 'campaign_sponsors.id')
            ->where('campaign_installments.status', '!=', CampaignInstallment::STATUS_CANCELADO);

        $count = fn (Builder $q) => $q->selectRaw('COUNT(*)');
        $sum = fn (Builder $q) => $q->selectRaw('COALESCE(SUM(campaign_installments.amount), 0)');
        $pagas = fn () => $ativas()->where('campaign_installments.status', CampaignInstallment::STATUS_PAGO);
        $pendentes = fn () => $ativas()->where('campaign_installments.status', CampaignInstallment::STATUS_PENDENTE);
        $vencidas = fn () => $pendentes()
            ->whereNotNull('campaign_installments.due_date')
            ->whereDate('campaign_installments.due_date', '<', now()->toDateString());

        return $query
            ->select('campaign_sponsors.*')
            ->selectSub($count($ativas()), 'installments_total')
            ->selectSub($count($pagas()), 'paid_count')
            ->selectSub($sum($pagas()), 'paid_amount')
            ->selectSub($count($pendentes()), 'pending_count')
            ->selectSub($sum($pendentes()), 'pending_amount')
            ->selectSub($count($vencidas()), 'overdue_count')
            ->selectSub($sum($vencidas()), 'overdue_amount');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $digits = preg_replace('/\D/', '', $term);

        return $query->where(function (Builder $q) use ($term, $digits) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");

            // Telefone é digitado com máscara no cadastro e sem máscara na busca (e vice-versa).
            if ($digits !== '') {
                $q->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(phone, '(', ''), ')', ''), '-', ''), ' ', '') LIKE ?",
                    ["%{$digits}%"]
                );
            }
        });
    }

    public function scopeSituacao(Builder $query, ?string $situacao): Builder
    {
        $vencidas = fn ($q) => $q->where('status', CampaignInstallment::STATUS_PENDENTE)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString());
        $pagas = fn ($q) => $q->where('status', CampaignInstallment::STATUS_PAGO);
        $pendentes = fn ($q) => $q->where('status', CampaignInstallment::STATUS_PENDENTE);

        return match ($situacao) {
            'em_atraso' => $query->whereHas('installments', $vencidas),
            'quitado' => $query->whereHas('installments')->whereDoesntHave('installments', $pendentes),
            'em_dia' => $query->whereHas('installments', $pagas)
                ->whereHas('installments', $pendentes)
                ->whereDoesntHave('installments', $vencidas),
            'nao_iniciado' => $query->whereDoesntHave('installments', $pagas)
                ->whereDoesntHave('installments', $vencidas),
            default => $query,
        };
    }

    /**
     * Totais das parcelas. Usa os agregados do `withSummary()` quando presentes;
     * caso contrário recorre à relação carregada.
     *
     * @return array{total:int,paid:int,pending:int,overdue:int,paid_amount:float,pending_amount:float,overdue_amount:float}
     */
    public function summary(): array
    {
        if ($this->getAttribute('installments_total') !== null) {
            return [
                'total' => (int) $this->getAttribute('installments_total'),
                'paid' => (int) $this->getAttribute('paid_count'),
                'pending' => (int) $this->getAttribute('pending_count'),
                'overdue' => (int) $this->getAttribute('overdue_count'),
                'paid_amount' => (float) $this->getAttribute('paid_amount'),
                'pending_amount' => (float) $this->getAttribute('pending_amount'),
                'overdue_amount' => (float) $this->getAttribute('overdue_amount'),
            ];
        }

        $ativas = $this->installments->where('status', '!=', CampaignInstallment::STATUS_CANCELADO);
        $pagas = $ativas->where('status', CampaignInstallment::STATUS_PAGO);
        $pendentes = $ativas->where('status', CampaignInstallment::STATUS_PENDENTE);
        $vencidas = $pendentes->filter(fn ($i) => $i->isOverdue());

        return [
            'total' => $ativas->count(),
            'paid' => $pagas->count(),
            'pending' => $pendentes->count(),
            'overdue' => $vencidas->count(),
            'paid_amount' => (float) $pagas->sum('amount'),
            'pending_amount' => (float) $pendentes->sum('amount'),
            'overdue_amount' => (float) $vencidas->sum('amount'),
        ];
    }

    /**
     * Situação da listagem: em_atraso | quitado | em_dia | nao_iniciado.
     * Diferente de `situacao()`, que atende a prestação de contas e não
     * distingue quem ainda não começou a pagar.
     */
    public function situacaoDetalhada(): string
    {
        $s = $this->summary();

        if ($s['total'] === 0) {
            return 'nao_iniciado';
        }
        if ($s['pending'] === 0) {
            return 'quitado';
        }
        if ($s['overdue'] > 0) {
            return 'em_atraso';
        }

        return $s['paid'] > 0 ? 'em_dia' : 'nao_iniciado';
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
