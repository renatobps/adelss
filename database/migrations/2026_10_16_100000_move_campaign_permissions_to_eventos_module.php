<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Campanhas de arrecadação saíram do módulo Financeiro para o novo módulo
 * Gestor de Eventos. Renomeia as chaves de permissão existentes para preservar
 * os vínculos já concedidos a cargos e usuários.
 */
return new class extends Migration
{
    private const OLD_PREFIX = 'financial.campanhas.';
    private const NEW_PREFIX = 'eventos.campanhas.';

    public function up(): void
    {
        $this->rename(self::OLD_PREFIX, self::NEW_PREFIX, 'Gestor de Eventos');
    }

    public function down(): void
    {
        $this->rename(self::NEW_PREFIX, self::OLD_PREFIX, 'Financeiro');
    }

    private function rename(string $from, string $to, string $module): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $rows = DB::table('permissions')->where('key', 'like', $from . '%')->get();

        foreach ($rows as $row) {
            $newKey = $to . substr($row->key, strlen($from));

            if (DB::table('permissions')->where('key', $newKey)->exists()) {
                DB::table('permissions')->where('id', $row->id)->delete();
                continue;
            }

            DB::table('permissions')
                ->where('id', $row->id)
                ->update(['key' => $newKey, 'module' => $module, 'updated_at' => now()]);
        }
    }
};
