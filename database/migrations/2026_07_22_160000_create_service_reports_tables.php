<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('service_reports')) {
            Schema::create('service_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
                $table->date('report_date');
                $table->string('service_type');
                $table->string('custom_type_label')->nullable();
                $table->time('start_time')->nullable();
                $table->foreignId('preacher_member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->string('external_preacher_name')->nullable();
                $table->string('message_theme')->nullable();
                $table->string('campaign_series')->nullable();
                $table->text('description')->nullable();
                $table->text('highlights')->nullable();
                $table->unsignedInteger('members_present_count')->nullable();
                $table->unsignedInteger('visitors_count')->nullable();
                $table->unsignedInteger('children_count')->nullable();
                $table->unsignedInteger('volunteers_count')->nullable();
                $table->decimal('offering_total', 10, 2)->nullable();
                $table->string('status')->default('rascunho');
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->index(['report_date', 'status']);
                $table->index('service_type');
            });
        }

        if (!Schema::hasTable('service_report_attendances')) {
            Schema::create('service_report_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_report_id')->constrained('service_reports')->cascadeOnDelete();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->boolean('present')->default(true);
                $table->timestamps();

                $table->unique(['service_report_id', 'member_id'], 'srv_rep_att_unique');
            });
        }

        if (!Schema::hasTable('service_report_visitors')) {
            Schema::create('service_report_visitors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_report_id')->constrained('service_reports')->cascadeOnDelete();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->foreignId('invited_by_member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->foreignId('converted_to_member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('service_report_offerings')) {
            Schema::create('service_report_offerings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_report_id')->constrained('service_reports')->cascadeOnDelete();
                $table->string('payment_method');
                $table->decimal('amount', 10, 2);
            });
        }

        if (!Schema::hasTable('service_report_photos')) {
            Schema::create('service_report_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_report_id')->constrained('service_reports')->cascadeOnDelete();
                $table->string('path');
                $table->timestamp('uploaded_at')->nullable();
            });
        }

        if (!Schema::hasTable('service_report_spiritual_decisions')) {
            Schema::create('service_report_spiritual_decisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_report_id')->constrained('service_reports')->cascadeOnDelete();
                $table->string('type');
                $table->string('person_name')->nullable();
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('service_report_settings')) {
            Schema::create('service_report_settings', function (Blueprint $table) {
                $table->id();
                $table->string('mode')->default('detalhado');
                $table->unsignedInteger('consecutive_absences_alert_threshold')->nullable();
                $table->unsignedInteger('visitor_no_return_days_alert')->nullable();
                $table->boolean('auto_register_visitor_as_member')->default(false);
                $table->boolean('enable_children_count')->default(false);
                $table->boolean('enable_volunteers_count')->default(false);
                $table->boolean('enable_payment_method_breakdown')->default(false);
                $table->boolean('enable_spiritual_decisions')->default(false);
                $table->json('custom_fields')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_report_spiritual_decisions');
        Schema::dropIfExists('service_report_photos');
        Schema::dropIfExists('service_report_offerings');
        Schema::dropIfExists('service_report_visitors');
        Schema::dropIfExists('service_report_attendances');
        Schema::dropIfExists('service_reports');
        Schema::dropIfExists('service_report_settings');
    }
};
