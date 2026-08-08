<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rede de segurança contra inscrições duplicadas: a checagem em PHP não protege
 * contra duas requisições simultâneas, o banco protege.
 *
 * Registros excluídos (soft delete) e contatos em branco ficam fora do índice —
 * do contrário, apagar uma duplicata deixaria o contato bloqueado para sempre.
 * No MySQL isso é feito com colunas geradas (não há índice parcial); nos demais
 * bancos, com índice parcial mesmo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->guardAgainstExistingDuplicates();

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("
                ALTER TABLE event_registrations
                ADD COLUMN dedupe_email VARCHAR(255)
                GENERATED ALWAYS AS (
                    CASE WHEN deleted_at IS NULL AND email IS NOT NULL AND email <> ''
                        THEN LOWER(email) END
                ) STORED
            ");
            DB::statement("
                ALTER TABLE event_registrations
                ADD COLUMN dedupe_phone VARCHAR(60)
                GENERATED ALWAYS AS (
                    CASE WHEN deleted_at IS NULL AND phone IS NOT NULL AND phone <> ''
                        THEN phone END
                ) STORED
            ");
            DB::statement('CREATE UNIQUE INDEX event_registrations_event_email_unique ON event_registrations (event_id, dedupe_email)');
            DB::statement('CREATE UNIQUE INDEX event_registrations_event_phone_unique ON event_registrations (event_id, dedupe_phone)');

            return;
        }

        DB::statement("
            CREATE UNIQUE INDEX event_registrations_event_email_unique
            ON event_registrations (event_id, LOWER(email))
            WHERE deleted_at IS NULL AND email IS NOT NULL AND email <> ''
        ");
        DB::statement("
            CREATE UNIQUE INDEX event_registrations_event_phone_unique
            ON event_registrations (event_id, phone)
            WHERE deleted_at IS NULL AND phone IS NOT NULL AND phone <> ''
        ");
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('event_registrations', function ($table) {
                $table->dropIndex('event_registrations_event_email_unique');
                $table->dropIndex('event_registrations_event_phone_unique');
                $table->dropColumn(['dedupe_email', 'dedupe_phone']);
            });

            return;
        }

        DB::statement('DROP INDEX IF EXISTS event_registrations_event_email_unique');
        DB::statement('DROP INDEX IF EXISTS event_registrations_event_phone_unique');
    }

    /**
     * O índice não pode ser criado com duplicatas na tabela. Em vez de estourar um
     * erro críptico do driver, aponta o comando de limpeza.
     */
    private function guardAgainstExistingDuplicates(): void
    {
        foreach (['email', 'phone'] as $column) {
            $duplicates = DB::table('event_registrations')
                ->selectRaw("event_id, LOWER({$column}) as contato, COUNT(*) as total")
                ->whereNull('deleted_at')
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->groupBy('event_id', DB::raw("LOWER({$column})"))
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($duplicates->isEmpty()) {
                continue;
            }

            throw new RuntimeException(
                'Há ' . $duplicates->count() . " grupo(s) de inscrições duplicadas por {$column}. "
                . 'Rode "php artisan inscricoes:limpar-duplicadas" antes desta migration.'
            );
        }
    }
};
