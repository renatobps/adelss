<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\AuditLogger;
use App\Services\EventRegistrationBatchSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendEventRegistrationWhatsappMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 900;

    /**
     * @param  list<int>  $registrationIds
     */
    public function __construct(
        public int $eventId,
        public array $registrationIds,
        public string $mensagem,
        public ?string $mediaDiskPath = null,
        public ?string $mediaOriginalName = null,
        public ?string $mediaMime = null,
    ) {}

    public function handle(EventRegistrationBatchSender $sender): void
    {
        @set_time_limit(0);

        $event = Event::query()->find($this->eventId);
        if (! $event) {
            return;
        }

        $registrations = EventRegistration::query()
            ->where('event_id', $this->eventId)
            ->whereIn('id', $this->registrationIds)
            ->orderBy('name')
            ->get();

        $arquivo = $this->restoreUploadedFile();

        try {
            $resultado = $sender->sendCustomMessages($registrations, $this->mensagem, $arquivo);

            AuditLogger::log('agenda', 'inscricoes.whatsapp', "WhatsApp enviado a inscritos de {$event->title}.", [
                'event_id' => $event->id,
                'sent' => $resultado['sent'],
                'failed' => $resultado['failed'],
                'skipped' => $resultado['skipped'],
                'aborted' => $resultado['aborted'],
                'ids' => $registrations->pluck('id')->all(),
                'com_midia' => $this->mediaDiskPath !== null,
                'async' => true,
            ]);
        } finally {
            $this->forgetStoredMedia();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->forgetStoredMedia();
    }

    private function restoreUploadedFile(): ?UploadedFile
    {
        if (! $this->mediaDiskPath || ! Storage::disk('local')->exists($this->mediaDiskPath)) {
            return null;
        }

        return new UploadedFile(
            Storage::disk('local')->path($this->mediaDiskPath),
            $this->mediaOriginalName ?: basename($this->mediaDiskPath),
            $this->mediaMime,
            null,
            true
        );
    }

    private function forgetStoredMedia(): void
    {
        if ($this->mediaDiskPath) {
            Storage::disk('local')->delete($this->mediaDiskPath);
        }
    }
}
