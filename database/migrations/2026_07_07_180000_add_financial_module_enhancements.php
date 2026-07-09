<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('financial_categories', 'slug')) {
            Schema::table('financial_categories', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('financial_categories', 'sends_receipt')) {
            Schema::table('financial_categories', function (Blueprint $table) {
                $table->boolean('sends_receipt')->default(false)->after('type');
            });
        }

        if (!Schema::hasColumn('financial_transactions', 'created_by')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('competence_date')->constrained('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('financial_notification_logs')) {
            Schema::create('financial_notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('financial_transaction_id')->nullable()->constrained('financial_transactions')->nullOnDelete();
                $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
                $table->string('phone', 20)->nullable();
                $table->string('notification_type', 50);
                $table->enum('status', ['sent', 'failed'])->default('sent');
                $table->text('message')->nullable();
                $table->text('error')->nullable();
                $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['financial_transaction_id', 'notification_type'], 'fin_notif_tx_type_idx');
                $table->index('created_at', 'fin_notif_created_idx');
            });
        }

        $categories = DB::table('financial_categories')->get(['id', 'name', 'slug', 'sends_receipt']);
        foreach ($categories as $category) {
            if (!empty($category->slug) || (bool) $category->sends_receipt) {
                continue;
            }

            $nameLower = Str::lower(Str::ascii($category->name));
            $slug = null;
            $sendsReceipt = false;

            if (str_contains($nameLower, 'dizim')) {
                $slug = 'dizimo';
                $sendsReceipt = true;
            } elseif (str_contains($nameLower, 'oferta')) {
                $slug = 'oferta';
                $sendsReceipt = true;
            }

            DB::table('financial_categories')->where('id', $category->id)->update([
                'slug' => $slug,
                'sends_receipt' => $sendsReceipt,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_notification_logs');

        if (Schema::hasColumn('financial_transactions', 'created_by')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasColumn('financial_categories', 'slug')) {
            Schema::table('financial_categories', function (Blueprint $table) {
                $table->dropColumn(['slug', 'sends_receipt']);
            });
        }
    }
};
