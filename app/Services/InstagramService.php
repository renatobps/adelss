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
    /** OAuth / API do fluxo "Instagram Login" (não Facebook Login). */
    private const AUTHORIZE_URL = 'https://www.instagram.com/oauth/authorize';
    private const TOKEN_URL = 'https://api.instagram.com/oauth/access_token';
    private const GRAPH_BASE = 'https://graph.instagram.com';

    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => config('services.instagram.app_id'),
            'redirect_uri' => route('midia.instagram.callback'),
            'response_type' => 'code',
            'scope' => 'instagram_business_basic,instagram_business_content_publish',
            'state' => csrf_token(),
        ];

        return self::AUTHORIZE_URL . '?' . http_build_query($params);
    }

    public function handleCallback(string $code): InstagramSetting
    {
        // A Meta às vezes anexa "#_" ao code no redirect
        $code = trim(str_replace('#_', '', $code));

        $shortResponse = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.instagram.app_id'),
            'client_secret' => config('services.instagram.app_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('midia.instagram.callback'),
            'code' => $code,
        ]);

        $short = $shortResponse->json() ?? [];
        Log::info('Instagram OAuth short-lived token response', [
            'status' => $shortResponse->status(),
            'payload' => $short,
        ]);

        // Formato comum: { access_token, user_id, permissions }
        // Em alguns casos: { data: [ { access_token, user_id, ... } ] }
        if (isset($short['data'][0]) && is_array($short['data'][0])) {
            $short = $short['data'][0];
        }

        if (empty($short['access_token'])) {
            $message = $short['error_message']
                ?? $short['error']['message']
                ?? $short['error_type']
                ?? 'Falha ao obter access_token do Instagram.';
            throw new RuntimeException($message);
        }

        $shortToken = (string) $short['access_token'];
        $userId = (string) ($short['user_id'] ?? '');
        $permissions = $short['permissions'] ?? null;

        // Token do /oauth/access_token é de curta duração (~1h).
        // Troca por long-lived (~60 dias) em graph.instagram.com.
        $longResponse = Http::get(self::GRAPH_BASE . '/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => config('services.instagram.app_secret'),
            'access_token' => $shortToken,
        ]);

        $long = $longResponse->json() ?? [];
        Log::info('Instagram OAuth long-lived token response', [
            'status' => $longResponse->status(),
            'payload' => $long,
            'short_user_id' => $userId,
            'short_permissions' => $permissions,
        ]);

        $accessToken = (string) ($long['access_token'] ?? $shortToken);
        $expiresIn = (int) ($long['expires_in'] ?? 3600);

        if (!$userId) {
            $me = Http::get(self::GRAPH_BASE . '/me', [
                'fields' => 'user_id,username,id',
                'access_token' => $accessToken,
            ])->json() ?? [];

            Log::info('Instagram /me after OAuth', ['payload' => $me]);
            $userId = (string) ($me['user_id'] ?? $me['id'] ?? '');
        }

        if ($userId === '') {
            throw new RuntimeException('Não foi possível obter o Instagram user_id após o OAuth.');
        }

        $settings = InstagramSetting::current();
        $settings->fill([
            'facebook_page_id' => null, // fluxo Instagram Login não usa Página do Facebook
            'instagram_business_account_id' => $userId,
            'access_token' => $accessToken,
            'token_expires_at' => now()->addSeconds(max(60, $expiresIn)),
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

            // Tenta refresh do long-lived (válido se ainda não expirou)
            try {
                $this->refreshLongLivedToken($settings);
                $settings->refresh();
                $expiringSoon = $settings->isTokenExpiringSoon(7);
            } catch (\Throwable $e) {
                Log::warning('Falha ao renovar token Instagram: ' . $e->getMessage());
            }
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

    private function refreshLongLivedToken(InstagramSetting $settings): void
    {
        $response = Http::get(self::GRAPH_BASE . '/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $settings->access_token,
        ]);

        $payload = $response->json() ?? [];
        Log::info('Instagram refresh long-lived token response', [
            'status' => $response->status(),
            'payload' => $payload,
        ]);

        if (empty($payload['access_token'])) {
            throw new RuntimeException($payload['error']['message'] ?? 'Falha ao renovar token do Instagram.');
        }

        $settings->update([
            'access_token' => $payload['access_token'],
            'token_expires_at' => now()->addSeconds((int) ($payload['expires_in'] ?? 5184000)),
        ]);
    }
}
