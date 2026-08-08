<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_reminder_settings', function (Blueprint $table) {
            $table->id();
            // Nulo = configuração global (padrão herdado por toda campanha nova).
            $table->foreignId('campaign_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->boolean('use_global')->default(true);

            $table->boolean('enabled')->default(false);
            $table->boolean('paused')->default(false);

            $table->unsignedSmallInteger('days_between')->default(7);
            $table->unsignedSmallInteger('max_reminders')->default(3);
            $table->string('send_time', 5)->default('10:00');
            $table->json('send_days')->nullable();
            $table->unsignedSmallInteger('daily_limit')->default(50);

            $table->boolean('attach_pdf')->default(true);
            $table->boolean('attach_pdf_first_only')->default(true);

            $table->text('template_1')->nullable();
            $table->text('template_2')->nullable();
            $table->text('template_3')->nullable();
            $table->unsignedSmallInteger('tier_2_days')->default(15);
            $table->unsignedSmallInteger('tier_3_days')->default(30);

            $table->boolean('courtesy_enabled')->default(false);
            $table->unsignedSmallInteger('courtesy_days_before')->default(3);
            $table->text('courtesy_template')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_reminder_settings');
    }
};
