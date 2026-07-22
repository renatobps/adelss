<?php

namespace App\Console\Commands;

use App\Models\ScheduledPost;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Services\InstagramService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledInstagramPosts extends Command
{
    protected $signature = 'midia:publish-instagram-posts';

    protected $description = 'Publica posts do Instagram agendados cujo horário já chegou';

    public function handle(InstagramService $instagram, GoogleDriveService $drive, WhatsAppService $whatsapp): int
    {
        $posts = ScheduledPost::query()
            ->with('mediaFile')
            ->where('status', ScheduledPost::STATUS_SCHEDULED)
            ->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')
            ->limit(10)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('Nenhum post agendado para publicar.');

            return self::SUCCESS;
        }

        foreach ($posts as $post) {
            $this->line("Publicando #{$post->id}...");
            $post->update(['status' => ScheduledPost::STATUS_PUBLISHING, 'error_message' => null]);

            try {
                $imageUrl = $instagram->resolvePublicImageUrl($post, $drive);
                $containerId = $instagram->createMediaContainer($imageUrl, (string) $post->caption);
                $instagram->waitUntilContainerReady($containerId);
                $mediaId = $instagram->publishContainer($containerId);

                $post->update([
                    'status' => ScheduledPost::STATUS_PUBLISHED,
                    'instagram_media_id' => $mediaId,
                    'published_at' => now(),
                    'error_message' => null,
                ]);

                $instagram->cleanupTempImage($post->fresh());
                $this->info("Post #{$post->id} publicado ({$mediaId}).");
            } catch (\Throwable $e) {
                Log::error('Falha ao publicar Instagram', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);

                $post->update([
                    'status' => ScheduledPost::STATUS_ERROR,
                    'error_message' => $e->getMessage(),
                ]);

                $this->error("Post #{$post->id}: " . $e->getMessage());
                $this->notifyAdmins($whatsapp, $post, $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function notifyAdmins(WhatsAppService $whatsapp, ScheduledPost $post, string $error): void
    {
        $message = "⚠️ *ADELSS — Instagram*\n"
            . "Falha ao publicar post agendado #{$post->id}.\n"
            . 'Horário: ' . optional($post->scheduled_for)->format('d/m/Y H:i') . "\n"
            . 'Erro: ' . $error;

        $number = config('whatsapp.number');
        if ($number) {
            try {
                $whatsapp->enviarMensagem($number, $message);
            } catch (\Throwable $e) {
                Log::warning('Não foi possível avisar admin via WhatsApp: ' . $e->getMessage());
            }
        }

        $admins = User::query()
            ->where('is_admin', true)
            ->whereNotNull('phone')
            ->get();

        foreach ($admins as $admin) {
            try {
                if ($admin->phone) {
                    $whatsapp->enviarMensagem($admin->phone, $message);
                }
            } catch (\Throwable $e) {
                // ignora falha individual
            }
        }
    }
}
