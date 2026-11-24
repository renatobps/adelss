<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['department_role_id', 'member_id']);
        });

        $timestamp = now();
        DB::table('department_roles')->join('members', 'members.id', '=', 'department_roles.responsible_member_id')
            ->select('department_roles.id', 'department_roles.responsible_member_id')->orderBy('department_roles.id')
            ->chunkById(200, function ($roles) use ($timestamp) {
                foreach ($roles as $role) {
                    DB::table('department_role_assignments')->insertOrIgnore([
                        'department_role_id' => $role->id, 'member_id' => $role->responsible_member_id,
                        'created_at' => $timestamp, 'updated_at' => $timestamp,
                    ]);
                }
            }, 'department_roles.id', 'id');
        DB::table('department_members')->join('department_roles', function ($join) {
            $join->on('department_roles.id', '=', 'department_members.department_role_id')
                ->on('department_roles.department_id', '=', 'department_members.department_id');
        })->join('members', 'members.id', '=', 'department_members.member_id')
            ->select('department_members.id', 'department_members.department_role_id', 'department_members.member_id')
            ->orderBy('department_members.id')->chunkById(200, function ($links) use ($timestamp) {
                foreach ($links as $link) {
                    DB::table('department_role_assignments')->insertOrIgnore([
                        'department_role_id' => $link->department_role_id, 'member_id' => $link->member_id,
                        'created_at' => $timestamp, 'updated_at' => $timestamp,
                    ]);
                }
            }, 'department_members.id', 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('department_role_assignments');
    }
};
