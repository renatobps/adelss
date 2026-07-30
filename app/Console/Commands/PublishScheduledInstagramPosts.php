<?php

namespace App\Console\Commands;

use App\Models\ScheduledPost;
use App\Models\ScheduledPostDestination;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Services\InstagramService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PublishScheduledInstagramPosts extends Command
{
    protected $signature = 'midia:publish-instagram-posts';

    protected $description = 'Publica posts agendados (Instagram Feed/Reels/Stories e Grupo WhatsApp)';

    public function handle(InstagramService $instagram, GoogleDriveService $drive, WhatsAppService $whatsapp): int
    {
        try {
            $posts = ScheduledPost::query()
                ->with(['mediaFile', 'destinations'])
                ->whereIn('status', [
                    ScheduledPost::STATUS_SCHEDULED,
                    ScheduledPost::STATUS_PUBLISHING, // retoma posts travados por falha anterior
                ])
                ->where('scheduled_for', '<=', now())
                ->whereHas('destinations', fn ($q) => $q->whereIn('status', [
                    ScheduledPostDestination::STATUS_PENDING,
                    ScheduledPostDestination::STATUS_PUBLISHING,
                ]))
                ->orderBy('scheduled_for')
                ->limit(10)
                ->get();

            if ($posts->isEmpty()) {
                $this->info('Nenhum post agendado para publicar.');

                return self::SUCCESS;
            }

            foreach ($posts as $post) {
                try {
                    $this->line("Publicando #{$post->id}...");
                    $post->update(['status' => ScheduledPost::STATUS_PUBLISHING]);

                    try {
                        $mediaUrl = $instagram->resolvePublicMediaUrl($post, $drive);
                    } catch (\Throwable $e) {
                        $this->failAllPending($post, $e->getMessage());
                        $post->recalculateStatus();
                        $this->notifyAdmins($whatsapp, $post->fresh(['destinations']));
                        $this->error("Post #{$post->id}: " . $e->getMessage());
                        continue;
                    }

                    // PENDING é sempre processado; PUBLISHING só é retomado se estiver
                    // travado há 15+ min (evita reenvio duplicado quando outro processo
                    // ainda está publicando este mesmo destino).
                    $stuckThreshold = now()->subMinutes(15);
                    $pending = $post->destinations
                        ->filter(function (ScheduledPostDestination $d) use ($stuckThreshold) {
                            if ($d->status === ScheduledPostDestination::STATUS_PENDING) {
                                return true;
                            }

                            return $d->status === ScheduledPostDestination::STATUS_PUBLISHING
                                && $d->updated_at !== null
                                && $d->updated_at->lte($stuckThreshold);
                        })
                        ->values();

                    $instagramPending = $pending->filter(fn (ScheduledPostDestination $d) => $d->isInstagram())->values();
                    $whatsappPending = $pending->filter(fn (ScheduledPostDestination $d) => $d->isWhatsApp())->values();

                    $pendingKeys = $instagramPending->pluck('destination')->values()->all();
                    $handledIds = [];

                    $isExactlyFeedAndReels = count($pendingKeys) === 2
                        && in_array(ScheduledPostDestination::DEST_FEED, $pendingKeys, true)
                        && in_array(ScheduledPostDestination::DEST_REELS, $pendingKeys, true);

                    if ($isExactlyFeedAndReels) {
                        $feed = $instagramPending->firstWhere('destination', ScheduledPostDestination::DEST_FEED);
                        $reels = $instagramPending->firstWhere('destination', ScheduledPostDestination::DEST_REELS);
                        $this->publishCombinedFeedReels($instagram, $post, $feed, $reels, $mediaUrl);
                        $handledIds = [$feed->id, $reels->id];
                    }

                    foreach ($instagramPending as $destination) {
                        if (in_array($destination->id, $handledIds, true)) {
                            continue;
                        }
                        $this->publishDestination($instagram, $post, $destination, $mediaUrl);
                    }

                    foreach ($whatsappPending as $destination) {
                        $this->publishWhatsAppGroup($whatsapp, $post, $destination, $mediaUrl);
                    }

                    $status = $post->recalculateStatus();
                    $fresh = $post->fresh(['destinations']);

                    if (in_array($status, [ScheduledPost::STATUS_DONE, ScheduledPost::STATUS_PARTIAL], true)
                        || $status === ScheduledPost::STATUS_ERROR) {
                        if ($fresh->destinations->whereIn('status', [
                            ScheduledPostDestination::STATUS_PENDING,
                            ScheduledPostDestination::STATUS_PUBLISHING,
                        ])->isEmpty()) {
                            $instagram->cleanupTempImage($fresh);
                        }
                    }

                    if (in_array($status, [ScheduledPost::STATUS_ERROR, ScheduledPost::STATUS_PARTIAL], true)) {
                        $this->notifyAdmins($whatsapp, $fresh);
                    }

                    $this->info("Post #{$post->id} finalizado com status: {$status}");
                } catch (\Throwable $e) {
                    Log::error('Falha não tratada ao publicar post agendado', [
                        'post_id' => $post->id,
                        'destination' => null,
                        'error' => $e->getMessage(),
                        'exception' => $e::class,
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $this->error("Post #{$post->id}: " . $e->getMessage());

                    try {
                        $this->failAllPending($post, $e->getMessage());
                        $post->recalculateStatus();
                    } catch (\Throwable $inner) {
                        Log::error('Falha ao marcar post agendado após erro', [
                            'post_id' => $post->id,
                            'error' => $inner->getMessage(),
                        ]);
                    }
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Falha fatal no comando midia:publish-instagram-posts', [
                'post_id' => null,
                'destination' => null,
                'error' => $e->getMessage(),
                'exception' => $e::class,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function publishWhatsAppGroup(
        WhatsAppService $whatsapp,
        ScheduledPost $post,
        ScheduledPostDestination $destination,
        string $mediaUrl
    ): void {
        // Claim atômico: apenas um processo consegue assumir este destino.
        // Se outro processo já o marcou como PUBLISHING recentemente, não reenvia
        // (evita a mesma mensagem duas vezes no grupo).
        $claimed = ScheduledPostDestination::query()
            ->whereKey($destination->id)
            ->where(function ($q) {
                $q->where('status', ScheduledPostDestination::STATUS_PENDING)
                    ->orWhere(function ($q2) {
                        $q2->where('status', ScheduledPostDestination::STATUS_PUBLISHING)
                            ->where('updated_at', '<=', now()->subMinutes(15));
                    });
            })
            ->update([
                'status' => ScheduledPostDestination::STATUS_PUBLISHING,
                'error_message' => null,
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            $this->warn("Post #{$post->id}: destino WhatsApp já está sendo publicado por outro processo — ignorando.");

            return;
        }

        try {
            if (!$destination->isWhatsAppGroup()) {
                throw new \RuntimeException('Destino WhatsApp inválido.');
            }
            if (!filled($destination->target_id)) {
                throw new \RuntimeException('Grupo do WhatsApp não informado (target_id).');
            }

            $mediaType = $post->isVideo() ? 'video' : 'image';
            $result = $whatsapp->sendMediaToGroup(
                (string) $destination->target_id,
                $mediaUrl,
                (string) $post->caption,
                $mediaType
            );

            if (!($result['success'] ?? false)) {
                throw new \RuntimeException($result['error'] ?? 'Falha ao enviar mídia ao grupo do WhatsApp.');
            }

            $publishedAt = now();
            $destination->update(array_merge([
                'status' => ScheduledPostDestination::STATUS_PUBLISHED,
                'published_at' => $publishedAt,
                'error_message' => null,
            ], $destination->removalPayloadForPublished($publishedAt)));
        } catch (\Throwable $e) {
            Log::error('Falha WhatsApp grupo', [
                'post_id' => $post->id,
                'destination' => $destination->destination,
                'target_id' => $destination->target_id,
                'error' => $e->getMessage(),
            ]);
            $destination->update([
                'status' => ScheduledPostDestination::STATUS_ERROR,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function publishCombinedFeedReels(
        InstagramService $instagram,
        ScheduledPost $post,
        ScheduledPostDestination $feed,
        ScheduledPostDestination $reels,
        string $mediaUrl
    ): void {
        $feed->update(['status' => ScheduledPostDestination::STATUS_PUBLISHING, 'error_message' => null]);
        $reels->update(['status' => ScheduledPostDestination::STATUS_PUBLISHING, 'error_message' => null]);

        try {
            if (!$post->isVideo()) {
                throw new \RuntimeException('Reels + Feed combinados exigem vídeo.');
            }

            $containerId = $instagram->createMediaContainer(
                $mediaUrl,
                (string) $post->caption,
                'REELS',
                true
            );
            $instagram->waitUntilContainerReady($containerId);
            $mediaId = $instagram->publishContainer($containerId);

            $publishedAt = now();
            foreach ([$feed, $reels] as $dest) {
                $dest->update(array_merge([
                    'status' => ScheduledPostDestination::STATUS_PUBLISHED,
                    'instagram_media_id' => $mediaId,
                    'published_at' => $publishedAt,
                    'error_message' => null,
                ], $dest->removalPayloadForPublished($publishedAt)));
            }
        } catch (\Throwable $e) {
            Log::error('Falha Instagram Feed+Reels', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            foreach ([$feed, $reels] as $dest) {
                $dest->update([
                    'status' => ScheduledPostDestination::STATUS_ERROR,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function publishDestination(
        InstagramService $instagram,
        ScheduledPost $post,
        ScheduledPostDestination $destination,
        string $mediaUrl
    ): void {
        $destination->update([
            'status' => ScheduledPostDestination::STATUS_PUBLISHING,
            'error_message' => null,
        ]);

        try {
            $mediaType = $this->mediaTypeFor($destination->destination, $post);
            $containerId = $instagram->createMediaContainer(
                $mediaUrl,
                (string) $post->caption,
                $mediaType,
                false
            );
            $instagram->waitUntilContainerReady($containerId);
            $mediaId = $instagram->publishContainer($containerId);

            $publishedAt = now();
            $destination->update(array_merge([
                'status' => ScheduledPostDestination::STATUS_PUBLISHED,
                'instagram_media_id' => $mediaId,
                'published_at' => $publishedAt,
                'error_message' => null,
            ], $destination->removalPayloadForPublished($publishedAt)));
        } catch (\Throwable $e) {
            Log::error('Falha Instagram destino', [
                'post_id' => $post->id,
                'destination' => $destination->destination,
                'error' => $e->getMessage(),
            ]);
            $destination->update([
                'status' => ScheduledPostDestination::STATUS_ERROR,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function mediaTypeFor(string $destination, ScheduledPost $post): string
    {
        return match ($destination) {
            ScheduledPostDestination::DEST_REELS => 'REELS',
            ScheduledPostDestination::DEST_STORIES => 'STORIES',
            default => $post->isVideo() ? 'VIDEO' : 'IMAGE',
        };
    }

    private function failAllPending(ScheduledPost $post, string $message): void
    {
        $post->destinations()
            ->whereIn('status', [
                ScheduledPostDestination::STATUS_PENDING,
                ScheduledPostDestination::STATUS_PUBLISHING,
            ])
            ->update([
                'status' => ScheduledPostDestination::STATUS_ERROR,
                'error_message' => $message,
            ]);
    }

    private function notifyAdmins(WhatsAppService $whatsapp, ScheduledPost $post): void
    {
        /** @var Collection<int, ScheduledPostDestination> $failed */
        $failed = $post->destinations->where('status', ScheduledPostDestination::STATUS_ERROR);
        if ($failed->isEmpty()) {
            return;
        }

        $lines = $failed->map(function (ScheduledPostDestination $d) {
            return '• ' . $d->destination_label . ': ' . ($d->error_message ?: 'erro desconhecido');
        })->implode("\n");

        $message = "⚠️ *ADELSS — Mídia*\n"
            . "Falha ao publicar post #{$post->id}.\n"
            . 'Horário: ' . optional($post->scheduled_for)->format('d/m/Y H:i') . "\n"
            . "Destinos com erro:\n{$lines}";

        $number = config('whatsapp.number');
        if ($number) {
            try {
                $whatsapp->enviarMensagem($number, $message);
            } catch (\Throwable $e) {
                Log::warning('Não foi possível avisar admin via WhatsApp: ' . $e->getMessage());
            }
        }

        $admins = User::query()->where('is_admin', true)->whereNotNull('phone')->get();
        foreach ($admins as $admin) {
            try {
                if ($admin->phone) {
                    $whatsapp->enviarMensagem($admin->phone, $message);
                }
            } catch (\Throwable) {
                // ignore
            }
        }
    }
}
