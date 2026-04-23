<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RifaVenda extends Model
{
    use HasFactory;

    protected $table = 'rifa_vendas';

    protected $fillable = [
        'rifa_id',
        'vendedor_id',
        'comprador_nome',
        'comprador_telefone',
        'status',
        'valor_total',
        'data_venda',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
        'data_venda' => 'datetime',
    ];

    public function rifa(): BelongsTo
    {
        return $this->belongsTo(Rifa::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'vendedor_id');
    }

    public function numeros(): HasMany
    {
        return $this->hasMany(NumeroRifa::class, 'venda_id');
    }
}
