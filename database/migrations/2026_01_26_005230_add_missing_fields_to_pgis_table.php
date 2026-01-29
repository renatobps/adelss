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
        Schema::table('pgis', function (Blueprint $table) {
            // Adicionar campos apenas se não existirem
            if (!Schema::hasColumn('pgis', 'opening_date')) {
                $table->date('opening_date')->nullable()->after('name');
            }
            if (!Schema::hasColumn('pgis', 'day_of_week')) {
                $table->enum('day_of_week', ['segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado', 'domingo'])->nullable()->after('opening_date');
            }
            if (!Schema::hasColumn('pgis', 'profile')) {
                $table->enum('profile', ['Masculino', 'Feminino', 'Misto'])->nullable()->after('day_of_week');
            }
            if (!Schema::hasColumn('pgis', 'time_schedule')) {
                $table->enum('time_schedule', ['Manhã', 'Tarde', 'Noite'])->nullable()->after('profile');
            }
            if (!Schema::hasColumn('pgis', 'leader_1_id')) {
                $table->unsignedBigInteger('leader_1_id')->nullable()->after('time_schedule');
            }
            if (!Schema::hasColumn('pgis', 'leader_2_id')) {
                $table->unsignedBigInteger('leader_2_id')->nullable()->after('leader_1_id');
            }
            if (!Schema::hasColumn('pgis', 'leader_training_1_id')) {
                $table->unsignedBigInteger('leader_training_1_id')->nullable()->after('leader_2_id');
            }
            if (!Schema::hasColumn('pgis', 'leader_training_2_id')) {
                $table->unsignedBigInteger('leader_training_2_id')->nullable()->after('leader_training_1_id');
            }
            if (!Schema::hasColumn('pgis', 'address')) {
                $table->string('address')->nullable()->after('leader_training_2_id');
            }
            if (!Schema::hasColumn('pgis', 'neighborhood')) {
                $table->string('neighborhood')->nullable()->after('address');
            }
            if (!Schema::hasColumn('pgis', 'number')) {
                $table->string('number')->nullable()->after('neighborhood');
            }
            if (!Schema::hasColumn('pgis', 'notes')) {
                $table->text('notes')->nullable()->after('number');
            }
            if (!Schema::hasColumn('pgis', 'logo_url')) {
                $table->string('logo_url')->nullable()->after('name');
            }
            if (!Schema::hasColumn('pgis', 'banner_url')) {
                $table->string('banner_url')->nullable()->after('logo_url');
            }
        });

        // Adicionar foreign keys apenas se não existirem
        Schema::table('pgis', function (Blueprint $table) {
            $foreignKeys = \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pgis' AND CONSTRAINT_NAME LIKE '%foreign%'");
            $existingKeys = array_column($foreignKeys, 'CONSTRAINT_NAME');
            
            if (!in_array('pgis_leader_1_id_foreign', $existingKeys) && Schema::hasColumn('pgis', 'leader_1_id')) {
                $table->foreign('leader_1_id')->references('id')->on('members')->onDelete('set null');
            }
            if (!in_array('pgis_leader_2_id_foreign', $existingKeys) && Schema::hasColumn('pgis', 'leader_2_id')) {
                $table->foreign('leader_2_id')->references('id')->on('members')->onDelete('set null');
            }
            if (!in_array('pgis_leader_training_1_id_foreign', $existingKeys) && Schema::hasColumn('pgis', 'leader_training_1_id')) {
                $table->foreign('leader_training_1_id')->references('id')->on('members')->onDelete('set null');
            }
            if (!in_array('pgis_leader_training_2_id_foreign', $existingKeys) && Schema::hasColumn('pgis', 'leader_training_2_id')) {
                $table->foreign('leader_training_2_id')->references('id')->on('members')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pgis', function (Blueprint $table) {
            // Remover foreign keys
            try {
                $table->dropForeign(['leader_1_id']);
            } catch (\Exception $e) {}
            try {
                $table->dropForeign(['leader_2_id']);
            } catch (\Exception $e) {}
            try {
                $table->dropForeign(['leader_training_1_id']);
            } catch (\Exception $e) {}
            try {
                $table->dropForeign(['leader_training_2_id']);
            } catch (\Exception $e) {}
            
            // Remover colunas
            $columnsToDrop = [];
            if (Schema::hasColumn('pgis', 'opening_date')) $columnsToDrop[] = 'opening_date';
            if (Schema::hasColumn('pgis', 'day_of_week')) $columnsToDrop[] = 'day_of_week';
            if (Schema::hasColumn('pgis', 'profile')) $columnsToDrop[] = 'profile';
            if (Schema::hasColumn('pgis', 'time_schedule')) $columnsToDrop[] = 'time_schedule';
            if (Schema::hasColumn('pgis', 'leader_1_id')) $columnsToDrop[] = 'leader_1_id';
            if (Schema::hasColumn('pgis', 'leader_2_id')) $columnsToDrop[] = 'leader_2_id';
            if (Schema::hasColumn('pgis', 'leader_training_1_id')) $columnsToDrop[] = 'leader_training_1_id';
            if (Schema::hasColumn('pgis', 'leader_training_2_id')) $columnsToDrop[] = 'leader_training_2_id';
            if (Schema::hasColumn('pgis', 'address')) $columnsToDrop[] = 'address';
            if (Schema::hasColumn('pgis', 'neighborhood')) $columnsToDrop[] = 'neighborhood';
            if (Schema::hasColumn('pgis', 'number')) $columnsToDrop[] = 'number';
            if (Schema::hasColumn('pgis', 'notes')) $columnsToDrop[] = 'notes';
            if (Schema::hasColumn('pgis', 'logo_url')) $columnsToDrop[] = 'logo_url';
            if (Schema::hasColumn('pgis', 'banner_url')) $columnsToDrop[] = 'banner_url';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
