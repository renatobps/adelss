<?php

namespace App\Models\Discipleship;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscipleshipIndicator extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discipleship_indicators';

    protected $fillable = [
        'nome',
        'tipo',
        'ativo',
        'order',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Relacionamento com valores do indicador
     */
    public function values()
    {
        return $this->hasMany(DiscipleshipIndicatorValue::class, 'indicator_id');
    }

    /**
     * Scope para indicadores ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope para indicadores espirituais
     */
    public function scopeEspirituais($query)
    {
        return $query->where('tipo', 'espiritual');
    }

    /**
     * Scope para indicadores materiais
     */
    public function scopeMateriais($query)
    {
        return $query->where('tipo', 'material');
    }
}
