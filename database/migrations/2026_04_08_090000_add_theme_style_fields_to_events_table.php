<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('subtitle_color', 20)->nullable()->after('subtitle');
            $table->string('subtitle_font_family', 100)->nullable()->after('subtitle_color');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'subtitle_color',
                'subtitle_font_family',
            ]);
        });
    }
};
