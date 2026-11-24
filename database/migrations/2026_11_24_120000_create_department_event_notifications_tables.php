<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('department_events')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['event_id', 'member_id'], 'dept_event_participant_unique');
        });
        Schema::create('department_event_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('department_events')->cascadeOnDelete();
            $table->string('instance_id', 191)->nullable();
            $table->string('instance_name')->nullable();
            $table->text('message')->nullable();
            $table->boolean('automatic_enabled')->default(false);
            $table->dateTime('scheduled_at')->nullable();
            $table->timestamps();
            $table->index(['automatic_enabled', 'scheduled_at'], 'dept_event_notifications_due');
        });
        Schema::create('department_event_notification_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('department_events')->cascadeOnDelete();
            $table->string('request_key', 100)->unique('dept_event_batch_request_unique');
            $table->string('mode', 20);
            $table->string('instance_id', 191);
            $table->string('instance_name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('department_event_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('department_event_notification_batches')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('recipient_name');
            $table->string('phone', 30)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->dateTime('claimed_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'id'], 'dept_event_delivery_pending');
            $table->unique(['batch_id', 'member_id'], 'dept_event_delivery_member_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_event_notification_deliveries');
        Schema::dropIfExists('department_event_notification_batches');
        Schema::dropIfExists('department_event_notifications');
        Schema::dropIfExists('department_event_participants');
    }
};
