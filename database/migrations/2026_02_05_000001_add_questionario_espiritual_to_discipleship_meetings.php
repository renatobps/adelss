<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('discipleship_meetings', function (Blueprint $table) {
            // Remover versículos e oração
            $table->dropColumn(['versiculos', 'oracao']);

            // Oração
            $table->string('oracao_tempo_dia', 20)->nullable()->after('proximo_passo'); // 0-60, mais_1h
            $table->text('oracao_como_sao')->nullable()->after('oracao_tempo_dia');
            $table->text('oracao_observacoes')->nullable()->after('oracao_como_sao');

            // Jejum
            $table->string('jejum_horas_semana', 20)->nullable()->after('oracao_observacoes'); // 0-24, mais_24
            $table->string('jejum_tipo', 20)->nullable()->after('jejum_horas_semana'); // total, parcial, nenhum
            $table->string('jejum_com_proposito', 5)->nullable()->after('jejum_tipo'); // sim, nao
            $table->text('jejum_observacoes')->nullable()->after('jejum_com_proposito');

            // Leitura Bíblica
            $table->string('leitura_capitulos_dia', 20)->nullable()->after('jejum_observacoes'); // 0-10, mais_10
            $table->string('leitura_estuda', 5)->nullable()->after('leitura_capitulos_dia'); // sim, nao
            $table->text('leitura_observacoes')->nullable()->after('leitura_estuda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discipleship_meetings', function (Blueprint $table) {
            $table->dropColumn([
                'oracao_tempo_dia', 'oracao_como_sao', 'oracao_observacoes',
                'jejum_horas_semana', 'jejum_tipo', 'jejum_com_proposito', 'jejum_observacoes',
                'leitura_capitulos_dia', 'leitura_estuda', 'leitura_observacoes',
            ]);
            $table->text('versiculos')->nullable()->after('assuntos_tratados');
            $table->text('oracao')->nullable()->after('versiculos');
        });
    }
};
