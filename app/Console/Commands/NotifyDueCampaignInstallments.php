<?php

namespace App\Console\Commands;

use App\Models\CampaignInstallment;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NotifyDueCampaignInstallments extends Command
{
    protected $signature = 'campaigns:notify-due-installments {--force : Ignora o controle de reenvio diário}';

    protected $description = 'Envia lembretes por WhatsApp de parcelas de campanha próximas do vencimento';

    public function handle(WhatsAppService $whatsapp): int
    {
        $daysAhead = max(0, (int) config('financial.campaigns.due_reminder_days_ahead', 3));
        $today = now()->startOfDay();
        $limit = $today->copy()->addDays($daysAhead)->endOfDay();

        $installments = CampaignInstallment::query()
            ->where('status', CampaignInstallment::STATUS_PENDENTE)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $limit])
            ->when(!$this->option('force'), function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->whereNull('reminder_sent_on')
                        ->orWhere('reminder_sent_on', '<', $today->toDateString());
                });
            })
            ->whereHas('sponsor', function ($q) {
                $q->whereNotNull('phone')->where('phone', '!=', '');
            })
            ->whereHas('sponsor.campaign', fn ($q) => $q->where('status', 'ativa'))
            ->with('sponsor.campaign')
            ->orderBy('due_date')
            ->get();

        if ($installments->isEmpty()) {
            $this->info('Nenhuma parcela de campanha a lembrar hoje.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($installments as $installment) {
            $sponsor = $installment->sponsor;
            $campaign = $sponsor->campaign;

            $mensagem = "🔔 *Lembrete de parcela — {$campaign->name}*\n\n"
                . "Olá, {$sponsor->name}!\n"
                . "A parcela *{$installment->installment_number}/{$campaign->installments_count}* "
                . 'no valor de *R$ ' . number_format((float) $installment->amount, 2, ',', '.') . '* '
                . 'vence em *' . $installment->due_date->format('d/m/Y') . "*.\n\n"
                . 'Deus abençoe sua contribuição! 🙏';

            try {
                $resultado = $whatsapp->enviarMensagem($sponsor->phone, $mensagem);

                if ($resultado['success'] ?? false) {
                    $installment->update(['reminder_sent_on' => now()->toDateString()]);
                    $sent++;
                } else {
                    $failed++;
                    Log::warning('Campanha: falha ao enviar lembrete de parcela.', [
                        'installment_id' => $installment->id,
                        'error' => $resultado['error'] ?? 'desconhecido',
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Campanha: erro inesperado ao enviar lembrete.', [
                    'installment_id' => $installment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Lembretes enviados: {$sent}. Falhas: {$failed}.");

        return self::SUCCESS;
    }
}
