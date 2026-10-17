<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gasto de um evento/campanha (ex.: comida, aluguel de chácara, decoração).
 * Todo gasto precisa de pelo menos um comprovante anexado.
 */
class CampaignExpense extends Model
{
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

    public const PAYMENT_METHODS = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'PIX',
        'cartao' => 'Cartão',
        'outro' => 'Outro',
    ];

    protected $fillable = [
        'campaign_id',
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

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ($this->payment_method ? ucfirst($this->payment_method) : '—');
    }
}
