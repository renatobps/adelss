<?php

namespace App\Models\Discipleship;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscipleshipGoal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discipleship_goals';

    protected $fillable = [
        'discipleship_member_id',
        'tipo',
        'descricao',
        'prazo',
        'status',
        'observacao',
        'quantidade_dias',
        'restricoes',
        'tipo_jejum',
        'horas_jejum_total',
        'dias_jejum_parcial',
        'alimentos_retirados',
        'periodos_oracao_dia',
        'minutos_oracao_periodo',
        'livro_biblia',
        'capitulos_por_dia',
    ];

    protected $casts = [
        'prazo' => 'date',
        'restricoes' => 'array',
        'alimentos_retirados' => 'array',
    ];

    /**
     * Relacionamento com o membro do discipulado
     */
    public function discipleshipMember()
    {
        return $this->belongsTo(DiscipleshipMember::class, 'discipleship_member_id');
    }

    /**
     * Encontros em que este propósito foi discutido
     */
    public function meetings()
    {
        return $this->belongsToMany(DiscipleshipMeeting::class, 'discipleship_meeting_goal', 'discipleship_goal_id', 'discipleship_meeting_id')
            ->withTimestamps();
    }

    /**
     * Scope para propósitos em andamento
     */
    public function scopeEmAndamento($query)
    {
        return $query->where('status', 'em_andamento');
    }

    /**
     * Scope para propósitos concluídos
     */
    public function scopeConcluidos($query)
    {
        return $query->where('status', 'concluido');
    }

    /**
     * Scope para propósitos vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('status', 'em_andamento')
            ->where('prazo', '<', now());
    }
}
