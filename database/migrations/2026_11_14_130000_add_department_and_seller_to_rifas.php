<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rifas', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
        });
        Schema::table('cartelas', function (Blueprint $table) {
            $table->foreignId('vendedor_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('whatsapp_enviado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cartelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendedor_id');
            $table->dropColumn('whatsapp_enviado_em');
        });
        Schema::table('rifas', fn (Blueprint $table) => $table->dropConstrainedForeignId('department_id'));
    }
};
