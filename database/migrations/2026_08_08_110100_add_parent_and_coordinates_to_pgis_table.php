<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pgis', function (Blueprint $table) {
            if (! Schema::hasColumn('pgis', 'parent_pgi_id')) {
                $table->unsignedBigInteger('parent_pgi_id')->nullable()->after('id');
                $table->index('parent_pgi_id');
            }
            if (! Schema::hasColumn('pgis', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('number');
            }
            if (! Schema::hasColumn('pgis', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('pgis', 'geocoded_at')) {
                $table->timestamp('geocoded_at')->nullable()->after('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pgis', function (Blueprint $table) {
            foreach (['geocoded_at', 'longitude', 'latitude'] as $column) {
                if (Schema::hasColumn('pgis', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('pgis', 'parent_pgi_id')) {
                $table->dropIndex(['parent_pgi_id']);
                $table->dropColumn('parent_pgi_id');
            }
        });
    }
};
