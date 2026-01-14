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
        Schema::create('studies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome/Título do estudo
            $table->string('category')->nullable(); // Categoria (ex: PGIs, etc)
            $table->longText('content')->nullable(); // Conteúdo do editor de texto rico
            $table->string('featured_image')->nullable(); // URL da imagem em destaque
            $table->string('attachment')->nullable(); // URL do arquivo anexo
            $table->string('attachment_name')->nullable(); // Nome original do arquivo
            $table->boolean('send_notification')->default(false); // Enviar notificação push
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studies');
    }
};
