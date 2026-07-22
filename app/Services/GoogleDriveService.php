<?php

namespace App\Services;

use App\Models\GoogleDriveSetting;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Oauth2;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleDriveService
{
    public function makeClient(?GoogleDriveSetting $settings = null): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google_drive.client_id'));
        $client->setClientSecret(config('services.google_drive.client_secret'));
        $client->setRedirectUri(route('midia.google.callback'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([
            Drive::DRIVE_FILE,
            'https://www.googleapis.com/auth/userinfo.email',
            'openid',
        ]);

        $settings ??= GoogleDriveSetting::current();
        if ($settings->isConnected()) {
            $token = [
                'access_token' => $settings->access_token,
                'refresh_token' => $settings->refresh_token,
                'expires_in' => $settings->token_expires_at
                    ? max(0, $settings->token_expires_at->getTimestamp() - time())
                    : 3600,
                'created' => $settings->token_expires_at
                    ? $settings->token_expires_at->getTimestamp() - 3600
                    : time(),
            ];
            $client->setAccessToken($token);
        }

        return $client;
    }

    public function getAuthUrl(): string
    {
        return $this->makeClient()->createAuthUrl();
    }

    public function handleCallback(string $code): GoogleDriveSetting
    {
        $client = $this->makeClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new RuntimeException($token['error_description'] ?? $token['error']);
        }

        $client->setAccessToken($token);
        $oauth = new Oauth2($client);
        $userInfo = $oauth->userinfo->get();

        $settings = GoogleDriveSetting::current();
        $settings->fill([
            'connected_account_email' => $userInfo->email ?? null,
            'access_token' => $token['access_token'] ?? null,
            'refresh_token' => $token['refresh_token'] ?? $settings->refresh_token,
            'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
            'connected_by' => Auth::id(),
            'connected_at' => now(),
        ]);

        if (!$settings->root_folder_id) {
            $settings->root_folder_id = $this->ensureRootFolder($client);
        }

        $settings->save();

        return $settings->fresh();
    }

    public function disconnect(): void
    {
        $settings = GoogleDriveSetting::current();
        try {
            if ($settings->access_token) {
                $this->makeClient($settings)->revokeToken($settings->access_token);
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao revogar token Google Drive: ' . $e->getMessage());
        }

        $settings->update([
            'connected_account_email' => null,
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'root_folder_id' => null,
            'connected_by' => null,
            'connected_at' => null,
        ]);
    }

    public function getValidAccessToken(): string
    {
        $settings = GoogleDriveSetting::current();
        if (!$settings->isConnected()) {
            throw new RuntimeException('Google Drive não está conectado.');
        }

        $client = $this->makeClient($settings);

        if ($client->isAccessTokenExpired()) {
            if (!$settings->refresh_token) {
                throw new RuntimeException('Refresh token do Google Drive ausente. Reconecte a conta.');
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($settings->refresh_token);
            if (isset($newToken['error'])) {
                throw new RuntimeException($newToken['error_description'] ?? $newToken['error']);
            }

            $settings->update([
                'access_token' => $newToken['access_token'] ?? $settings->access_token,
                'refresh_token' => $newToken['refresh_token'] ?? $settings->refresh_token,
                'token_expires_at' => now()->addSeconds((int) ($newToken['expires_in'] ?? 3600)),
            ]);

            $client->setAccessToken($newToken);
        }

        $token = $client->getAccessToken();

        return is_array($token) ? (string) ($token['access_token'] ?? '') : (string) $token;
    }

    public function drive(): Drive
    {
        $this->getValidAccessToken();
        $settings = GoogleDriveSetting::current();

        return new Drive($this->makeClient($settings));
    }

    public function ensureRootFolder(?GoogleClient $client = null): string
    {
        $client ??= $this->makeClient();
        $drive = new Drive($client);
        $settings = GoogleDriveSetting::current();

        if ($settings->root_folder_id) {
            return $settings->root_folder_id;
        }

        $name = config('services.google_drive.root_folder_name', 'ADELSS');
        // Com escopo drive.file, listFiles PRECISA restringir a um parent.
        // Na criação da raiz do app, o parent é "root" do Drive.
        $folderId = 'root';
        $query = sprintf(
            "'%s' in parents and trashed = false and mimeType = 'application/vnd.google-apps.folder' and name = '%s'",
            $folderId,
            addslashes($name)
        );

        $existing = $drive->files->listFiles([
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'pageSize' => 1,
        ]);

        if (count($existing->getFiles()) > 0) {
            return $existing->getFiles()[0]->getId();
        }

        $folder = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$folderId],
        ]);

        $created = $drive->files->create($folder, ['fields' => 'id']);

        return $created->getId();
    }

    /**
     * Lista arquivos/pastas dentro de uma pasta específica do Drive.
     * Sempre filtra por parent — obrigatório com escopo drive.file.
     *
     * @return list<DriveFile>
     */
    public function listFilesInFolder(string $folderId, ?string $extraQuery = null): array
    {
        $q = "'{$folderId}' in parents and trashed = false";
        if ($extraQuery) {
            $q .= ' and (' . $extraQuery . ')';
        }

        $result = $this->drive()->files->listFiles([
            'q' => $q,
            'spaces' => 'drive',
            'fields' => 'files(id, name, mimeType, size, modifiedTime, thumbnailLink, iconLink)',
            'pageSize' => 100,
        ]);

        return $result->getFiles() ?? [];
    }

    /**
     * Metadados de miniatura/ícone de um arquivo no Drive.
     *
     * @return array{thumbnailLink: ?string, iconLink: ?string, mimeType: ?string}
     */
    public function getFilePreviewLinks(string $fileId): array
    {
        $file = $this->drive()->files->get($fileId, [
            'fields' => 'id, mimeType, thumbnailLink, iconLink',
        ]);

        return [
            'thumbnailLink' => $file->getThumbnailLink(),
            'iconLink' => $file->getIconLink(),
            'mimeType' => $file->getMimeType(),
        ];
    }

    /**
     * Baixa bytes da miniatura (ou ícone) autenticado — thumbnailLink do Drive
     * costuma exigir o token e não funciona como <img src> público.
     *
     * @return array{contents: string, contentType: string}|null
     */
    public function downloadThumbnailContents(string $fileId): ?array
    {
        $meta = $this->getFilePreviewLinks($fileId);
        $token = $this->getValidAccessToken();

        foreach (array_filter([$meta['thumbnailLink'], $meta['iconLink']]) as $url) {
            $fetched = $this->httpGetBinary($url, $token);
            if ($fetched !== null) {
                return $fetched;
            }
        }

        // Fallback: arquivo de imagem completo (mesmo custo do preview).
        if (str_starts_with((string) $meta['mimeType'], 'image/')) {
            return [
                'contents' => $this->downloadContents($fileId),
                'contentType' => (string) $meta['mimeType'],
            ];
        }

        return null;
    }

    /**
     * @return array{contents: string, contentType: string}|null
     */
    private function httpGetBinary(string $url, string $accessToken): ?array
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                ],
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($body === false || $status < 200 || $status >= 300 || $body === '') {
                // Tenta sem Authorization (alguns iconLink/googleusercontent são públicos).
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 20,
                ]);
                $body = curl_exec($ch);
                $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                curl_close($ch);
            }

            if ($body === false || $status < 200 || $status >= 300 || $body === '') {
                return null;
            }

            $contentType = explode(';', $contentType)[0] ?: 'image/jpeg';

            return [
                'contents' => $body,
                'contentType' => $contentType,
            ];
        } catch (\Throwable $e) {
            Log::warning('Falha ao baixar miniatura Drive: ' . $e->getMessage());

            return null;
        }
    }

    public function createFolder(string $name, ?string $parentDriveId = null): string
    {
        $drive = $this->drive();
        $parent = $parentDriveId ?: GoogleDriveSetting::current()->root_folder_id ?: $this->ensureRootFolder();

        $folder = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parent],
        ]);

        $created = $drive->files->create($folder, ['fields' => 'id']);

        return $created->getId();
    }

    /**
     * @return array{id: string, name: string, mime: string, size: int}
     */
    public function uploadFile(UploadedFile|string $file, string $name, ?string $parentDriveId = null): array
    {
        $drive = $this->drive();
        $parent = $parentDriveId ?: GoogleDriveSetting::current()->root_folder_id ?: $this->ensureRootFolder();

        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $size = $file->getSize() ?: 0;
        } else {
            $path = $file;
            $mime = mime_content_type($path) ?: 'application/octet-stream';
            $size = filesize($path) ?: 0;
        }

        $meta = new DriveFile([
            'name' => $name,
            'parents' => [$parent],
        ]);

        $created = $drive->files->create($meta, [
            'data' => file_get_contents($path),
            'mimeType' => $mime,
            'uploadType' => 'multipart',
            'fields' => 'id, name, mimeType, size',
        ]);

        return [
            'id' => $created->getId(),
            'name' => $created->getName(),
            'mime' => $created->getMimeType() ?: $mime,
            'size' => (int) ($created->getSize() ?: $size),
        ];
    }

    public function downloadContents(string $fileId): string
    {
        $response = $this->drive()->files->get($fileId, ['alt' => 'media']);

        return $response->getBody()->getContents();
    }

    public function getTemporaryDownloadUrl(string $fileId): string
    {
        $drive = $this->drive();
        $drive->permissions->create($fileId, new \Google\Service\Drive\Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]));

        $file = $drive->files->get($fileId, ['fields' => 'webContentLink,webViewLink']);

        return $file->getWebContentLink() ?: $file->getWebViewLink();
    }

    public function deleteFile(string $fileId): void
    {
        $this->drive()->files->delete($fileId);
    }

    public function moveFile(string $fileId, string $newParentId, ?string $oldParentId = null): void
    {
        $params = ['addParents' => $newParentId, 'fields' => 'id, parents'];
        if ($oldParentId) {
            $params['removeParents'] = $oldParentId;
        }

        $this->drive()->files->update($fileId, new DriveFile(), $params);
    }
}
