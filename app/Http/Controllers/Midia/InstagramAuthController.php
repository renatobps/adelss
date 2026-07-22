<?php

namespace App\Http\Controllers\Midia;

use App\Http\Controllers\Controller;
use App\Services\InstagramService;
use Illuminate\Http\Request;

class InstagramAuthController extends Controller
{
    public function __construct(private InstagramService $instagram) {}

    public function redirect()
    {
        if (!config('services.instagram.app_id') || !config('services.instagram.app_secret')) {
            return redirect()
                ->route('midia.settings')
                ->with('error', 'Configure INSTAGRAM_APP_ID e INSTAGRAM_APP_SECRET no .env.');
        }

        return redirect()->away($this->instagram->getAuthUrl());
    }

    public function callback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('midia.settings')
                ->with('error', 'Autorização Instagram cancelada: ' . $request->input('error_description', $request->input('error')));
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('midia.settings')->with('error', 'Código OAuth não recebido.');
        }

        try {
            $this->instagram->handleCallback($code);

            return redirect()
                ->route('midia.settings')
                ->with('success', 'Instagram conectado com sucesso.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('midia.settings')
                ->with('error', 'Falha ao conectar Instagram: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $this->instagram->disconnect();

        return redirect()
            ->route('midia.settings')
            ->with('success', 'Instagram desconectado.');
    }
}
