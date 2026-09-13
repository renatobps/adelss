<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_areas')) {
            return;
        }

        Schema::table('service_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('service_areas', 'whatsapp_group_jid')) {
                $table->string('whatsapp_group_jid', 80)->nullable()->after('sort_order');
            }
            if (! Schema::hasColumn('service_areas', 'whatsapp_group_name')) {
                $table->string('whatsapp_group_name', 150)->nullable()->after('whatsapp_group_jid');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_areas')) {
            return;
        }

        Schema::table('service_areas', function (Blueprint $table) {
            if (Schema::hasColumn('service_areas', 'whatsapp_group_name')) {
                $table->dropColumn('whatsapp_group_name');
            }
            if (Schema::hasColumn('service_areas', 'whatsapp_group_jid')) {
                $table->dropColumn('whatsapp_group_jid');
            }
        });
    }
};
