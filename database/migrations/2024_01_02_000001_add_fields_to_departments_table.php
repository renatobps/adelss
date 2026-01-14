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
        Schema::table('departments', function (Blueprint $table) {
            $table->string('template')->nullable()->after('name');
            $table->string('icon')->nullable()->after('template');
            $table->string('color')->nullable()->after('icon');
            $table->enum('status', ['ativo', 'arquivado'])->default('ativo')->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['template', 'icon', 'color', 'status']);
        });
    }
};

