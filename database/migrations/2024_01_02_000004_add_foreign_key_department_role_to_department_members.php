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
        Schema::table('department_members', function (Blueprint $table) {
            $table->foreign('department_role_id')->references('id')->on('department_roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_members', function (Blueprint $table) {
            $table->dropForeign(['department_role_id']);
        });
    }
};

