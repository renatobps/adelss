<?php

namespace App\Services;

use App\Models\InstagramSetting;
use App\Models\MediaFile;
use App\Models\ScheduledPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class InstagramService
{
    private const GRAPH_BASE = 'https://graph.facebook.com/v21.0';

    public function getAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => config('services.instagram.app_id'),
            'redirect_uri' => route('midia.instagram.callback'),
            'scope' => 'instagram_basic,instagram_content_publish,pages_show_list,pages_read_engagement,business_management',
            'response_type' => 'code',
            'state' => csrf_token(),
        ]);

        return 'https://www.facebook.com/v21.0/dialog/oauth?' . $params;
    }

    public function handleCallback(string $code): InstagramSetting
    {
        $short = Http::asForm()->post(self::GRAPH_BASE . '/oauth/access_token', [
            'client_id' => config('services.instagram.app_id'),
            'client_secret' => config('services.instagram.app_secret'),
            'redirect_uri' => route('midia.instagram.callback'),
            'code' => $code,
        ])->json();

        if (empty($short['access_token'])) {
            throw new RuntimeException($short['error']['message'] ?? 'Falha ao obter token do Instagram/Facebook.');
        }

        $long = Http::get(self::GRAPH_BASE . '/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.instagram.app_id'),
            'client_secret' => config('services.instagram.app_secret'),
            'fb_exchange_token' => $short['access_token'],
        ])->json();

        $userToken = $long['access_token'] ?? $short['access_token'];
        $expiresIn = (int) ($long['expires_in'] ?? 5184000);

        $pages = Http::get(self::GRAPH_BASE . '/me/accounts', [
            'access_token' => $userToken,
            'fields' => 'id,name,access_token,instagram_business_account',
        ])->json();

        $page = collect($pages['data'] ?? [])->first(fn ($p) => !empty($p['instagram_business_account']['id']));
        if (!$page) {
            throw new RuntimeException('Nenhuma Página do Facebook com conta Instagram Business/Creator foi encontrada.');
        }

        $settings = InstagramSetting::current();
        $settings->fill([
            'facebook_page_id' => $page['id'],
            'instagram_business_account_id' => $page['instagram_business_account']['id'],
            'access_token' => $page['access_token'] ?? $userToken,
            'token_expires_at' => now()->addSeconds($expiresIn),
            'connected_by' => auth()->id(),
            'connected_at' => now(),
        ])->save();

        return $settings->fresh();
    }

    public function disconnect(): void
    {
        InstagramSetting::current()->update([
            'instagram_business_account_id' => null,
            'facebook_page_id' => null,
            'access_token' => null,
            'token_expires_at' => null,
            'connected_by' => null,
            'connected_at' => null,
        ]);
    }

    /**
     * @return array{token: string, expiring_soon: bool}
     */
    public function getValidAccessToken(): array
    {
        $settings = InstagramSetting::current();
        if (!$settings->isConnected()) {
            throw new RuntimeException('Instagram não está conectado.');
        }

        $expiringSoon = $settings->isTokenExpiringSoon(7);
        if ($expiringSoon) {
            Log::warning('Token do Instagram próximo da expiração.', [
                'expires_at' => optional($settings->token_expires_at)->toDateTimeString(),
            ]);
        }

        if ($settings->token_expires_at && $settings->token_expires_at->isPast()) {
            throw new RuntimeException('Token do Instagram expirado. Reconecte a conta nas configurações de Mídia.');
        }

        return [
            'token' => (string) $settings->access_token,
            'expiring_soon' => $expiringSoon,
        ];
    }

    public function createMediaContainer(string $imageUrl, string $caption): string
    {
        $settings = InstagramSetting::current();
        $token = $this->getValidAccessToken()['token'];

        $response = Http::asForm()->post(self::GRAPH_BASE . '/' . $settings->instagram_business_account_id . '/media', [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $token,
        ])->json();

        if (empty($response['id'])) {
            throw new RuntimeException($response['error']['message'] ?? 'Falha ao criar container de mídia no Instagram.');
        }

        return (string) $response['id'];
    }

    public function checkContainerStatus(string $containerId): string
    {
        $token = $this->getValidAccessToken()['token'];
        $response = Http::get(self::GRAPH_BASE . '/' . $containerId, [
            'fields' => 'status_code',
            'access_token' => $token,
        ])->json();

        return (string) ($response['status_code'] ?? 'UNKNOWN');
    }

    public function publishContainer(string $containerId): string
    {
        $settings = InstagramSetting::current();
        $token = $this->getValidAccessToken()['token'];

        $response = Http::asForm()->post(
            self::GRAPH_BASE . '/' . $settings->instagram_business_account_id . '/media_publish',
            [
                'creation_id' => $containerId,
                'access_token' => $token,
            ]
        )->json();

        if (empty($response['id'])) {
            throw new RuntimeException($response['error']['message'] ?? 'Falha ao publicar no Instagram.');
        }

        return (string) $response['id'];
    }

    /**
     * Gera URL pública temporária a partir do Drive ou do upload local.
     */
    public function resolvePublicImageUrl(ScheduledPost $post, GoogleDriveService $drive): string
    {
        if ($post->image_path) {
            $relative = ltrim(str_replace('\\', '/', $post->image_path), '/');
            if (str_starts_with($relative, 'storage/')) {
                $relative = substr($relative, 8);
            }

            return url('/storage/' . $relative);
        }

        if (!$post->media_file_id) {
            throw new RuntimeException('Post sem imagem associada.');
        }

        /** @var MediaFile $media */
        $media = $post->mediaFile;
        if (!$media) {
            throw new RuntimeException('Arquivo de mídia não encontrado.');
        }

        $contents = $drive->downloadContents($media->google_drive_file_id);
        $ext = pathinfo($media->original_filename, PATHINFO_EXTENSION) ?: 'jpg';
        $tempName = 'instagram-temp/' . Str::uuid() . '.' . strtolower($ext);
        Storage::disk('public')->put($tempName, $contents);

        $post->forceFill(['image_path' => $tempName])->save();

        return url('/storage/' . $tempName);
    }

    public function cleanupTempImage(ScheduledPost $post): void
    {
        if (!$post->image_path || !$post->media_file_id) {
            return;
        }

        if (str_starts_with($post->image_path, 'instagram-temp/')) {
            Storage::disk('public')->delete($post->image_path);
            $post->forceFill(['image_path' => null])->save();
        }
    }

    public function waitUntilContainerReady(string $containerId, int $maxAttempts = 10, int $sleepSeconds = 3): void
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $status = $this->checkContainerStatus($containerId);
            if ($status === 'FINISHED') {
                return;
            }
            if (in_array($status, ['ERROR', 'EXPIRED'], true)) {
                throw new RuntimeException('Container Instagram com status: ' . $status);
            }
            sleep($sleepSeconds);
        }

        throw new RuntimeException('Timeout aguardando container do Instagram ficar pronto.');
    }
}
