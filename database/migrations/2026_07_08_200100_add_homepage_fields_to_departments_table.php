<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('show_on_homepage')->default(false)->after('description');
            $table->unsignedSmallInteger('homepage_order')->nullable()->after('show_on_homepage');
            $table->string('homepage_url')->nullable()->after('homepage_order');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['show_on_homepage', 'homepage_order', 'homepage_url']);
        });
    }
};
