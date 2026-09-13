<?php

use App\Models\ScheduleNotificationSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schedule_notification_settings')) {
            return;
        }

        $previousIndividual = "✨ Olá {nome}, paz do Senhor! 🙏\n\nVocê está escalado para servir no culto e será uma bênção nesse dia!\n\n⛪ Culto: {culto}\n📅 Dia: {dia_culto}\n⏰ Hora: {hora_culto}\n📍 Área de Serviço: {area_servico}\n📌 Local: {local_servico}\n\nSua presença é muito importante para o Reino e para o bom andamento do culto.\n\nContamos com você 💙";
        $previousGroup = "Confira a escala do culto, chegue 20 minutos antes do culto iniciar contamos com vc!!\n\n⛪ Culto: {culto}\n📅 Dia: {dia_culto}\n⏰ Hora: {hora_culto}";

        DB::table('schedule_notification_settings')
            ->where(function ($query) use ($previousIndividual) {
                $query->whereNull('immediate_individual_template')
                    ->orWhere('immediate_individual_template', '')
                    ->orWhere('immediate_individual_template', $previousIndividual);
            })
            ->update(['immediate_individual_template' => ScheduleNotificationSetting::DEFAULT_IMMEDIATE_INDIVIDUAL_TEMPLATE]);

        DB::table('schedule_notification_settings')
            ->where(function ($query) use ($previousGroup) {
                $query->whereNull('immediate_group_template')
                    ->orWhere('immediate_group_template', '')
                    ->orWhere('immediate_group_template', $previousGroup);
            })
            ->update(['immediate_group_template' => ScheduleNotificationSetting::DEFAULT_IMMEDIATE_GROUP_TEMPLATE]);
    }

    public function down(): void
    {
        // Mantém os templates atuais; nada a reverter.
    }
};
