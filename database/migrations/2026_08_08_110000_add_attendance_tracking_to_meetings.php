<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            if (! Schema::hasColumn('meetings', 'attendance_registered_at')) {
                $table->timestamp('attendance_registered_at')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('meetings', 'attendance_registered_by')) {
                $table->unsignedBigInteger('attendance_registered_by')->nullable()->after('attendance_registered_at');
            }
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('meeting_attendances', 'visitor_phone')) {
                $table->string('visitor_phone', 30)->nullable()->after('visitor_name');
            }
        });

        // Reuniões que já possuem lançamentos de presença passam a contar como chamada feita,
        // para não aparecerem como "chamada pendente" logo após o deploy.
        DB::table('meetings')
            ->whereNull('attendance_registered_at')
            ->whereIn('id', function ($query) {
                $query->select('meeting_id')->distinct()->from('meeting_attendances');
            })
            ->update(['attendance_registered_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings', 'attendance_registered_by')) {
                $table->dropColumn('attendance_registered_by');
            }
            if (Schema::hasColumn('meetings', 'attendance_registered_at')) {
                $table->dropColumn('attendance_registered_at');
            }
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('meeting_attendances', 'visitor_phone')) {
                $table->dropColumn('visitor_phone');
            }
        });
    }
};
