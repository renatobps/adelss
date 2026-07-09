<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\EnqueteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EvolutionWebhookController extends Controller
{
    public function __construct(
        private readonly EnqueteService $enqueteService,
    ) {}

    public function handle(Request $request, ?string $event = null): JsonResponse
    {
        try {
            $payload = $request->all();

            if ($payload === []) {
                Log::info('Evolution webhook vazio ignorado.', ['path' => $request->path()]);

                return response()->json(['ok' => true, 'processed' => false]);
            }

            if ($event !== null && blank($payload['event'] ?? null)) {
                $payload['event'] = Str::upper(str_replace('-', '_', $event));
            }

            Log::info('Evolution webhook recebido.', [
                'event' => $payload['event'] ?? $event,
                'instance' => $payload['instance'] ?? null,
                'path' => $request->path(),
            ]);

            $processed = $this->enqueteService->processarWebhookPayload($payload);

            return response()->json(['ok' => true, 'processed' => $processed]);
        } catch (\Throwable $e) {
            Log::error('Evolution webhook erro.', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
            ]);

            return response()->json(['ok' => true, 'processed' => false, 'error' => $e->getMessage()]);
        }
    }
}
