<?php

namespace App\Services;

use App\Models\EventRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Envio de comprovantes em lote com as mesmas proteções dos lembretes de campanha:
 * a API de WhatsApp é não-oficial e uma rajada de mensagens idênticas em cadência
 * regular é o caminho mais curto para o número ser banido.
 */
class EventRegistrationBatchSender
{
    public const MIN_INTERVAL_SECONDS = 8;

    public const MAX_INTERVAL_SECONDS = 20;

    /** Teto por lote: acima disso o envio deve ser fatiado em rodadas. */
    public const BATCH_LIMIT = 30;

    private const CONSECUTIVE_FAILURE_LIMIT = 5;

    public function __construct(
        private readonly WhatsAppService $whatsapp,
        private readonly EventRegistrationReceiptService $receipts,
    ) {}

    /**
     * @param  Collection<int, EventRegistration>  $registrations
     * @return array{sent:int,failed:int,skipped:int,aborted:?string}
     */
    public function sendReceipts(Collection $registrations): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'aborted' => null];

        $candidates = $registrations->filter(fn (EventRegistration $r) => filled($r->phone));
        $result['skipped'] = $registrations->count() - $candidates->count();

        if ($candidates->isEmpty()) {
            return $result;
        }

        // Não faz sentido enfileirar dezenas de mensagens contra uma instância fora do ar.
        $connection = $this->whatsapp->checkConnectionStatus();
        if (! ($connection['connected'] ?? false)) {
            $result['aborted'] = 'WhatsApp desconectado (instância: ' . ($connection['instance_name'] ?: 'padrão') . ').';

            return $result;
        }

        if ($candidates->count() > self::BATCH_LIMIT) {
            $result['aborted'] = 'Lote limitado a ' . self::BATCH_LIMIT . ' envios por vez; selecione o restante em uma nova rodada.';
            $result['skipped'] += $candidates->count() - self::BATCH_LIMIT;
            $candidates = $candidates->take(self::BATCH_LIMIT);
        }

        @set_time_limit(0);

        $consecutiveFailures = 0;
        $first = true;

        foreach ($candidates as $registration) {
            if (! $first) {
                // Cadência perfeitamente regular é padrão de robô: o intervalo varia.
                sleep(random_int(self::MIN_INTERVAL_SECONDS, self::MAX_INTERVAL_SECONDS));
            }
            $first = false;

            $outcome = $this->receipts->enviarComprovante($registration);

            if ($outcome['success'] ?? false) {
                $result['sent']++;
                $consecutiveFailures = 0;
                continue;
            }

            if ($outcome['skipped'] ?? false) {
                $result['skipped']++;
                continue;
            }

            $result['failed']++;
            $consecutiveFailures++;

            Log::warning('Evento: falha ao enviar comprovante em lote.', [
                'registration_id' => $registration->id,
                'error' => $outcome['error'] ?? 'desconhecido',
            ]);

            if ($consecutiveFailures >= self::CONSECUTIVE_FAILURE_LIMIT) {
                $result['aborted'] = self::CONSECUTIVE_FAILURE_LIMIT . ' envios seguidos falharam; o lote foi interrompido.';
                break;
            }
        }

        return $result;
    }
}
