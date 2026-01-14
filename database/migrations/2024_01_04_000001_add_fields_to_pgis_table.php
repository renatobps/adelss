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
            // Remove foreign key antiga se existir
            try {
                $table->dropForeign(['leader_id']);
            } catch (\Exception $e) {
                // Ignora se não existir
            }
            
            // Remove campos antigos se existirem
            $columnsToDrop = [];
            if (Schema::hasColumn('pgis', 'description')) $columnsToDrop[] = 'description';
            if (Schema::hasColumn('pgis', 'location')) $columnsToDrop[] = 'location';
            if (Schema::hasColumn('pgis', 'schedule')) $columnsToDrop[] = 'schedule';
            if (Schema::hasColumn('pgis', 'leader_id')) $columnsToDrop[] = 'leader_id';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
        
        Schema::table('pgis', function (Blueprint $table) {
            // Adiciona novos campos conforme a imagem
            $table->date('opening_date')->nullable()->after('name');
            $table->enum('day_of_week', ['segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado', 'domingo'])->nullable()->after('opening_date');
            $table->enum('profile', ['Masculino', 'Feminino', 'Misto'])->nullable()->after('day_of_week');
            $table->enum('time_schedule', ['Manhã', 'Tarde', 'Noite'])->nullable()->after('profile');
            
            // Campos de liderança
            $table->unsignedBigInteger('leader_1_id')->nullable()->after('time_schedule');
            $table->unsignedBigInteger('leader_2_id')->nullable()->after('leader_1_id');
            $table->unsignedBigInteger('leader_training_1_id')->nullable()->after('leader_2_id');
            $table->unsignedBigInteger('leader_training_2_id')->nullable()->after('leader_training_1_id');
            
            // Campos de endereço
            $table->string('address')->nullable()->after('leader_training_2_id');
            $table->string('neighborhood')->nullable()->after('address');
            $table->string('number')->nullable()->after('neighborhood');
            
            // Campo de anotações
            $table->text('notes')->nullable()->after('number');
        });
        
        // Adiciona foreign keys
        Schema::table('pgis', function (Blueprint $table) {
            $table->foreign('leader_1_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('leader_2_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('leader_training_1_id')->references('id')->on('members')->onDelete('set null');
            $table->foreign('leader_training_2_id')->references('id')->on('members')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pgis', function (Blueprint $table) {
            // Remove foreign keys
            try {
                $table->dropForeign(['leader_1_id']);
                $table->dropForeign(['leader_2_id']);
                $table->dropForeign(['leader_training_1_id']);
                $table->dropForeign(['leader_training_2_id']);
            } catch (\Exception $e) {
                // Ignora se não existirem
            }
            
            // Remove novos campos
            $table->dropColumn([
                'opening_date',
                'day_of_week',
                'profile',
                'time_schedule',
                'leader_1_id',
                'leader_2_id',
                'leader_training_1_id',
                'leader_training_2_id',
                'address',
                'neighborhood',
                'number',
                'notes'
            ]);
            
            // Restaura campos antigos
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('schedule')->nullable();
            $table->unsignedBigInteger('leader_id')->nullable();
        });
    }
};
