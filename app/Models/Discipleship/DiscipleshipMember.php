<?php

namespace App\Models\Discipleship;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscipleshipMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discipleship_members';

    protected $fillable = [
        'cycle_id',
        'member_id',
        'discipulador_id',
        'status',
        'data_inicio',
        'data_fim',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    /**
     * Relacionamento com o ciclo
     */
    public function cycle()
    {
        return $this->belongsTo(DiscipleshipCycle::class, 'cycle_id');
    }

    /**
     * Relacionamento com o membro
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Relacionamento com o discipulador (usuário)
     */
    public function discipulador()
    {
        return $this->belongsTo(User::class, 'discipulador_id');
    }

    /**
     * Relacionamento com encontros
     */
    public function meetings()
    {
        return $this->hasMany(DiscipleshipMeeting::class, 'discipleship_member_id')->orderBy('data', 'desc');
    }

    /**
     * Relacionamento com valores de indicadores
     */
    public function indicatorValues()
    {
        return $this->hasMany(DiscipleshipIndicatorValue::class, 'discipleship_member_id');
    }

    /**
     * Relacionamento com propósitos/metas
     */
    public function goals()
    {
        return $this->hasMany(DiscipleshipGoal::class, 'discipleship_member_id');
    }

    /**
     * Relacionamento com feedbacks
     */
    public function feedbacks()
    {
        return $this->hasMany(DiscipleshipFeedback::class, 'discipleship_member_id');
    }

    /**
     * Scope para membros ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Scope para membros concluídos
     */
    public function scopeConcluidos($query)
    {
        return $query->where('status', 'concluido');
    }
}
