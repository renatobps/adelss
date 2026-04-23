<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SorteioRifa extends Model
{
    use HasFactory;

    protected $table = 'rifa_sorteios';

    protected $fillable = [
        'rifa_id',
        'numero_rifa_id',
        'numero',
        'comprador_nome',
        'vendedor_id',
        'vendedor_nome',
        'sorteado_por_id',
    ];

    public function rifa(): BelongsTo
    {
        return $this->belongsTo(Rifa::class);
    }

    public function numeroRifa(): BelongsTo
    {
        return $this->belongsTo(NumeroRifa::class, 'numero_rifa_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'vendedor_id');
    }

    public function sorteadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sorteado_por_id');
    }
}
