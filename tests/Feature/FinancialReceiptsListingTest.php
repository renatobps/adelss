<?php

namespace Tests\Feature;

use App\Http\Controllers\Financial\ReceiptController;
use App\Models\FinancialCategory;
use App\Models\FinancialNotificationLog;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinancialReceiptsListingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('type');
            $table->boolean('sends_receipt')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(true);
            $table->string('status')->default('pago');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('received_from_other')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('document_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_transaction_id')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('notification_type');
            $table->string('status');
            $table->text('message')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('triggered_by_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_transaction_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamps();
        });

        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'admin-recibos@test.local',
            'password' => bcrypt('secret'),
            'is_admin' => true,
        ]));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_transaction_attachments');
        Schema::dropIfExists('financial_notification_logs');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('financial_categories');
        Schema::dropIfExists('financial_contacts');
        Schema::dropIfExists('members');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function listing(array $query = []): array
    {
        $request = Request::create('/financial/receipts', 'GET', array_merge([
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ], $query));

        return app(ReceiptController::class)->index($request)->getData();
    }

    private function transaction(array $overrides = []): FinancialTransaction
    {
        return FinancialTransaction::create(array_merge([
            'type' => 'receita',
            'transaction_date' => '2026-09-10',
            'description' => 'Dízimo',
            'amount' => 150,
            'is_paid' => true,
            'status' => 'recebido',
        ], $overrides));
    }

    private function attachment(FinancialTransaction $transaction, bool $generated = false): FinancialTransactionAttachment
    {
        $dir = $generated
            ? FinancialTransactionAttachment::GENERATED_DIR
            : 'financial/transactions/attachments';

        return FinancialTransactionAttachment::create([
            'transaction_id' => $transaction->id,
            'file_path' => $dir.'/recibo-'.$transaction->id.'.pdf',
            'file_name' => 'recibo-'.$transaction->id.'.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 2048,
        ]);
    }

    public function test_lista_recibos_enviados_e_emitidos_pelo_sistema(): void
    {
        $member = Member::create(['name' => 'Ana Dizimista']);
        $upload = $this->attachment($this->transaction(['member_id' => $member->id]));
        $generated = $this->attachment($this->transaction([
            'type' => 'despesa',
            'description' => 'Prebenda pastoral',
            'status' => 'pago',
        ]), generated: true);

        $data = $this->listing();

        $this->assertSame('arquivos', $data['tab']);
        $this->assertEqualsCanonicalizing(
            [$upload->id, $generated->id],
            $data['receipts']->pluck('id')->all()
        );
        $this->assertSame(2, $data['summary']['total']);
        $this->assertSame(1, $data['summary']['uploaded']);
        $this->assertSame(1, $data['summary']['generated']);
    }

    public function test_ignora_recibos_fora_do_periodo(): void
    {
        $this->attachment($this->transaction(['transaction_date' => '2026-08-20']));

        $this->assertSame(0, $this->listing()['receipts']->total());
    }

    public function test_filtra_por_origem(): void
    {
        $upload = $this->attachment($this->transaction());
        $this->attachment($this->transaction(['description' => 'Prebenda']), generated: true);

        $data = $this->listing(['origin' => 'upload']);

        $this->assertSame([$upload->id], $data['receipts']->pluck('id')->all());
        $this->assertSame(1, $data['summary']['total']);
        $this->assertSame(0, $data['summary']['generated']);
    }

    public function test_busca_por_numero_do_recibo_e_por_nome(): void
    {
        $member = Member::create(['name' => 'Ana Dizimista']);
        $wanted = $this->attachment($this->transaction(['member_id' => $member->id]));
        $this->attachment($this->transaction(['description' => 'Oferta especial']));

        $byNumber = $this->listing(['search' => str_pad((string) $wanted->transaction_id, 6, '0', STR_PAD_LEFT)]);
        $this->assertSame([$wanted->id], $byNumber['receipts']->pluck('id')->all());

        $byName = $this->listing(['search' => 'Dizimista']);
        $this->assertSame([$wanted->id], $byName['receipts']->pluck('id')->all());
    }

    public function test_aba_pendentes_lista_apenas_despesas_pagas_sem_recibo(): void
    {
        $semRecibo = $this->transaction(['type' => 'despesa', 'description' => 'Energia', 'status' => 'pago']);
        $comRecibo = $this->transaction(['type' => 'despesa', 'description' => 'Água', 'status' => 'pago']);
        $this->attachment($comRecibo);
        $this->transaction(['type' => 'despesa', 'description' => 'Aluguel', 'is_paid' => false, 'status' => 'a_pagar']);
        $this->transaction(); // receita sem anexo não é cobrada

        $data = $this->listing(['tab' => 'pendentes']);

        $this->assertSame('pendentes', $data['tab']);
        $this->assertSame([$semRecibo->id], $data['pending']->pluck('id')->all());
        $this->assertSame(1, $data['summary']['pending']);
        $this->assertSame(150.0, $data['summary']['pending_amount']);
    }

    public function test_aba_de_dizimos_mostra_comprovante_enviado_por_whatsapp(): void
    {
        $member = Member::create(['name' => 'Ana Dizimista']);
        $enviado = $this->transaction(['member_id' => $member->id]);
        $naoEnviado = $this->transaction(['description' => 'Oferta', 'amount' => 40]);
        $this->transaction(['type' => 'despesa', 'description' => 'Energia', 'status' => 'pago']);
        FinancialNotificationLog::create([
            'financial_transaction_id' => $enviado->id,
            'member_id' => $member->id,
            'notification_type' => FinancialNotificationLog::TYPE_RECEIPT_MEMBER,
            'status' => 'sent',
        ]);

        $data = $this->listing(['tab' => 'receitas']);

        $this->assertSame('receitas', $data['tab']);
        $this->assertEqualsCanonicalizing(
            [$enviado->id, $naoEnviado->id],
            $data['revenues']->pluck('id')->all()
        );
        $this->assertSame(2, $data['summary']['revenues']);
        $this->assertNotNull($data['revenues']->firstWhere('id', $enviado->id)->notificationLogs->first());
        $this->assertNull($data['revenues']->firstWhere('id', $naoEnviado->id)->notificationLogs->first());
    }

    public function test_arquivo_do_recibo_e_servido_para_download(): void
    {
        Storage::fake('public');
        $attachment = $this->attachment($this->transaction());
        Storage::disk('public')->put($attachment->file_path, 'conteudo-do-recibo');

        $this->get(route('financial.receipts.file', $attachment))->assertOk();
        $this->get(route('financial.receipts.download', $attachment))
            ->assertOk()
            ->assertDownload($attachment->file_name);
    }

    public function test_arquivo_ausente_no_storage_retorna_404(): void
    {
        Storage::fake('public');
        $attachment = $this->attachment($this->transaction());

        $this->get(route('financial.receipts.file', $attachment))->assertNotFound();
    }

    public function test_exportacao_csv_traz_os_recibos_filtrados(): void
    {
        $category = FinancialCategory::create(['name' => 'Dízimos', 'type' => 'receita']);
        $member = Member::create(['name' => 'Ana Dizimista']);
        $this->attachment($this->transaction([
            'member_id' => $member->id,
            'category_id' => $category->id,
        ]));

        $response = $this->get(route('financial.receipts.export', [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]))->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Ana Dizimista', $csv);
        $this->assertStringContainsString('Enviado por upload', $csv);
        $this->assertStringContainsString('150,00', $csv);
    }
}
