<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_roles', function (Blueprint $table) {
            $table->foreignId('responsible_member_id')->nullable()->constrained('members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('department_roles', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                $table->dropColumn('responsible_member_id');
            } else {
                $table->dropConstrainedForeignId('responsible_member_id');
            }
        });
    }
};
