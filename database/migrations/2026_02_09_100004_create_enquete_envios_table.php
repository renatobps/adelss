<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquete_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquete_id')->constrained('notificacao_enquetes')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('telefone', 32);
            $table->string('status', 32)->default('enviado');
            $table->timestamp('enviado_em')->useCurrent();
            $table->timestamps();
            $table->index(['telefone', 'enviado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquete_envios');
    }
};
