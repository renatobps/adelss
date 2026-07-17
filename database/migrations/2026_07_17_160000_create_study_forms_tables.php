<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_id')->constrained('studies')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('public_slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('study_form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_form_id')->constrained('study_forms')->cascadeOnDelete();
            $table->text('prompt');
            $table->string('type', 32); // dissertative | multiple_choice | true_false
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('study_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_form_id')->constrained('study_forms')->cascadeOnDelete();
            $table->string('respondent_name');
            $table->string('ip_address', 45)->nullable();
            $table->unsignedSmallInteger('score')->nullable();
            $table->unsignedSmallInteger('max_score')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('study_form_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('study_form_submissions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('study_form_questions')->cascadeOnDelete();
            $table->longText('answer_text')->nullable();
            $table->string('selected_option')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_form_answers');
        Schema::dropIfExists('study_form_submissions');
        Schema::dropIfExists('study_form_questions');
        Schema::dropIfExists('study_forms');
    }
};
