<?php

namespace App\Services;

use App\Models\EventRegistration;
use Illuminate\Http\UploadedFile;
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
        private readonly NotificacaoService $notificacoes,
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
            $result['aborted'] = 'WhatsApp desconectado (instância: '.($connection['instance_name'] ?: 'padrão').').';

            return $result;
        }

        if ($candidates->count() > self::BATCH_LIMIT) {
            $result['aborted'] = 'Lote limitado a '.self::BATCH_LIMIT.' envios por vez; selecione o restante em uma nova rodada.';
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
                $result['aborted'] = self::CONSECUTIVE_FAILURE_LIMIT.' envios seguidos falharam; o lote foi interrompido.';
                break;
            }
        }

        return $result;
    }

    /**
     * Mensagem livre (texto e/ou mídia) para inscritos selecionados.
     *
     * @param  Collection<int, EventRegistration>  $registrations
     * @return array{sent:int,failed:int,skipped:int,aborted:?string,errors:list<string>}
     */
    public function sendCustomMessages(Collection $registrations, string $mensagem, ?UploadedFile $arquivo = null): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'aborted' => null, 'errors' => []];
        $mensagem = trim($mensagem);

        if ($mensagem === '' && $arquivo === null) {
            $result['aborted'] = 'Informe uma mensagem ou anexe um arquivo.';

            return $result;
        }

        $candidates = $registrations->filter(fn (EventRegistration $r) => filled($r->phone));
        $result['skipped'] = $registrations->count() - $candidates->count();

        if ($candidates->isEmpty()) {
            $result['aborted'] = 'Nenhum inscrito selecionado tem telefone cadastrado.';

            return $result;
        }

        $connection = $this->whatsapp->checkConnectionStatus();
        if (! ($connection['connected'] ?? false)) {
            $result['aborted'] = 'WhatsApp desconectado (instância: '.($connection['instance_name'] ?: 'padrão').').';

            return $result;
        }

        if ($candidates->count() > self::BATCH_LIMIT) {
            $result['aborted'] = 'Lote limitado a '.self::BATCH_LIMIT.' envios por vez; selecione o restante em uma nova rodada.';
            $result['skipped'] += $candidates->count() - self::BATCH_LIMIT;
            $candidates = $candidates->take(self::BATCH_LIMIT);
        }

        $midia = $arquivo ? $this->detectarMidia($arquivo) : null;

        @set_time_limit(0);

        $consecutiveFailures = 0;
        $first = true;

        foreach ($candidates as $registration) {
            if (! $first) {
                sleep(random_int(self::MIN_INTERVAL_SECONDS, self::MAX_INTERVAL_SECONDS));
            }
            $first = false;

            $texto = $this->notificacoes->personalizarMensagem($mensagem, $registration->name);
            $outcome = $midia
                ? $this->whatsapp->enviarMidiaArquivo(
                    $registration->phone,
                    $arquivo,
                    $midia['tipo'],
                    $midia['is_pdf_document'],
                    $midia['file_name'],
                    $texto
                )
                : $this->whatsapp->enviarMensagem($registration->phone, $texto);

            if ($outcome['success'] ?? false) {
                $result['sent']++;
                $consecutiveFailures = 0;

                continue;
            }

            $result['failed']++;
            $consecutiveFailures++;
            $erro = $outcome['error'] ?? 'desconhecido';
            $result['errors'][] = $registration->name.': '.$erro;

            Log::warning('Evento: falha ao enviar WhatsApp em lote.', [
                'registration_id' => $registration->id,
                'error' => $erro,
            ]);

            if ($consecutiveFailures >= self::CONSECUTIVE_FAILURE_LIMIT) {
                $result['aborted'] = self::CONSECUTIVE_FAILURE_LIMIT.' envios seguidos falharam; o lote foi interrompido.';
                break;
            }
        }

        return $result;
    }

    /**
     * @return array{tipo: string, is_pdf_document: bool, file_name: string}
     */
    private function detectarMidia(UploadedFile $arquivo): array
    {
        $fileName = $arquivo->getClientOriginalName();
        $mime = strtolower((string) $arquivo->getMimeType());
        $tipo = 'document';

        if (str_starts_with($mime, 'image/')) {
            $tipo = 'image';
        } elseif (str_starts_with($mime, 'video/')) {
            $tipo = 'video';
        } elseif (str_starts_with($mime, 'audio/')) {
            $tipo = 'audio';
        }

        return [
            'tipo' => $tipo,
            'is_pdf_document' => $tipo === 'document' && $mime === 'application/pdf',
            'file_name' => $fileName,
        ];
    }
}
