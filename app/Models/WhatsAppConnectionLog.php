<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConnectionLog extends Model
{
    public const STATUS_CONECTADO = 'conectado';
    public const STATUS_DESCONECTADO = 'desconectado';
    public const STATUS_ERRO_CONSULTA = 'erro_consulta';

    protected $table = 'whatsapp_connection_logs';

    protected $fillable = [
        'instance_name',
        'instance_id',
        'status',
        'raw_state',
        'checked_at',
        'notified',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'notified' => 'boolean',
    ];

    public function isOnline(): bool
    {
        return $this->status === self::STATUS_CONECTADO;
    }
}
