<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rifa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'quantidade_numeros',
        'valor_numero',
        'numeros_por_cartela',
        'data_sorteio',
        'status',
    ];

    protected $casts = [
        'valor_numero' => 'decimal:2',
        'data_sorteio' => 'date',
    ];

    public function numeros(): HasMany
    {
        return $this->hasMany(NumeroRifa::class);
    }

    public function cartelas(): HasMany
    {
        return $this->hasMany(Cartela::class);
    }

    public function vendas(): HasMany
    {
        return $this->hasMany(RifaVenda::class);
    }

    public function sorteios(): HasMany
    {
        return $this->hasMany(SorteioRifa::class);
    }
}
