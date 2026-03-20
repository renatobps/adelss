<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquete extends Model
{
    protected $table = 'notificacao_enquetes';

    protected $fillable = [
        'titulo',
        'descricao',
        'tipo',
        'opcoes',
        'ativa',
        'inicio_em',
        'fim_em',
    ];

    protected $casts = [
        'opcoes' => 'array',
        'ativa' => 'boolean',
        'inicio_em' => 'datetime',
        'fim_em' => 'datetime',
    ];

    protected $attributes = [
        'tipo' => 'texto',
    ];

    public function respostas(): HasMany
    {
        return $this->hasMany(EnqueteResposta::class, 'enquete_id');
    }

    public function envios(): HasMany
    {
        return $this->hasMany(EnqueteEnvio::class, 'enquete_id');
    }

    public function getTotalRespostasAttribute(): int
    {
        return $this->respostas()->count();
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativa', true);
    }
}
