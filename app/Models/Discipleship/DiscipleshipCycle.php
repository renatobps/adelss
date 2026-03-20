<?php

namespace App\Models\Discipleship;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscipleshipCycle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discipleship_cycles';

    protected $fillable = [
        'nome',
        'descricao',
        'data_inicio',
        'data_fim',
        'status',
        'created_by',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    /**
     * Relacionamento com o usuário criador
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relacionamento com membros do ciclo
     */
    public function members()
    {
        return $this->hasMany(DiscipleshipMember::class, 'cycle_id');
    }

    /**
     * Scope para ciclos ativos
     */
    public function scopeAtivos($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Scope para ciclos encerrados
     */
    public function scopeEncerrados($query)
    {
        return $query->where('status', 'encerrado');
    }
}
