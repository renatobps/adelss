<?php

namespace Tests\Feature;

use App\Models\NotificacaoEnviada;
use App\Services\WhatsAppSendHistory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppSendHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('notificacoes_enviadas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('telefone')->nullable();
            $table->string('tipo_notificacao')->default('custom');
            $table->string('origem')->nullable();
            $table->text('mensagem');
            $table->timestamp('data_envio')->nullable();
            $table->string('status')->default('pendente');
            $table->string('whatsapp_message_id')->nullable();
            $table->json('resposta_api')->nullable();
            $table->integer('tentativas')->default(0);
            $table->text('erro_detalhes')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('notificacoes_enviadas');
        parent::tearDown();
    }

    public function test_grava_sucesso_e_erro_no_historico(): void
    {
        $history = app(WhatsAppSendHistory::class);

        $history->record('5561999999999', 'Olá igreja', ['success' => true, 'data' => ['id' => 'ABC']], 'custom');
        $history->record('5561888888888', 'Falhou', ['success' => false, 'error' => 'timeout'], 'custom');

        $this->assertSame(2, NotificacaoEnviada::count());
        $this->assertSame(1, NotificacaoEnviada::where('status', 'enviada')->count());
        $this->assertSame(1, NotificacaoEnviada::where('status', 'erro')->count());
        $this->assertNotNull(NotificacaoEnviada::where('status', 'erro')->value('erro_detalhes'));
    }
}
