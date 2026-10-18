<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lançamento de um evento/campanha: gasto (saída) ou receita extra (entrada
 * fora das parcelas dos patrocinadores — doação externa, venda, rifa, etc.).
 * Gastos exigem comprovante anexado; receitas, não.
 */
class CampaignExpense extends Model
{
    public const KIND_GASTO = 'gasto';
    public const KIND_RECEITA = 'receita';

    public const KINDS = [
        self::KIND_GASTO => 'Gasto',
        self::KIND_RECEITA => 'Receita',
    ];

    public const CATEGORIES = [
        'alimentacao' => 'Alimentação',
        'aluguel' => 'Aluguel / Espaço',
        'transporte' => 'Transporte',
        'decoracao' => 'Decoração',
        'material' => 'Materiais',
        'divulgacao' => 'Divulgação',
        'servicos' => 'Serviços / Mão de obra',
        'brindes' => 'Brindes / Premiações',
        'outros' => 'Outros',
    ];

    public const INCOME_CATEGORIES = [
        'doacao_externa' => 'Doação externa',
        'doacao_avulsa' => 'Doação avulsa',
        'venda' => 'Venda / cantina',
        'rifa' => 'Rifa / sorteio',
        'ingresso' => 'Ingresso',
        'patrocinio_avulso' => 'Patrocínio avulso',
        'outros' => 'Outros',
    ];

    public const PAYMENT_METHODS = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'PIX',
        'cartao' => 'Cartão',
        'outro' => 'Outro',
    ];

    protected $fillable = [
        'campaign_id',
        'kind',
        'description',
        'category',
        'amount',
        'expense_date',
        'supplier',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CampaignExpenseAttachment::class);
    }

    public function isIncome(): bool
    {
        return $this->kind === self::KIND_RECEITA;
    }

    public function isExpense(): bool
    {
        return $this->kind !== self::KIND_RECEITA;
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? ucfirst((string) $this->kind);
    }

    public function categoryLabel(): string
    {
        $map = $this->isIncome() ? self::INCOME_CATEGORIES : self::CATEGORIES;

        return $map[$this->category] ?? ucfirst((string) $this->category);
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ($this->payment_method ? ucfirst($this->payment_method) : '—');
    }

    public function scopeGastos(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_GASTO);
    }

    public function scopeReceitas(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_RECEITA);
    }
}
