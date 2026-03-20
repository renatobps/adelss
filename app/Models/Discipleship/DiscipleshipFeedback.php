<?php

namespace App\Models\Discipleship;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscipleshipFeedback extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discipleship_feedbacks';

    protected $fillable = [
        'discipleship_member_id',
        'autor_id',
        'visibilidade',
        'conteudo',
    ];

    /**
     * Relacionamento com o membro do discipulado
     */
    public function discipleshipMember()
    {
        return $this->belongsTo(DiscipleshipMember::class, 'discipleship_member_id');
    }

    /**
     * Relacionamento com o autor do feedback
     */
    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    /**
     * Scope para ordenar por data (mais recente primeiro)
     */
    public function scopeRecentes($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
