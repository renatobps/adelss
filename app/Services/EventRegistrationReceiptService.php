<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\PdfText;
use App\Support\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Comprovante de inscrição em eventos: número de inscrição, token de check-in,
 * PDF profissional e envio por WhatsApp.
 * Segue o padrão do CampaignReceiptService: falha de envio NUNCA invalida a inscrição.
 */
class EventRegistrationReceiptService
{
    public function __construct(
        private readonly WhatsAppService $whatsappService,
    ) {}

    /**
     * Garante que a inscrição tenha número legível e token de check-in.
     * Seguro para chamar mais de uma vez (idempotente).
     */
    public function ensureCredentials(EventRegistration $registration): void
    {
        $registration->loadMissing('event');

        if (!$registration->check_in_token) {
            $registration->check_in_token = $this->generateUniqueToken();
        }

        if (!$registration->registration_number) {
            $registration->registration_number = $this->generateRegistrationNumber($registration);
        }

        if ($registration->isDirty(['check_in_token', 'registration_number'])) {
            try {
                $registration->save();
            } catch (QueryException $e) {
                // Colisão rara de unicidade (inscrições simultâneas): tenta uma vez com novos valores.
                $registration->check_in_token = $this->generateUniqueToken();
                $registration->registration_number = $this->generateRegistrationNumber($registration);
                $registration->save();
            }
        }
    }

