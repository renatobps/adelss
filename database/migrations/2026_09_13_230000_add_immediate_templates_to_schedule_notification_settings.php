<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schedule_notification_settings')) {
            return;
        }

        Schema::table('schedule_notification_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('schedule_notification_settings', 'immediate_individual_template')) {
                $table->text('immediate_individual_template')->nullable()->after('day_template');
            }
            if (! Schema::hasColumn('schedule_notification_settings', 'immediate_group_template')) {
                $table->text('immediate_group_template')->nullable()->after('immediate_individual_template');
            }
        });

        $individual = "✨ Olá {nome}, paz do Senhor! 🙏\n\nVocê está escalado para servir no culto e será uma bênção nesse dia!\n\n⛪ Culto: {culto}\n📅 Dia: {dia_culto}\n⏰ Hora: {hora_culto}\n📍 Área de Serviço: {area_servico}\n📌 Local: {local_servico}\n\nSua presença é muito importante para o Reino e para o bom andamento do culto.\n\nContamos com você 💙";
        $group = "Confira a escala do culto, chegue 20 minutos antes do culto iniciar contamos com vc!!\n\n⛪ Culto: {culto}\n📅 Dia: {dia_culto}\n⏰ Hora: {hora_culto}";

        DB::table('schedule_notification_settings')
            ->where(function ($query) {
                $query->whereNull('immediate_individual_template')
                    ->orWhere('immediate_individual_template', '');
            })
            ->update(['immediate_individual_template' => $individual]);

        DB::table('schedule_notification_settings')
            ->where(function ($query) {
                $query->whereNull('immediate_group_template')
                    ->orWhere('immediate_group_template', '');
            })
            ->update(['immediate_group_template' => $group]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('schedule_notification_settings')) {
            return;
        }

        Schema::table('schedule_notification_settings', function (Blueprint $table) {
            if (Schema::hasColumn('schedule_notification_settings', 'immediate_group_template')) {
                $table->dropColumn('immediate_group_template');
            }
            if (Schema::hasColumn('schedule_notification_settings', 'immediate_individual_template')) {
                $table->dropColumn('immediate_individual_template');
            }
        });
    }
};
