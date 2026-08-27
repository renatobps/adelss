<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notificacoes_enviadas')) {
            return;
        }

        if (! Schema::hasColumn('notificacoes_enviadas', 'origem')) {
            Schema::table('notificacoes_enviadas', function (Blueprint $table) {
                $table->string('origem', 32)->nullable()->after('tipo_notificacao')->index();
            });
        }

        DB::table('notificacoes_enviadas')
            ->whereNull('origem')
            ->update(['origem' => 'painel']);

        if (! Schema::hasTable('financial_notification_logs')) {
            return;
        }

        $jaImportado = DB::table('notificacoes_enviadas')
            ->where('origem', 'financeiro')
            ->where('tipo_notificacao', 'like', 'financeiro:%')
            ->exists();

        if ($jaImportado) {
            return;
        }

        $logs = DB::table('financial_notification_logs')->orderBy('id')->get();
        foreach ($logs as $log) {
            $sucesso = in_array((string) $log->status, ['sent', 'enviada', 'success'], true);
            DB::table('notificacoes_enviadas')->insert([
                'member_id' => $log->member_id,
                'telefone' => $log->phone,
                'tipo_notificacao' => 'financeiro:'.($log->notification_type ?: 'aviso'),
                'origem' => 'financeiro',
                'mensagem' => ($log->message !== null && $log->message !== '') ? $log->message : '[sem texto]',
                'data_envio' => $log->created_at ?? now(),
                'status' => $sucesso ? 'enviada' : 'erro',
                'erro_detalhes' => $log->error,
                'tentativas' => 1,
                'created_at' => $log->created_at ?? now(),
                'updated_at' => $log->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notificacoes_enviadas') || ! Schema::hasColumn('notificacoes_enviadas', 'origem')) {
            return;
        }

        DB::table('notificacoes_enviadas')
            ->where('origem', 'financeiro')
            ->where('tipo_notificacao', 'like', 'financeiro:%')
            ->delete();

        Schema::table('notificacoes_enviadas', function (Blueprint $table) {
            $table->dropIndex(['origem']);
            $table->dropColumn('origem');
        });
    }
};
