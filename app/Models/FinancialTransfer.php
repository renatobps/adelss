<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Movimentação de dinheiro entre contas próprias (ex.: depósito do caixa na Mercado Pago).
 * Não é receita nem despesa: muda apenas o saldo das contas envolvidas.
 */
class FinancialTransfer extends Model
{
    use SoftDeletes;

    protected $table = 'financial_transfers';

    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'transfer_date',
        'amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'to_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
