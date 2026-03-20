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
        Schema::table('discipleship_goals', function (Blueprint $table) {
            // Área de Propósito
            $table->integer('quantidade_dias')->nullable()->after('descricao');
            $table->json('restricoes')->nullable()->after('quantidade_dias'); // Array de restrições
            
            // Área de Jejum
            $table->enum('tipo_jejum', ['nenhum', 'total', 'parcial'])->default('nenhum')->after('restricoes');
            $table->integer('horas_jejum_total')->nullable()->after('tipo_jejum'); // 6 a 72 horas
            $table->integer('dias_jejum_parcial')->nullable()->after('horas_jejum_total'); // 1 a 30 dias
            $table->json('alimentos_retirados')->nullable()->after('dias_jejum_parcial'); // Array de alimentos
            
            // Área de Oração
            $table->integer('periodos_oracao_dia')->nullable()->after('alimentos_retirados'); // 1, 2 ou 3
            $table->integer('minutos_oracao_periodo')->nullable()->after('periodos_oracao_dia');
            
            // Área de Estudo da Palavra
            $table->string('livro_biblia')->nullable()->after('minutos_oracao_periodo');
            $table->integer('capitulos_por_dia')->nullable()->after('livro_biblia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discipleship_goals', function (Blueprint $table) {
            $table->dropColumn([
                'quantidade_dias',
                'restricoes',
                'tipo_jejum',
                'horas_jejum_total',
                'dias_jejum_parcial',
                'alimentos_retirados',
                'periodos_oracao_dia',
                'minutos_oracao_periodo',
                'livro_biblia',
                'capitulos_por_dia'
            ]);
        });
    }
};
