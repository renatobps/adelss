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
            '{culto}' => 'Nome do culto',
            '{dia_culto}' => 'Data do culto (ex.: 08/05/2026)',
            '{hora_culto}' => 'Horário do culto (ex.: 19:30)',
            '{area_servico}' => 'Área/função em que vai servir',
            '{local_servico}' => 'Local onde vai servir',
        ];
    }

    /**
     * Aplica variáveis de template em uma mensagem.
     * Variáveis não informadas permanecem no texto para preenchimento futuro.
     */
    public static function aplicarVariaveis(string $template, array $dados = []): string
    {
        if ($template === '') {
            return '';
        }

        $valoresPadrao = [
            '{nome}' => '',
            '{data}' => now()->format('d/m/Y'),
            '{dia}' => now()->format('d'),
            '{culto}' => '',
            '{dia_culto}' => '',
            '{hora_culto}' => '',
            '{area_servico}' => '',
            '{local_servico}' => '',
        ];

        $substituicoes = [];
        foreach ($valoresPadrao as $chave => $valorPadrao) {
            $substituicoes[$chave] = (string) ($dados[$chave] ?? $valorPadrao);
        }

        foreach ($dados as $chave => $valor) {
            if (! isset($substituicoes[$chave])) {
                $substituicoes[$chave] = (string) $valor;
            }
        }

        return strtr($template, $substituicoes);
    }
}
