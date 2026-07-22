<?php

namespace App\Http\Controllers\Midia;

use App\Http\Controllers\Controller;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;

class GoogleDriveAuthController extends Controller
{
    public function __construct(private GoogleDriveService $drive) {}

    public function redirect()
    {
        if (!config('services.google_drive.client_id') || !config('services.google_drive.client_secret')) {
            return redirect()
                ->route('midia.settings')
                ->with('error', 'Configure GOOGLE_DRIVE_CLIENT_ID e GOOGLE_DRIVE_CLIENT_SECRET no .env.');
        }

        return redirect()->away($this->drive->getAuthUrl());
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('midia.settings')
                ->with('error', 'Autorização Google cancelada: ' . $request->input('error'));
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('midia.settings')->with('error', 'Código OAuth não recebido.');
        }

        try {
            $this->drive->handleCallback($code);

            return redirect()
                ->route('midia.settings')
                ->with('success', 'Google Drive conectado com sucesso.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('midia.settings')
                ->with('error', 'Falha ao conectar Google Drive: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $this->drive->disconnect();

        return redirect()
            ->route('midia.settings')
            ->with('success', 'Google Drive desconectado.');
    }
}
