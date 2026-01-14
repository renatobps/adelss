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
        Schema::create('financial_contact_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->enum('type', ['pessoa_fisica', 'pessoa_juridica'])->default('pessoa_fisica');
            $table->string('cpf')->nullable(); // Para pessoa física
            $table->string('cnpj')->nullable(); // Para pessoa jurídica
            $table->string('phone_1')->nullable();
            $table->string('phone_2')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('financial_contact_categories')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_contacts');
        Schema::dropIfExists('financial_contact_categories');
    }
};
