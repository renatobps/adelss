<?php

namespace App\Console\Commands;

use App\Models\ScheduledPostDestination;
use App\Models\User;
use App\Services\InstagramService;
use App\Services\NotificacaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveExpiredInstagramPosts extends Command
{
    protected $signature = 'midia:remove-expired-instagram-posts';

    protected $description = 'Arquiva Stories expiradas e tenta remover Feed/Reels com remoção agendada';

    public function handle(InstagramService $instagram, NotificacaoService $notificacao): int
    {
        $due = ScheduledPostDestination::query()
            ->with('post')
            ->where('removal_status', ScheduledPostDestination::REMOVAL_SCHEDULED)
            ->whereNotNull('remove_at')
            ->where('remove_at', '<=', now())
            ->orderBy('remove_at')
            ->limit(30)
            ->get();

        if ($due->isEmpty()) {
            $this->info('Nenhuma remoção pendente.');

            return self::SUCCESS;
        }

        foreach ($due as $destination) {
            try {
                if ($destination->isStories()) {
                    $this->archiveStoriesLocally($destination);
                    $this->line("Stories #{$destination->id}: arquivada localmente.");
                    continue;
                }

                if ($destination->isPermanentDestination()) {
                    $this->removePermanentViaApi($destination, $instagram, $notificacao);
                }
            } catch (\Throwable $e) {
                Log::error('Falha não tratada na remoção Instagram', [
                    'destination_id' => $destination->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Destino #{$destination->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function archiveStoriesLocally(ScheduledPostDestination $destination): void
    {
        $destination->update([
            'removed_at' => now(),
            'removal_status' => ScheduledPostDestination::REMOVAL_DONE,
            'removal_error' => null,
        ]);
    }

    private function removePermanentViaApi(
        ScheduledPostDestination $destination,
        InstagramService $instagram,
        NotificacaoService $notificacao
    ): void {
        $mediaId = (string) ($destination->instagram_media_id ?? '');

        try {
            if ($mediaId === '') {
                throw new \RuntimeException('Sem instagram_media_id para excluir no Instagram.');
            }

            $instagram->deleteMedia($mediaId);

            $destination->update([
                'removed_at' => now(),
                'removal_status' => ScheduledPostDestination::REMOVAL_DONE,
                'removal_error' => null,
            ]);
            $this->info("{$destination->destination_label} #{$destination->id}: removida via API.");
        } catch (\Throwable $e) {
            $destination->update([
                'removal_status' => ScheduledPostDestination::REMOVAL_ERROR,
                'removal_error' => $e->getMessage(),
            ]);

            Log::warning('Erro ao remover Feed/Reels no Instagram', [
                'destination_id' => $destination->id,
                'post_id' => $destination->scheduled_post_id,
                'error' => $e->getMessage(),
            ]);

            $this->warn("{$destination->destination_label} #{$destination->id}: erro_remocao — sem novas tentativas.");
            $this->notifyAdminRemovalFailure($notificacao, $destination, $e->getMessage());
        }
    }

    private function notifyAdminRemovalFailure(
        NotificacaoService $notificacao,
        ScheduledPostDestination $destination,
        string $apiError
    ): void {
        $post = $destination->post;
        $when = optional($destination->published_at)->format('d/m/Y H:i')
            ?: optional($post?->scheduled_for)->format('d/m/Y H:i')
            ?: '—';

        $message = "⚠️ *ADELSS — Instagram*\n"
            . "Não foi possível remover automaticamente a publicação de *{$destination->destination_label}* de {$when}.\n"
            . "Remova manualmente pelo aplicativo do Instagram.\n"
            . "Detalhe: {$apiError}";

        $numbers = [];
        if ($configNumber = config('whatsapp.number')) {
            $numbers[] = $configNumber;
        }
        foreach (User::query()->where('is_admin', true)->whereNotNull('phone')->pluck('phone') as $phone) {
            $numbers[] = $phone;
        }

        foreach (array_unique(array_filter($numbers)) as $phone) {
            try {
                $notificacao->enviarParaTelefone((string) $phone, $message);
            } catch (\Throwable $e) {
                Log::warning('Falha ao alertar admin sobre remoção Instagram: ' . $e->getMessage());
            }
        }
    }
}
