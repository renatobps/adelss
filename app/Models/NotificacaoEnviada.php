<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacaoEnviada extends Model
{
    protected $table = 'notificacoes_enviadas';

    protected $fillable = [
        'member_id',
        'telefone',
        'tipo_notificacao',
        'mensagem',
        'data_envio',
        'status',
        'resposta_api',
        'tentativas',
        'erro_detalhes',
    ];

    protected $casts = [
        'data_envio' => 'datetime',
        'resposta_api' => 'array',
        'tentativas' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function scopeEnviadas($query)
    {
        return $query->where('status', 'enviada');
    }

    public function scopeComErro($query)
    {
        return $query->where('status', 'erro');
    }

    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }
}
