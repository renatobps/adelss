<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\EnqueteService;
use App\Services\NotificacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EvolutionWebhookController extends Controller
{
    public function __construct(
        private readonly EnqueteService $enqueteService,
        private readonly NotificacaoService $notificacaoService,
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
                'state' => $payload['state'] ?? data_get($payload, 'data.state'),
                'instance' => $payload['instance'] ?? $payload['instanceId'] ?? null,
                'path' => $request->path(),
                // Evolution GO: data.Message / data.Info | Evolution API: data.message / data.key
                'message_keys' => array_keys(
                    data_get($payload, 'data.Message', [])
                        ?: data_get($payload, 'data.message', [])
                        ?: []
                ),
                'message_ids' => data_get($payload, 'data.MessageIDs'),
                'remote_jid' => data_get($payload, 'data.Info.Sender')
                    ?? data_get($payload, 'data.Info.Chat')
                    ?? data_get($payload, 'data.Chat')
                    ?? data_get($payload, 'data.key.remoteJid'),
                'remote_jid_alt' => data_get($payload, 'data.Info.SenderAlt')
                    ?? data_get($payload, 'data.key.remoteJidAlt'),
                'from_me' => data_get($payload, 'data.Info.IsFromMe')
                    ?? data_get($payload, 'data.key.fromMe'),
            ]);

            $processedReceipt = $this->notificacaoService->processarConfirmacaoEntrega($payload);
            $processedEnquete = $this->enqueteService->processarWebhookPayload($payload);

            return response()->json([
                'ok' => true,
                'processed' => $processedReceipt || $processedEnquete,
                'receipt' => $processedReceipt,
                'enquete' => $processedEnquete,
            ]);
        } catch (\Throwable $e) {
            Log::error('Evolution webhook erro.', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
            ]);

            return response()->json(['ok' => true, 'processed' => false, 'error' => $e->getMessage()]);
        }
    }
}
