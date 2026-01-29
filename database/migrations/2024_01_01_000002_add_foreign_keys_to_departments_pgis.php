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
        // Verificar se as foreign keys já existem antes de criar
        $deptForeignKeys = \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'departments' AND CONSTRAINT_NAME LIKE '%foreign%'");
        $deptExistingKeys = array_column($deptForeignKeys, 'CONSTRAINT_NAME');
        
        if (Schema::hasTable('departments') && !in_array('departments_leader_id_foreign', $deptExistingKeys)) {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreign('leader_id')->references('id')->on('members')->onDelete('set null');
            });
        }

        if (Schema::hasTable('pgis')) {
            $pgiForeignKeys = \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pgis' AND CONSTRAINT_NAME LIKE '%foreign%'");
            $pgiExistingKeys = array_column($pgiForeignKeys, 'CONSTRAINT_NAME');
            
            if (!in_array('pgis_leader_id_foreign', $pgiExistingKeys)) {
                Schema::table('pgis', function (Blueprint $table) {
                    $table->foreign('leader_id')->references('id')->on('members')->onDelete('set null');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['leader_id']);
        });

        Schema::table('pgis', function (Blueprint $table) {
            $table->dropForeign(['leader_id']);
        });
    }
};


