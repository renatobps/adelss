<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashClosing extends Model
{
    protected $table = 'cash_closings';

    protected $fillable = [
        'period_start',
        'period_end',
        'total_receitas',
        'total_despesas',
        'saldo',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_receitas' => 'decimal:2',
        'total_despesas' => 'decimal:2',
        'saldo' => 'decimal:2',
        'generated_at' => 'datetime',
    ];

    public function generatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
