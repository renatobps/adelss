<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NumeroRifa extends Model
{
    use HasFactory;

    protected $table = 'numeros_rifa';

    protected $fillable = [
        'rifa_id',
        'venda_id',
        'numero',
        'status',
        'comprador_nome',
        'comprador_telefone',
        'vendedor_id',
        'data_venda',
    ];

    protected $casts = [
        'data_venda' => 'datetime',
    ];

    public function rifa(): BelongsTo
    {
        return $this->belongsTo(Rifa::class);
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(RifaVenda::class, 'venda_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'vendedor_id');
    }

    public function cartelas(): BelongsToMany
    {
        return $this->belongsToMany(Cartela::class, 'cartela_numero', 'numero_id', 'cartela_id')
            ->withTimestamps();
    }
}
