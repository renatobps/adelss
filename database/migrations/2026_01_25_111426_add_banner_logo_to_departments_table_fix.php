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
        if (!Schema::hasColumn('departments', 'banner_url')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->string('banner_url')->nullable()->after('description');
            });
        }
        if (!Schema::hasColumn('departments', 'logo_url')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->string('logo_url')->nullable()->after('banner_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'banner_url')) {
                $table->dropColumn('banner_url');
            }
            if (Schema::hasColumn('departments', 'logo_url')) {
                $table->dropColumn('logo_url');
            }
        });
    }
};
