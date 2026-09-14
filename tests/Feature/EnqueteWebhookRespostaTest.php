<?php

namespace Tests\Feature;

use App\Models\Enquete;
use App\Models\EnqueteEnvio;
use App\Models\EnqueteResposta;
use App\Services\EnqueteService;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class EnqueteWebhookRespostaTest extends TestCase
{
    private const TELEFONE = '5561999999999';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('notificacao_enquetes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('tipo', 32)->default('texto');
            $table->json('opcoes');
            $table->boolean('ativa')->default(true);
            $table->timestamp('inicio_em')->nullable();
            $table->timestamp('fim_em')->nullable();
            $table->timestamps();
        });

        Schema::create('enquete_envios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enquete_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('telefone', 32);
            $table->string('status', 32)->default('enviado');
            $table->timestamp('enviado_em')->nullable();
            $table->timestamps();
        });

        Schema::create('enquete_respostas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enquete_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('telefone', 32);
            $table->string('resposta');
            $table->timestamp('respondido_em')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('enquete_respostas');
        Schema::dropIfExists('enquete_envios');
        Schema::dropIfExists('notificacao_enquetes');
        parent::tearDown();
    }

    public function test_mensagem_de_texto_comum_nao_dispara_resposta_automatica(): void
    {
        $this->criarEnvioPendente();
        $whatsapp = $this->mockWhatsApp(fn (MockInterface $m) => $m->shouldReceive('enviarMensagem')->never());

        $processou = $this->servico($whatsapp)->processarWebhookPayload(
            $this->payload(['conversation' => 'Bom dia, pastor!'])
        );

        $this->assertFalse($processou);
        $this->assertSame(0, EnqueteResposta::count());
    }

    public function test_reacao_nao_dispara_resposta_automatica(): void
    {
        $this->criarEnvioPendente();
        $whatsapp = $this->mockWhatsApp(fn (MockInterface $m) => $m->shouldReceive('enviarMensagem')->never());

        $processou = $this->servico($whatsapp)->processarWebhookPayload(
            $this->payload(['reactionMessage' => ['text' => "\u{1F64F}"]])
        );

        $this->assertFalse($processou);
    }

    public function test_clique_em_opcao_invalida_avisa_o_membro(): void
    {
        $this->criarEnvioPendente();
        $whatsapp = $this->mockWhatsApp(function (MockInterface $m) {
            $m->shouldReceive('enviarMensagem')
                ->once()
                ->with(self::TELEFONE, Mockery::pattern('/^Resposta incorreta/'))
                ->andReturn(['success' => true]);
        });

        $processou = $this->servico($whatsapp)->processarWebhookPayload($this->payload([
            'buttonsResponseMessage' => [
                'selectedButtonId' => '7',
                'selectedDisplayText' => 'Talvez',
            ],
        ]));

        $this->assertTrue($processou);
        $this->assertSame(0, EnqueteResposta::count());
    }

    public function test_clique_em_opcao_valida_registra_resposta(): void
    {
        $this->criarEnvioPendente();
        $whatsapp = $this->mockWhatsApp(function (MockInterface $m) {
            $m->shouldReceive('enviarMensagem')
                ->once()
                ->with(self::TELEFONE, Mockery::pattern('/^Obrigado pela sua resposta/'))
                ->andReturn(['success' => true]);
        });

        $processou = $this->servico($whatsapp)->processarWebhookPayload($this->payload([
            'buttonsResponseMessage' => [
                'selectedButtonId' => '1',
                'selectedDisplayText' => 'Sim! Estarei lá',
            ],
        ]));

        $this->assertTrue($processou);
        $this->assertSame('Sim! Estarei lá', EnqueteResposta::first()->resposta);
        $this->assertSame('respondido', EnqueteEnvio::first()->status);
    }

    public function test_enquete_com_periodo_vencido_nao_dispara_resposta(): void
    {
        $enquete = $this->criarEnvioPendente(now()->subDays(10));
        $enquete->update(['fim_em' => now()->subDays(5)]);

        $whatsapp = $this->mockWhatsApp(fn (MockInterface $m) => $m->shouldReceive('enviarMensagem')->never());

        $processou = $this->servico($whatsapp)->processarWebhookPayload($this->payload([
            'buttonsResponseMessage' => [
                'selectedButtonId' => '7',
                'selectedDisplayText' => 'Talvez',
            ],
        ]));

        $this->assertFalse($processou);
    }

    public function test_enquete_encerrada_nao_dispara_resposta(): void
    {
        $enquete = $this->criarEnvioPendente();
        $enquete->update(['ativa' => false]);

        $whatsapp = $this->mockWhatsApp(fn (MockInterface $m) => $m->shouldReceive('enviarMensagem')->never());

        $processou = $this->servico($whatsapp)->processarWebhookPayload($this->payload([
            'buttonsResponseMessage' => [
                'selectedButtonId' => '7',
                'selectedDisplayText' => 'Talvez',
            ],
        ]));

        $this->assertFalse($processou);
    }

    private function criarEnvioPendente(?\Illuminate\Support\Carbon $enviadoEm = null): Enquete
    {
        $enquete = Enquete::create([
            'titulo' => 'Kids Day ADEL',
            'descricao' => 'Você vai ao evento?',
            'tipo' => 'texto',
            'opcoes' => ['Sim! Estarei lá', 'Hoje não vou conseguir ir'],
            'ativa' => true,
        ]);

        EnqueteEnvio::create([
            'enquete_id' => $enquete->id,
            'member_id' => 1,
            'telefone' => self::TELEFONE,
            'status' => 'enviado',
            'enviado_em' => $enviadoEm ?? now()->subHour(),
        ]);

        return $enquete;
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    private function payload(array $message): array
    {
        return [
            'event' => 'Message',
            'data' => [
                'Info' => [
                    'Sender' => self::TELEFONE . '@s.whatsapp.net',
                    'Chat' => self::TELEFONE . '@s.whatsapp.net',
                    'IsFromMe' => false,
                    'IsGroup' => false,
                ],
                'Message' => $message,
            ],
        ];
    }

    private function mockWhatsApp(callable $expectativas): WhatsAppService
    {
        /** @var WhatsAppService&MockInterface $mock */
        $mock = Mockery::mock(WhatsAppService::class);
        $expectativas($mock);

        return $mock;
    }

    private function servico(WhatsAppService $whatsapp): EnqueteService
    {
        return new EnqueteService($whatsapp);
    }
}
