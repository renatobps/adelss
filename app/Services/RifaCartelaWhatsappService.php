<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Rifa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;

class RifaCartelaWhatsappService
{
    public function __construct(private WhatsAppService $whatsapp) {}

    public function enviar(Rifa $rifa, Member $vendedor, Collection $cartelas): array
    {
        if ($cartelas->isEmpty() || $cartelas->contains(fn ($c) => (int) $c->rifa_id !== (int) $rifa->id || (int) $c->vendedor_id !== (int) $vendedor->id)) {
            return ['success' => false, 'message' => 'Nenhuma cartela válida para este vendedor nesta rifa.'];
        }
        if (empty($vendedor->phone)) return ['success' => false, 'message' => 'O vendedor não possui telefone cadastrado.'];
        if (!$this->whatsapp->isConfigurado()) return ['success' => false, 'message' => 'Configure a conexão WhatsApp antes de enviar as cartelas.'];
        $path = null;
        try {
            $cartelas->load(['vendedor', 'numeros' => fn ($q) => $q->orderBy('numeros_rifa.id')]);
            $cartela = $cartelas->count() === 1 ? $cartelas->first() : null;
            $fileName = 'rifa-'.$rifa->id.($cartela ? '-cartela-'.$cartela->id : '-vendedor-'.$vendedor->id).'.pdf';
            $path = tempnam(sys_get_temp_dir(), 'rifa-cartela-');
            if ($path === false) throw new \RuntimeException('Falha ao criar arquivo temporário da cartela.');
            $pdf = Pdf::loadView('rifas.cartelas.pdf', compact('rifa', 'cartela', 'cartelas'))->setPaper('a4');
            if (file_put_contents($path, $pdf->output()) === false) throw new \RuntimeException('Falha ao gravar cartela temporária.');
            $legenda = 'Olá, '.$vendedor->name.'! '.($cartela ? 'Segue sua '.$cartela->identificador : 'Seguem suas '.$cartelas->count().' cartelas').' da rifa '.$rifa->nome.'. Valor por número: R$ '.number_format((float) $rifa->valor_numero, 2, ',', '.').'.';
            if ($rifa->pix_chave && $rifa->pix_recebedor) {
                $legenda .= "\n\nPagamento por PIX\nRecebedor: ".$rifa->pix_recebedor."\nChave PIX: ".$rifa->pix_chave;
            }
            $result = $this->whatsapp->enviarDocumentoArquivo($vendedor->phone, $path, $fileName, $legenda);
            if (!($result['success'] ?? false)) return ['success' => false, 'message' => 'O WhatsApp não confirmou o envio. Confira a conexão antes de reenviar.'];
            $rifa->cartelas()->whereIn('id', $cartelas->modelKeys())->update(['whatsapp_enviado_em' => now()]);
            return ['success' => true, 'message' => 'Cartelas enviadas ao WhatsApp de '.$vendedor->name.'.'];
        } catch (\Throwable $e) {
            report($e);
            return ['success' => false, 'message' => 'Não foi possível enviar. Confira a conexão WhatsApp antes de tentar novamente.'];
        } finally {
            if (is_string($path) && is_file($path)) unlink($path);
        }
    }
}
