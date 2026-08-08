<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignReminderLog;
use App\Models\CampaignReminderSetting;
use App\Services\CampaignReminderService;
use Illuminate\Console\Command;

/**
 * Roda de hora em hora e decide internamente se é o momento de enviar.
 * Assim, mudar o horário na tela de configuração não exige alterar o agendador.
 */
class SendCampaignReminders extends Command
{
    protected $signature = 'campaigns:send-reminders
        {--campaign= : Limita a execução a uma campanha}
        {--force : Ignora a checagem de horário e dia da semana}
        {--dry-run : Apenas lista quem receberia, sem enviar}';

    protected $description = 'Envia lembretes de parcelas em atraso das campanhas conforme a configuração de cada uma';

    public function handle(CampaignReminderService $reminders): int
    {
        $campaigns = Campaign::query()
            ->where('status', Campaign::STATUS_ATIVA)
            ->when($this->option('campaign'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('name')
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('Nenhuma campanha ativa.');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $settings = $campaign->reminderSettings();

            if (! $settings->isActiveFor($campaign)) {
                continue;
            }

            if (! $this->option('force') && ! $this->isSendWindow($settings)) {
                continue;
            }

            $this->runFor($campaign, $settings, $reminders, CampaignReminderLog::TYPE_ATRASO);

            if ($settings->courtesy_enabled) {
                $this->runFor($campaign, $settings, $reminders, CampaignReminderLog::TYPE_CORTESIA);
            }
        }

        return self::SUCCESS;
    }

    private function runFor(
        Campaign $campaign,
        CampaignReminderSetting $settings,
        CampaignReminderService $reminders,
        string $type
    ): void {
        $label = $type === CampaignReminderLog::TYPE_CORTESIA ? 'cortesia' : 'atraso';

        $result = $reminders->runBatch(
            $campaign,
            $settings,
            $type,
            function ($sponsor, $outcome) use ($label) {
                $this->line("  [{$label}] {$sponsor->name}: {$outcome['status']}"
                    . ($outcome['reason'] ? ' — ' . $outcome['reason'] : ''));
            },
            (bool) $this->option('dry-run')
        );

        $this->info("Campanha \"{$campaign->name}\" ({$label}): {$result['sent']} enviado(s), "
            . "{$result['failed']} falha(s), {$result['skipped']} pulado(s).");

        if ($result['aborted']) {
            $this->warn('  Interrompido: ' . $result['aborted']);
        }
    }

    /**
     * A hora e o dia batem com o configurado? A janela de silêncio (8h–20h)
     * é validada junto, e não é configurável.
     */
    private function isSendWindow(CampaignReminderSetting $settings): bool
    {
        $now = now();

        if (! $settings->sendHourAllowed()) {
            return false;
        }

        if (! in_array((int) $now->isoWeekday(), $settings->weekdays(), true)) {
            return false;
        }

        return (int) $now->hour === $settings->sendHour();
    }
}
