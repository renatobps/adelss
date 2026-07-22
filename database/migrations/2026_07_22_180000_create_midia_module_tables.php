<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('google_drive_settings')) {
            Schema::create('google_drive_settings', function (Blueprint $table) {
                $table->id();
                $table->string('connected_account_email')->nullable();
                $table->text('access_token')->nullable();
                $table->text('refresh_token')->nullable();
                $table->timestamp('token_expires_at')->nullable();
                $table->string('root_folder_id')->nullable();
                $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('connected_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_folders')) {
            Schema::create('media_folders', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('google_drive_folder_id');
                $table->foreignId('parent_folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media_files')) {
            Schema::create('media_files', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('original_filename');
                $table->string('mime_type');
                $table->unsignedBigInteger('size')->default(0);
                $table->string('google_drive_file_id');
                $table->foreignId('media_folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
                $table->string('category'); // foto | documento
                $table->string('module_reference')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->text('description')->nullable();
                $table->json('tags')->nullable();
                $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['category', 'media_folder_id']);
                $table->index(['module_reference', 'reference_id']);
            });
        }

        if (!Schema::hasTable('instagram_settings')) {
            Schema::create('instagram_settings', function (Blueprint $table) {
                $table->id();
                $table->string('instagram_business_account_id')->nullable();
                $table->string('facebook_page_id')->nullable();
                $table->text('access_token')->nullable();
                $table->timestamp('token_expires_at')->nullable();
                $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('connected_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('scheduled_posts')) {
            Schema::create('scheduled_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('media_file_id')->nullable()->constrained('media_files')->nullOnDelete();
                $table->string('image_path')->nullable();
                $table->text('caption')->nullable();
                $table->dateTime('scheduled_for');
                $table->string('status')->default('agendado'); // agendado|publicando|publicado|erro
                $table->string('instagram_media_id')->nullable();
                $table->text('error_message')->nullable();
                $table->dateTime('published_at')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['status', 'scheduled_for']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
        Schema::dropIfExists('instagram_settings');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('media_folders');
        Schema::dropIfExists('google_drive_settings');
    }
};
