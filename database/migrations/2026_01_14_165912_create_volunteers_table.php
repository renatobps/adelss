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
        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->enum('experience_level', ['novo', 'em_treinamento', 'experiente'])->default('novo');
            $table->date('start_date');
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->text('leader_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('member_id');
            $table->index('status');
            $table->index('experience_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteers');
    }
};
