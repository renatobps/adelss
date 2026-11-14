<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rifas', function (Blueprint $table) {
            $table->string('pix_chave', 255)->nullable();
            $table->string('pix_recebedor', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rifas', fn (Blueprint $table) => $table->dropColumn(['pix_chave', 'pix_recebedor']));
    }
};
