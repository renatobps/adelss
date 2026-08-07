<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendCampaignScheduledMessages extends Command
{
    protected $signature = 'campaigns:send-scheduled-messages {--force : Envia mesmo que já tenha sido enviada}';

    protected $description = 'Envia a mensagem programada da campanha (ex: aviso do dia do pagamento) por WhatsApp aos patrocinadores';

    public function handle(WhatsAppService $whatsapp): int
    {
        $campaigns = Campaign::query()
            ->where('status', Campaign::STATUS_ATIVA)
            ->whereNotNull('reminder_message')
            ->whereNotNull('reminder_send_date')
            ->whereDate('reminder_send_date', '<=', now()->toDateString())
            ->when(!$this->option('force'), fn ($q) => $q->whereNull('reminder_sent_at'))
            ->with(['sponsors' => fn ($q) => $q->whereNotNull('phone')->where('phone', '!=', '')])
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('Nenhuma mensagem programada de campanha para enviar hoje.');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $sent = 0;
            $failed = 0;

            foreach ($campaign->sponsors as $sponsor) {
                $mensagem = str_replace(
                    ['{nome}', '{campanha}', '{valor_parcela}'],
                    [
                        $sponsor->name,
                        $campaign->name,
                        'R$ ' . number_format((float) $campaign->installment_amount, 2, ',', '.'),
                    ],
                    $campaign->reminder_message
                );

                try {
                    $resultado = $whatsapp->enviarMensagem($sponsor->phone, $mensagem);

                    if ($resultado['success'] ?? false) {
                        $sent++;
                    } else {
                        $failed++;
                        Log::warning('Campanha: falha ao enviar mensagem programada.', [
                            'campaign_id' => $campaign->id,
                            'sponsor_id' => $sponsor->id,
                            'error' => $resultado['error'] ?? 'desconhecido',
                        ]);
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('Campanha: erro inesperado na mensagem programada.', [
                        'campaign_id' => $campaign->id,
                        'sponsor_id' => $sponsor->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Marca como enviada mesmo com falhas parciais, para não bombardear
            // os que receberam. Reenvio manual: alterar a data na campanha.
            $campaign->update(['reminder_sent_at' => now()]);

            $this->info("Campanha \"{$campaign->name}\": {$sent} enviada(s), {$failed} falha(s).");
        }

        return self::SUCCESS;
    }
}
