<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cartela extends Model
{
    use HasFactory;

    protected $fillable = [
        'rifa_id',
        'identificador',
        'vendedor_id',
        'whatsapp_enviado_em',
    ];

    protected $casts = ['whatsapp_enviado_em' => 'datetime'];

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'vendedor_id');
    }

    public function rifa(): BelongsTo
    {
        return $this->belongsTo(Rifa::class);
    }

    public function numeros(): BelongsToMany
    {
        return $this->belongsToMany(NumeroRifa::class, 'cartela_numero', 'cartela_id', 'numero_id')
            ->withTimestamps();
    }
}
