<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notificacoes_enviadas')) {
            return;
        }

        Schema::table('notificacoes_enviadas', function (Blueprint $table) {
            if (!Schema::hasColumn('notificacoes_enviadas', 'whatsapp_message_id')) {
                $table->string('whatsapp_message_id', 80)->nullable()->after('status');
                $table->index('whatsapp_message_id', 'notificacoes_enviadas_wa_msg_id_idx');
            }
            if (!Schema::hasColumn('notificacoes_enviadas', 'recebido_em')) {
                $table->timestamp('recebido_em')->nullable()->after('data_envio');
            }
            if (!Schema::hasColumn('notificacoes_enviadas', 'lido_em')) {
                $table->timestamp('lido_em')->nullable()->after('recebido_em');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notificacoes_enviadas')) {
            return;
        }

        Schema::table('notificacoes_enviadas', function (Blueprint $table) {
            if (Schema::hasColumn('notificacoes_enviadas', 'whatsapp_message_id')) {
                $table->dropIndex('notificacoes_enviadas_wa_msg_id_idx');
                $table->dropColumn('whatsapp_message_id');
            }
            foreach (['recebido_em', 'lido_em'] as $col) {
                if (Schema::hasColumn('notificacoes_enviadas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
