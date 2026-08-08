<?php

use App\Services\EventRegistrationReceiptService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * O número de inscrição passou a ser gerado na criação do registro. As inscrições
 * anteriores ficaram sem número (só ganhavam um ao confirmar/enviar comprovante),
 * então recebem o seu aqui, na ordem cronológica em que se inscreveram.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_registrations')) {
            return;
        }

        app(EventRegistrationReceiptService::class)->backfillMissingCredentials();
    }

    public function down(): void
    {
        // Números emitidos não são revogados: são o identificador da pessoa no evento.
    }
};
