<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracaoWhatsapp extends Model
{
    protected $table = 'configuracoes_whatsapp';

    protected $fillable = ['chave', 'valor', 'descricao'];

    public static function getValor(string $chave, $padrao = null)
    {
        $config = self::where('chave', $chave)->first();
        return $config ? $config->valor : $padrao;
    }

    public static function setValor(string $chave, $valor, ?string $descricao = null): self
    {
        return self::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor, 'descricao' => $descricao]
        );
    }
}
