<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rifa_vendedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rifa_id')->constrained('rifas')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['rifa_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rifa_vendedores');
    }
};
