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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->enum('gender', ['M', 'F'])->nullable();
            $table->date('birth_date')->nullable();
            $table->string('photo_url')->nullable();
            $table->enum('status', ['ativo', 'inativo', 'visitante', 'membro_transferido'])->default('visitante');
            $table->string('cpf')->nullable()->unique();
            $table->string('rg')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->date('membership_date')->nullable();
            $table->text('notes')->nullable();
            // Foreign keys serão adicionadas depois que departments e pgis existirem
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('pgi_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
