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
        Schema::table('songs', function (Blueprint $table) {
            $table->string('version_name', 20)->nullable()->after('title');
            $table->text('observations')->nullable()->after('version_name');
            $table->integer('bpm')->nullable()->after('key');
            $table->integer('duration_hours')->default(0)->after('bpm');
            $table->integer('duration_minutes')->default(0)->after('duration_hours');
            $table->integer('duration_seconds')->default(0)->after('duration_minutes');
            $table->string('link_letra')->nullable()->after('lyrics');
            $table->string('link_cifra')->nullable()->after('link_letra');
            $table->string('link_audio')->nullable()->after('link_cifra');
            $table->string('link_video')->nullable()->after('link_audio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn([
                'version_name',
                'observations',
                'bpm',
                'duration_hours',
                'duration_minutes',
                'duration_seconds',
                'link_letra',
                'link_cifra',
                'link_audio',
                'link_video'
            ]);
        });
    }
};
