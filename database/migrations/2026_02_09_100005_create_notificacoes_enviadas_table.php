<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacoes_enviadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('telefone', 32)->nullable();
            $table->string('tipo_notificacao', 64)->default('custom');
            $table->text('mensagem');
            $table->timestamp('data_envio')->useCurrent();
            $table->string('status', 32)->default('pendente');
            $table->json('resposta_api')->nullable();
            $table->integer('tentativas')->default(0);
            $table->text('erro_detalhes')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('data_envio');
            $table->index('tipo_notificacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes_enviadas');
    }
};
