<?php

namespace App\Services;

use App\Models\ConfiguracaoMensagem;
use App\Models\Member;
use App\Models\Rifa;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class RifaCartelaMensagemService
{
    public const TIPO = 'rifas_cartelas';

    public function cadastrada(): ?string
    {
        $template = ConfiguracaoMensagem::getTemplate(self::TIPO);
        return is_string($template) && trim($template) !== '' ? $template : null;
    }

    public function variaveis(): array
    {
        return [
            '{nome}' => 'Nome do vendedor', '{rifa}' => 'Nome da rifa',
            '{cartelas}' => 'Identificação das cartelas enviadas', '{quantidade_cartelas}' => 'Quantidade de cartelas no arquivo',
            '{valor_numero}' => 'Valor por número, em reais', '{data_sorteio}' => 'Data do sorteio',
            '{chave_pix}' => 'Chave PIX da rifa', '{recebedor_pix}' => 'Nome do recebedor do PIX',
        ];
    }

    public function gerar(Rifa $rifa, Member $vendedor, Collection $cartelas, string $tipo = 'padrao'): string
    {
        if (!in_array($tipo, ['padrao', 'cadastrada'], true)) {
            throw ValidationException::withMessages(['mensagem_tipo' => 'Escolha a mensagem padrão ou a mensagem cadastrada.']);
        }
        if ($tipo === 'cadastrada') {
            $template = $this->cadastrada();
            if ($template === null) {
                throw ValidationException::withMessages(['mensagem_tipo' => 'Cadastre uma mensagem em Configuração antes de escolher a mensagem cadastrada.']);
            }
            return ConfiguracaoMensagem::aplicarVariaveis($template, [
                '{nome}' => $vendedor->name, '{rifa}' => $rifa->nome,
                '{cartelas}' => $cartelas->pluck('identificador')->implode(', '),
                '{quantidade_cartelas}' => (string) $cartelas->count(),
                '{valor_numero}' => 'R$ '.number_format((float) $rifa->valor_numero, 2, ',', '.'),
                '{data_sorteio}' => $rifa->data_sorteio?->format('d/m/Y') ?? 'A definir',
                '{chave_pix}' => $rifa->pix_chave ?? '', '{recebedor_pix}' => $rifa->pix_recebedor ?? '',
            ]);
        }
        $cartela = $cartelas->count() === 1 ? $cartelas->first() : null;
        $mensagem = 'Olá, '.$vendedor->name.'! '.($cartela ? 'Segue sua '.$cartela->identificador : 'Seguem suas '.$cartelas->count().' cartelas').' da rifa '.$rifa->nome.'. Valor por número: R$ '.number_format((float) $rifa->valor_numero, 2, ',', '.').'.';
        if ($rifa->pix_chave && $rifa->pix_recebedor) {
            $mensagem .= "\n\nPagamento por PIX\nRecebedor: ".$rifa->pix_recebedor."\nChave PIX: ".$rifa->pix_chave;
        }
        return $mensagem;
    }
}
