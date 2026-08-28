<?php

namespace App\Services;

use App\Models\Member;
use App\Models\NotificacaoEnviada;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class WhatsAppSendHistory
{
    /**
     * @param  array{success?: bool, data?: mixed, error?: string|null}  $resultado
     */
    public function record(string $numero, string $mensagem, array $resultado, string $tipoNotificacao = 'custom'): void
    {
        try {
            if (! Schema::hasTable('notificacoes_enviadas')) {
                return;
            }

            $sucesso = (bool) ($resultado['success'] ?? false);
            $payload = $resultado['data'] ?? $resultado;
            $telefone = WhatsAppService::normalizarNumero($numero) ?: $numero;
            $texto = trim($mensagem) !== '' ? $mensagem : '[sem texto]';

            $attrs = [
                'member_id' => $this->encontrarMembroId($telefone),
                'telefone' => $telefone !== '' ? $telefone : null,
                'tipo_notificacao' => $tipoNotificacao !== '' ? $tipoNotificacao : 'custom',
                'mensagem' => $texto,
                'data_envio' => now(),
                'status' => $sucesso ? NotificacaoEnviada::STATUS_ENVIADA : NotificacaoEnviada::STATUS_ERRO,
                'whatsapp_message_id' => $sucesso ? $this->extrairWhatsappMessageId($payload) : null,
                'resposta_api' => is_array($payload) ? $payload : ['raw' => $payload],
                'tentativas' => 1,
                'erro_detalhes' => $sucesso ? null : $this->humanizarErro($resultado['error'] ?? null),
            ];

            if (Schema::hasColumn('notificacoes_enviadas', 'origem')) {
                $attrs['origem'] = $this->detectarOrigem();
            }

            NotificacaoEnviada::create($attrs);
        } catch (Throwable $e) {
            Log::warning('Falha ao gravar histórico de WhatsApp.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function detectarOrigem(): string
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 16) as $frame) {
            $class = (string) ($frame['class'] ?? '');
            if ($class === '' || str_contains($class, 'WhatsAppSendHistory') || str_contains($class, 'WhatsAppService')) {
                continue;
            }

            $short = class_basename($class);

            return match ($short) {
                'NotificacaoService', 'PainelController', 'NotificacoesController' => 'painel',
                'EnqueteService', 'EnqueteController' => 'enquetes',
                'FinancialNotificationService', 'AutomationController',
                'NotifyDueFinancialExpenses', 'SendSmartFinancialSummary' => 'financeiro',
                'CampaignReceiptService', 'CampaignReminderService', 'CampaignSponsorController',
                'CampaignReminderController', 'CampaignController',
                'NotifyDueCampaignInstallments', 'SendCampaignScheduledMessages' => 'campanhas',
                'EventRegistrationReceiptService', 'EventRegistrationBatchSender',
                'SendEventRegistrationWhatsappMessages',
                'EventosController', 'PublicEventController' => 'agenda',
                'ConfigController' => 'whatsapp_teste',
                'CheckPastoralAttendanceAlerts' => 'cultos',
                default => $this->origemPorNamespace($class),
            };
        }

        return 'sistema';
    }

    private function origemPorNamespace(string $class): string
    {
        return match (true) {
            str_contains($class, '\\Financial\\') || str_contains($class, 'Financial') => 'financeiro',
            str_contains($class, '\\Agenda\\') || str_contains($class, 'Event') => 'agenda',
            str_contains($class, 'Campaign') => 'campanhas',
            str_contains($class, 'Enquete') => 'enquetes',
            str_contains($class, 'Notificac') || str_contains($class, 'Painel') => 'painel',
            str_contains($class, 'Culto') || str_contains($class, 'Pastoral') => 'cultos',
            str_contains($class, 'Discipleship') => 'discipulado',
            default => 'sistema',
        };
    }

    private function encontrarMembroId(string $phoneNormalizado): ?int
    {
        if (! Schema::hasTable('members') || strlen(preg_replace('/\D+/', '', $phoneNormalizado) ?? '') < 10) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phoneNormalizado) ?? '';
        $sufixo = substr($digits, -11);

        $id = Member::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where(function ($q) use ($digits, $sufixo) {
                $q->where('phone', $digits)
                    ->orWhere('phone', 'like', '%'.$sufixo);
            })
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function extrairWhatsappMessageId(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        foreach ([
            data_get($payload, 'data.Info.ID'),
            data_get($payload, 'data.key.id'),
            data_get($payload, 'key.id'),
            data_get($payload, 'data.id'),
            data_get($payload, 'messageId'),
            data_get($payload, 'id'),
        ] as $id) {
            if (is_string($id) && trim($id) !== '') {
                return trim($id);
            }
        }

        return null;
    }

    private function humanizarErro(?string $erro): ?string
    {
        if ($erro === null || trim($erro) === '') {
            return 'Falha no envio.';
        }

        $erro = trim($erro);
        if (stripos($erro, 'not registered on WhatsApp') !== false) {
            return 'Número não está registrado no WhatsApp (verifique DDD + número).';
        }

        return $erro;
    }
}
