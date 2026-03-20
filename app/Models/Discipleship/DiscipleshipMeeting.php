<?php

namespace App\Models\Discipleship;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscipleshipMeeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discipleship_meetings';

    protected $fillable = [
        'discipleship_member_id',
        'data',
        'tipo',
        'assuntos_tratados',
        'observacoes_privadas',
        'proximo_passo',
        'oracao_tempo_dia',
        'oracao_como_sao',
        'oracao_observacoes',
        'jejum_horas_semana',
        'jejum_tipo',
        'jejum_com_proposito',
        'jejum_observacoes',
        'leitura_capitulos_dia',
        'leitura_estuda',
        'leitura_observacoes',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    /**
     * Relacionamento com o membro do discipulado
     */
    public function discipleshipMember()
    {
        return $this->belongsTo(DiscipleshipMember::class, 'discipleship_member_id');
    }

    /**
     * Propósitos vinculados a este encontro
     */
    public function goals()
    {
        return $this->belongsToMany(DiscipleshipGoal::class, 'discipleship_meeting_goal', 'discipleship_meeting_id', 'discipleship_goal_id')
            ->withTimestamps();
    }

    /**
     * Retorna valor numérico de oracao_tempo_dia para gráficos (minutos)
     */
    public function getOracaoTempoNumeroAttribute(): int
    {
        if (!$this->oracao_tempo_dia) return 0;
        return $this->oracao_tempo_dia === 'mais_1h' ? 90 : (int) $this->oracao_tempo_dia;
    }

    /**
     * Retorna valor numérico de jejum_horas_semana para gráficos
     */
    public function getJejumHorasNumeroAttribute(): int
    {
        if (!$this->jejum_horas_semana) return 0;
        return $this->jejum_horas_semana === 'mais_24' ? 30 : (int) $this->jejum_horas_semana;
    }

    /**
     * Retorna valor numérico de leitura_capitulos_dia para gráficos
     */
    public function getLeituraCapitulosNumeroAttribute(): int
    {
        if (!$this->leitura_capitulos_dia) return 0;
        return $this->leitura_capitulos_dia === 'mais_10' ? 12 : (int) $this->leitura_capitulos_dia;
    }

    /**
     * Scope para ordenar por data (mais recente primeiro)
     */
    public function scopeRecentes($query)
    {
        return $query->orderBy('data', 'desc');
    }
}
