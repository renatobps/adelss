<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoMensagem extends Model
{
    protected $table = 'configuracoes_mensagens';

    protected $fillable = ['tipo_notificacao', 'template', 'ativo'];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public static function getTemplate(string $tipo): ?string
    {
        $config = self::where('tipo_notificacao', $tipo)->where('ativo', true)->first();
        return $config ? $config->template : null;
    }

    public static function variaveisDisponiveis(): array
    {
        return [
            '{nome}' => 'Nome do membro',
            '{data}' => 'Data atual',
            '{dia}' => 'Dia do mês',
        ];
    }
}
