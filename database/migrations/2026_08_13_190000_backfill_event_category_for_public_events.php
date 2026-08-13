<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Eventos com página pública criados antes do padrão de categoria ficaram
     * com category_id nulo e por isso não apareciam na agenda da home.
     */
    public function up(): void
    {
        $categoriaEventos = DB::table('event_categories')
            ->whereRaw('LOWER(name) = ?', ['eventos'])
            ->value('id');

        if (! $categoriaEventos) {
            return;
        }

        DB::table('events')
            ->whereNull('category_id')
            ->whereNotNull('public_slug')
            ->where('public_slug', '!=', '')
            ->update(['category_id' => $categoriaEventos]);
    }

    public function down(): void
    {
        // Sem reversão: não há registro de quais eventos tinham categoria nula.
    }
};
