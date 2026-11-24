<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('department_project_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 191);
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_project_actions');
        Schema::dropIfExists('department_projects');
    }
};
