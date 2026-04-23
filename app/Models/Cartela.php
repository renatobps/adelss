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
    ];

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
