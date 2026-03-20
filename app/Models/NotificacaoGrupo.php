<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NotificacaoGrupo extends Model
{
    protected $table = 'notificacao_grupos';

    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected $casts = [
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'grupo_member', 'grupo_id', 'member_id')->withTimestamps();
    }
}
