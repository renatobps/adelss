<?php

namespace App\Models\Discipleship;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscipleshipIndicatorValue extends Model
{
    use HasFactory;

    protected $table = 'discipleship_indicator_values';

    protected $fillable = [
        'indicator_id',
        'discipleship_member_id',
        'valor',
        'observacao',
        'data_registro',
    ];

    protected $casts = [
        'data_registro' => 'date',
        'valor' => 'string',
    ];

    /**
     * Relacionamento com o indicador
     */
    public function indicator()
    {
        return $this->belongsTo(DiscipleshipIndicator::class, 'indicator_id');
    }

    /**
     * Relacionamento com o membro do discipulado
     */
    public function discipleshipMember()
    {
        return $this->belongsTo(DiscipleshipMember::class, 'discipleship_member_id');
    }

    /**
     * Scope para ordenar por data
     */
    public function scopePorData($query)
    {
        return $query->orderBy('data_registro', 'desc');
    }
}
