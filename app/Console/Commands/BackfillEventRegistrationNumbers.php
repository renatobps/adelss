<?php

namespace App\Console\Commands;

use App\Services\EventRegistrationReceiptService;
use Illuminate\Console\Command;

class BackfillEventRegistrationNumbers extends Command
{
    protected $signature = 'inscricoes:gerar-numeros {--event= : Limita a um evento específico (id)}';

    protected $description = 'Gera número de inscrição e token de check-in para inscrições antigas que ficaram sem eles';

    public function handle(EventRegistrationReceiptService $receipts): int
    {
        $eventId = $this->option('event') !== null ? (int) $this->option('event') : null;

        $updated = $receipts->backfillMissingCredentials($eventId);

        $this->info($updated === 0
            ? 'Todas as inscrições já possuem número.'
            : "{$updated} inscrição(ões) atualizada(s), respeitando a ordem cronológica.");

        return self::SUCCESS;
    }
}
