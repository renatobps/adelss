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
        if (!Schema::hasTable('moriah_schedule_member_functions')) {
            Schema::create('moriah_schedule_member_functions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('moriah_schedule_member_id');
                $table->foreign('moriah_schedule_member_id', 'msmf_schedule_member_fk')->references('id')->on('moriah_schedule_members')->onDelete('cascade');
                $table->unsignedBigInteger('moriah_function_id');
                $table->foreign('moriah_function_id', 'msmf_function_fk')->references('id')->on('moriah_functions')->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['moriah_schedule_member_id', 'moriah_function_id'], 'msmf_unique');
            });
        } else {
            // Se a tabela já existe, apenas adicionar as foreign keys se não existirem
            Schema::table('moriah_schedule_member_functions', function (Blueprint $table) {
                if (!Schema::hasColumn('moriah_schedule_member_functions', 'moriah_schedule_member_id')) {
                    $table->unsignedBigInteger('moriah_schedule_member_id')->after('id');
                }
                if (!Schema::hasColumn('moriah_schedule_member_functions', 'moriah_function_id')) {
                    $table->unsignedBigInteger('moriah_function_id')->after('moriah_schedule_member_id');
                }
                
                // Verificar e adicionar foreign keys
                $foreignKeys = \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'moriah_schedule_member_functions' AND CONSTRAINT_NAME LIKE '%foreign%'");
                $existingKeys = array_column($foreignKeys, 'CONSTRAINT_NAME');
                
                if (!in_array('msmf_schedule_member_fk', $existingKeys)) {
                    $table->foreign('moriah_schedule_member_id', 'msmf_schedule_member_fk')->references('id')->on('moriah_schedule_members')->onDelete('cascade');
                }
                if (!in_array('msmf_function_fk', $existingKeys)) {
                    $table->foreign('moriah_function_id', 'msmf_function_fk')->references('id')->on('moriah_functions')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moriah_schedule_member_functions');
    }
};
