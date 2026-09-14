<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAccount extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_MERCADO_PAGO = 'mercado_pago';

    public const TYPE_CAIXA = 'caixa';

    public const TYPES = [
        self::TYPE_CAIXA => 'Caixa',
        'conta_corrente' => 'Conta Corrente',
        'poupanca' => 'Poupança',
        'investimento' => 'Investimento',
        self::TYPE_MERCADO_PAGO => 'Mercado Pago',
    ];

    public const COLORS = [
        '#ef4444', '#f97316', '#f59e0b', '#eab308',
        '#84cc16', '#22c55e', '#14b8a6', '#06b6d4',
        '#0ea5e9', '#3b82f6', '#2563eb', '#8b5cf6',
        '#7c3aed', '#d946ef', '#ec4899',
    ];

    protected $table = 'financial_accounts';

    protected $fillable = [
        'name',
        'description',
        'type',
        'bank_name',
        'initial_balance',
        'color',
        'is_active',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'account_id');
    }

    public function transfersOut(): HasMany
    {
        return $this->hasMany(FinancialTransfer::class, 'from_account_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(FinancialTransfer::class, 'to_account_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    /**
     * Forma como o dinheiro entra ou sai desta conta (PIX na Mercado Pago, dinheiro no caixa).
     */
    public function paymentFormLabel(): string
    {
        if ($this->isMercadoPago()) {
            return 'PIX';
        }

        if ($this->type === self::TYPE_CAIXA) {
            return 'Dinheiro';
        }

        return '';
    }

    /**
     * Rótulo usado no campo de forma de recebimento/pagamento: "PIX (Mercado Pago)".
     */
    public function paymentOptionLabel(): string
    {
        $forma = $this->paymentFormLabel();

        return $forma !== '' ? "{$forma} ({$this->name})" : $this->name;
    }

    public function bankDisplay(): string
    {
        if ($this->isMercadoPago()) {
            return 'Mercado Pago';
        }

        $bank = trim((string) ($this->bank_name ?: ''));

        return $bank !== '' ? $bank : $this->name;
    }

    public function isMercadoPago(): bool
    {
        if ($this->type === self::TYPE_MERCADO_PAGO) {
            return true;
        }

        $haystack = mb_strtolower(trim($this->name.' '.$this->bank_name));

        return str_contains($haystack, 'mercado pago')
            || str_contains($haystack, 'mercadopago');
    }

    public static function mercadoPagoAccount(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('type', self::TYPE_MERCADO_PAGO)
                    ->orWhere('bank_name', 'like', '%Mercado Pago%')
                    ->orWhere('bank_name', 'like', '%MercadoPago%')
                    ->orWhere('name', 'like', '%Mercado Pago%')
                    ->orWhere('name', 'like', '%MercadoPago%');
            })
            ->orderBy('id')
            ->first();
    }

    public function currentBalance(): float
    {
        $receitas = (float) $this->transactions()
            ->where('type', 'receita')
            ->where('is_paid', true)
            ->sum('amount');

        $despesas = (float) $this->transactions()
            ->where('type', 'despesa')
            ->where('is_paid', true)
            ->sum('amount');

        $recebidoEmTransferencias = (float) $this->transfersIn()->sum('amount');
        $enviadoEmTransferencias = (float) $this->transfersOut()->sum('amount');

        return (float) $this->initial_balance
            + $receitas - $despesas
            + $recebidoEmTransferencias - $enviadoEmTransferencias;
    }
}
