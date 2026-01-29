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
        Schema::table('members', function (Blueprint $table) {
            // Verificar se as foreign keys já existem antes de criar
            $foreignKeys = \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'members' AND CONSTRAINT_NAME LIKE '%foreign%'");
            $existingKeys = array_column($foreignKeys, 'CONSTRAINT_NAME');
            
            // Verificar se a tabela departments existe
            if (Schema::hasTable('departments') && !in_array('members_department_id_foreign', $existingKeys)) {
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
            
            // Verificar se a tabela pgis existe
            if (Schema::hasTable('pgis') && !in_array('members_pgi_id_foreign', $existingKeys)) {
                $table->foreign('pgi_id')->references('id')->on('pgis')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['pgi_id']);
        });
    }
};