    /**
     * Gera número e token para inscrições antigas que ficaram sem eles.
     * Percorre em ordem cronológica para que o sequencial reflita a ordem de inscrição.
     *
     * @return int quantidade de inscrições atualizadas
     */
    public function backfillMissingCredentials(?int $eventId = null): int
    {
        $updated = 0;

        // Os ids são coletados antes: a própria condição do filtro é a coluna que
        // será preenchida, então paginar a consulta enquanto se escreve nela pularia registros.
        $ids = EventRegistration::withTrashed()
            ->when($eventId !== null, fn ($q) => $q->where('event_id', $eventId))
            ->where(function ($q) {
                $q->whereNull('registration_number')
                    ->orWhere('registration_number', '')
                    ->orWhereNull('check_in_token')
                    ->orWhere('check_in_token', '');
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids->chunk(200) as $chunk) {
            $registrations = EventRegistration::withTrashed()
                ->whereIn('id', $chunk)
                ->with('event')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            foreach ($registrations as $registration) {
                if (! $registration->event) {
                    continue;
                }
                try {
                    $this->ensureCredentials($registration);
                    $updated++;
                } catch (\Throwable $e) {
                    Log::warning('Evento: falha ao gerar número da inscrição em lote.', [
                        'registration_id' => $registration->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $updated;
    }

    /**
     * Envia o comprovante por WhatsApp (texto + PDF).
     * Nunca lança exceção: a inscrição não pode ser invalidada por falha de envio.
     *
     * @return array{success: bool, skipped?: bool, error?: string}
     */
    public function enviarComprovante(EventRegistration $registration): array
    {
        $registration->loadMissing(['event', 'payment']);
        $event = $registration->event;

        $phone = trim((string) $registration->phone);
        if ($phone === '') {
            return [
                'success' => false,
                'skipped' => true,
                'error' => 'Inscrito sem telefone cadastrado.',
            ];
        }

        try {
            $this->ensureCredentials($registration);
        } catch (\Throwable $e) {
            Log::error('Evento: erro ao gerar credenciais da inscrição.', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);
        }

        $paymentLine = '';
        if ($event->is_paid) {
            $paymentLine = 'Pagamento: ' . ($registration->isPaymentApproved() ? 'Confirmado' : 'Pendente') . "\n";
        }

        $mensagem = "*Comprovante de inscrição*\n\n"
            . "Evento: *{$event->title}*\n"
            . 'Inscrição nº: ' . ($registration->registration_number ?: '-') . "\n"
            . "Nome: {$registration->name}\n"
            . 'Data: ' . $event->start_date->format('d/m/Y H:i') . "\n"
            . ($event->location ? "Local: {$event->location}\n" : '')
            . $paymentLine
            . "\nApresente o comprovante em anexo (com QR Code) na entrada do evento.";

        try {
            $resultado = ['success' => false];
            $pdfPath = $this->gerarPdfComprovante($registration);
            if ($pdfPath) {
                $resultado = $this->whatsappService->enviarDocumentoArquivo(
                    $phone,
                    $pdfPath,
                    'comprovante-' . Str::slug((string) ($registration->registration_number ?: 'inscricao-' . $registration->id)) . '.pdf',
                    $mensagem
                );
                @unlink($pdfPath);
            }

            if (!($resultado['success'] ?? false)) {
                $resultado = $this->whatsappService->enviarMensagem($phone, $mensagem);
            }

            if ($resultado['success'] ?? false) {
                $registration->update(['receipt_sent_at' => now()]);

                return ['success' => true];
            }

            Log::warning('Evento: falha ao enviar comprovante de inscrição por WhatsApp.', [
                'registration_id' => $registration->id,
                'error' => $resultado['error'] ?? 'desconhecido',
            ]);

            return ['success' => false, 'error' => $resultado['error'] ?? 'Falha no envio do WhatsApp.'];
        } catch (\Throwable $e) {
            Log::error('Evento: erro inesperado ao enviar comprovante de inscrição.', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Gera o PDF do comprovante e retorna o caminho do arquivo temporário.
     */
    public function gerarPdfComprovante(EventRegistration $registration): ?string
    {
        try {
            $pdf = Pdf::loadView('agenda.eventos.pdf.comprovante', $this->pdfViewData($registration))
                ->setPaper('a4');

            $dir = storage_path('app/temp/event-receipts');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $path = $dir . '/comprovante-' . $registration->id . '-' . time() . '.pdf';
            file_put_contents($path, $pdf->output());

            return $path;
        } catch (\Throwable $e) {
            Log::error('Evento: erro ao gerar PDF do comprovante de inscrição.', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Dados compartilhados do template PDF (usado também no download pelo admin).
     *
     * @return array<string, mixed>
     */
    public function pdfViewData(EventRegistration $registration): array
    {
        $registration->loadMissing(['event', 'payment']);
        $this->ensureCredentials($registration);
        $event = $registration->event;

        return [
            'registration' => $registration,
            'event' => $event,
            'bannerDataUri' => self::publicFileDataUri($event->banner_image),
            'qrDataUri' => QrCode::pngDataUri((string) $registration->check_in_token, 8),
            'eventTitle' => PdfText::stripEmoji((string) $event->title),
        ];
    }

    /**
     * Converte um arquivo do disco "public" em data URI base64 (imagens no DomPDF).
     */
    public static function publicFileDataUri(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        $path = preg_replace('#^(?:/+)?storage/+#', '', trim(str_replace('\\', '/', $path)));
        if ($path === '' || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);
        $mime = @mime_content_type($absolute) ?: 'image/jpeg';
        $contents = @file_get_contents($absolute);
        if ($contents === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(20);
        } while (EventRegistration::withTrashed()->where('check_in_token', $token)->exists());

        return $token;
    }

    /**
     * Número legível e curto: prefixo do evento + ano + sequencial por evento.
     * Ex.: IMR-2026-0042
     */
    private function generateRegistrationNumber(EventRegistration $registration): string
    {
        $event = $registration->event;
        $prefix = $this->eventPrefix($event);
        $year = $event->start_date?->format('Y') ?: now()->format('Y');

        $sequence = $this->nextSequence($event, $prefix, $year);

        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }

    private function nextSequence(Event $event, string $prefix, string $year): int
    {
        // Inclui excluídas: reaproveitar o número de uma inscrição removida faria
        // dois comprovantes diferentes carregarem o mesmo identificador.
        $last = EventRegistration::withTrashed()
            ->where('event_id', $event->id)
            ->whereNotNull('registration_number')
            ->where('registration_number', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('registration_number')
            ->value('registration_number');

        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            return ((int) $m[1]) + 1;
        }

        return 1;
    }

    private function eventPrefix(Event $event): string
    {
        $title = Str::ascii((string) $event->title);
        $stopWords = ['de', 'da', 'do', 'das', 'dos', 'e', 'a', 'o', 'as', 'os', 'em', 'na', 'no', 'para', 'com'];

        $letters = '';
        foreach (preg_split('/[^a-zA-Z0-9]+/', $title, -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (in_array(mb_strtolower($word), $stopWords, true)) {
                continue;
            }
            $letters .= strtoupper(substr($word, 0, 1));
            if (strlen($letters) >= 3) {
                break;
            }
        }

        return $letters !== '' ? $letters : 'EVT';
    }
}
