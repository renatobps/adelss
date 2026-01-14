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
        Schema::table('service_areas', function (Blueprint $table) {
            $table->foreignId('leader_id')->nullable()->after('description')->constrained('members')->onDelete('set null');
            $table->integer('min_quantity')->default(1)->after('leader_id');
            $table->enum('allowed_audience', ['adulto', 'jovem', 'ambos'])->default('ambos')->after('min_quantity');
            
            $table->index('leader_id');
            $table->index('allowed_audience');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropForeign(['leader_id']);
            $table->dropColumn(['leader_id', 'min_quantity', 'allowed_audience']);
        });
    }
};
