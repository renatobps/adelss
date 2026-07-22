<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAccount extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'caixa' => 'Caixa',
        'conta_corrente' => 'Conta Corrente',
        'poupanca' => 'Poupança',
        'investimento' => 'Investimento',
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

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function bankDisplay(): string
    {
        $bank = trim((string) ($this->bank_name ?: ''));

        return $bank !== '' ? $bank : $this->name;
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

        return (float) $this->initial_balance + $receitas - $despesas;
    }
}
