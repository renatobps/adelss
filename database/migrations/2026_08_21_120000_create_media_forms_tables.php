<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('media_forms')) {
            Schema::create('media_forms', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('public_slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_accepting_responses')->default(true);
                $table->boolean('requires_identification')->default(true);
                $table->text('success_message')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'is_accepting_responses']);
            });
        }

        if (!Schema::hasTable('media_form_fields')) {
            Schema::create('media_form_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('media_form_id')->constrained('media_forms')->cascadeOnDelete();
                $table->string('label');
                $table->string('help_text')->nullable();
                $table->string('type', 32); // text | textarea | number | select | radio | checkbox | date | email | phone
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                // Campo removido do construtor e apagado de vez levaria as respostas
                // ja coletadas com ele, entao a exclusao aqui e sempre logica.
                $table->softDeletes();

                $table->index(['media_form_id', 'sort_order']);
            });
        }

        if (!Schema::hasTable('media_form_submissions')) {
            Schema::create('media_form_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('media_form_id')->constrained('media_forms')->cascadeOnDelete();
                $table->string('respondent_name')->nullable();
                $table->string('respondent_phone', 40)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('submitted_at')->useCurrent();
                $table->timestamps();

                $table->index(['media_form_id', 'submitted_at']);
            });
        }

        if (!Schema::hasTable('media_form_answers')) {
            Schema::create('media_form_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submission_id')->constrained('media_form_submissions')->cascadeOnDelete();
                $table->foreignId('field_id')->constrained('media_form_fields')->cascadeOnDelete();
                // `value` guarda sempre a forma legivel, usada em busca, tabela e
                // exportacao. `value_list` guarda a estrutura dos campos de
                // multipla escolha, que tem mais de um valor por resposta.
                $table->longText('value')->nullable();
                $table->json('value_list')->nullable();
                $table->timestamps();

                $table->unique(['submission_id', 'field_id']);
                $table->index('field_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_form_answers');
        Schema::dropIfExists('media_form_submissions');
        Schema::dropIfExists('media_form_fields');
        Schema::dropIfExists('media_forms');
    }
};
