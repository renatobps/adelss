<?php

namespace App\Providers;

use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Masbug\Flysystem\GoogleDriveAdapter;
use League\Flysystem\Filesystem;

class GoogleDriveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {
            try {
                /** @var GoogleDriveService $service */
                $service = $app->make(GoogleDriveService::class);
                $settings = GoogleDriveSetting::current();

                if (!$settings->isConnected()) {
                    throw new \RuntimeException('Google Drive não conectado.');
                }

                $service->getValidAccessToken();
                $settings->refresh();

                $client = new GoogleClient();
                $client->setClientId(config('services.google_drive.client_id'));
                $client->setClientSecret(config('services.google_drive.client_secret'));
                $client->setAccessToken([
                    'access_token' => $settings->access_token,
                    'refresh_token' => $settings->refresh_token,
                    'expires_in' => $settings->token_expires_at
                        ? max(0, $settings->token_expires_at->getTimestamp() - time())
                        : 3600,
                ]);

                $drive = new Drive($client);
                $adapter = new GoogleDriveAdapter(
                    $drive,
                    $settings->root_folder_id ?: 'root',
                    $config['options'] ?? []
                );

                return new \Illuminate\Filesystem\FilesystemAdapter(
                    new Filesystem($adapter),
                    $adapter,
                    $config
                );
            } catch (\Throwable $e) {
                Log::error('Falha ao montar disk google: ' . $e->getMessage());
                throw $e;
            }
        });
    }
}
