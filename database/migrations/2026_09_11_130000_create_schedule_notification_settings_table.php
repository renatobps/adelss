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
            Schema::create('schedule_notification_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('month_enabled')->default(false);
                $table->unsignedTinyInteger('month_day')->default(1);
                $table->string('month_time', 5)->default('09:00');
                $table->text('month_template')->nullable();
                $table->boolean('week_enabled')->default(false);
                $table->unsignedTinyInteger('week_weekday')->default(1);
                $table->string('week_time', 5)->default('09:00');
                $table->text('week_template')->nullable();
                $table->boolean('day_enabled')->default(false);
                $table->string('day_time', 5)->default('09:00');
                $table->text('day_template')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('schedule_notification_settings') && DB::table('schedule_notification_settings')->count() === 0) {
            $now = now();
            DB::table('schedule_notification_settings')->insert([
                'month_enabled' => false,
                'month_day' => 1,
                'month_time' => '09:00',
                'month_template' => "Olá, {nome}! 🙏\n\nSegue sua escala de *{mes}*:\n{escalas}\n\nDeus abençoe seu serviço!",
                'week_enabled' => false,
                'week_weekday' => 1,
                'week_time' => '09:00',
                'week_template' => "Olá, {nome}! 🙏\n\nNesta semana você está escalado(a) em:\n{escalas}\n\nDeus abençoe!",
                'day_enabled' => false,
                'day_time' => '09:00',
                'day_template' => "Olá, {nome}! 🙏\n\nLembrete: hoje ({dia_culto}) você serve no *{culto}* às {hora_culto} na área *{area_servico}*.\n\nDeus abençoe!",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! Schema::hasTable('schedule_reminder_logs')) {
            Schema::create('schedule_reminder_logs', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20);
                $table->string('period_key', 40);
                $table->unsignedBigInteger('volunteer_id');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->unique(['type', 'period_key', 'volunteer_id'], 'schedule_reminder_unique');
                $table->index('volunteer_id');
            });
        }

        if (Schema::hasTable('service_areas')) {
            $updates = [
                ['%intercess%', 4],
                ['%recep%', 2],
                ['%crian%', 2],
                ['%limpeza%', 2],
                ['%zelador%', 2],
            ];

            foreach ($updates as [$like, $quantity]) {
                DB::table('service_areas')
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->update(['min_quantity' => $quantity]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_reminder_logs');
        Schema::dropIfExists('schedule_notification_settings');
    }
};
