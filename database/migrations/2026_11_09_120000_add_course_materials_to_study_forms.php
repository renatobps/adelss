<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_forms', function (Blueprint $table) {
            $table->string('import_key')->nullable()->unique();
            $table->string('material_path')->nullable();
            $table->string('exam_pdf_path')->nullable();
        });
        Schema::table('study_form_questions', function (Blueprint $table) {
            $table->boolean('review_required')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('study_form_questions', fn (Blueprint $table) => $table->dropColumn('review_required'));
        Schema::table('study_forms', function (Blueprint $table) {
            $table->dropUnique(['import_key']);
            $table->dropColumn(['import_key', 'material_path', 'exam_pdf_path']);
        });
    }
};
