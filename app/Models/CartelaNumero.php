<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartelaNumero extends Model
{
    use HasFactory;

    protected $table = 'cartela_numero';

    protected $fillable = [
        'cartela_id',
        'numero_id',
    ];

    public function cartela(): BelongsTo
    {
        return $this->belongsTo(Cartela::class);
    }

    public function numero(): BelongsTo
    {
        return $this->belongsTo(NumeroRifa::class, 'numero_id');
    }
}
