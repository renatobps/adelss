<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_connection_logs', function (Blueprint $table) {
            $table->id();
            $table->string('instance_name');
            $table->string('instance_id')->nullable();
            $table->string('status'); // conectado | desconectado | erro_consulta
            $table->string('raw_state')->nullable();
            $table->dateTime('checked_at');
            $table->boolean('notified')->default(false);
            $table->timestamps();

            $table->index(['instance_name', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_connection_logs');
    }
};
