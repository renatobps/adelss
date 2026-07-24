<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacaoEnviada extends Model
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_ENVIADA = 'enviada';
    public const STATUS_ENTREGUE = 'entregue';
    public const STATUS_LIDA = 'lida';
    public const STATUS_ERRO = 'erro';

    public const STATUSES = [
        self::STATUS_PENDENTE => 'Pendente',
        self::STATUS_ENVIADA => 'Enviada',
        self::STATUS_ENTREGUE => 'Recebido',
        self::STATUS_LIDA => 'Lida',
        self::STATUS_ERRO => 'Erro',
    ];

    protected $table = 'notificacoes_enviadas';

    protected $fillable = [
        'member_id',
        'telefone',
        'tipo_notificacao',
        'mensagem',
        'data_envio',
        'recebido_em',
        'lido_em',
        'status',
        'whatsapp_message_id',
        'resposta_api',
        'tentativas',
        'erro_detalhes',
    ];

    protected $casts = [
        'data_envio' => 'datetime',
        'recebido_em' => 'datetime',
        'lido_em' => 'datetime',
        'resposta_api' => 'array',
        'tentativas' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function scopeEnviadas($query)
    {
        return $query->where('status', self::STATUS_ENVIADA);
    }

    public function scopeComErro($query)
    {
        return $query->where('status', self::STATUS_ERRO);
    }

    public function scopePendentes($query)
    {
        return $query->where('status', self::STATUS_PENDENTE);
    }
}
