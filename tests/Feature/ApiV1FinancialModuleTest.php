<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiV1FinancialModuleTest extends TestCase
{
    private User $admin;

    private User $member;

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
            $table->boolean('must_change_password')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->unsignedBigInteger('member_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('ativo');
            $table->timestamps();
        });

        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type')->default('caixa');
            $table->string('bank_name')->nullable();
            $table->decimal('initial_balance', 12, 2)->default(0);
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('description')->nullable();
            $table->string('type');
            $table->boolean('sends_receipt')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('type')->default('pessoa_juridica');
            $table->string('cpf')->nullable();
            $table->string('cnpj')->nullable();
            $table->string('phone_1')->nullable();
            $table->string('phone_2')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('transaction_date')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_paid')->default(false);
            $table->date('due_date')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('received_from_other')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('culto_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->string('payment_type')->nullable();
            $table->integer('installments_count')->nullable();
            $table->integer('installment_number')->nullable();
            $table->unsignedBigInteger('parent_transaction_id')->nullable();
            $table->unsignedBigInteger('fixed_expense_id')->nullable();
            $table->string('document_number')->nullable();
            $table->string('external_ref')->nullable();
            $table->text('notes')->nullable();
            $table->date('competence_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_account_id');
            $table->unsignedBigInteger('to_account_id');
            $table->date('transfer_date');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_fixed_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->unsignedTinyInteger('due_day');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('amount_variable')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transaction_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_automations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->boolean('enabled')->default(false);
            $table->text('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->decimal('goal_amount', 12, 2)->nullable();
            $table->decimal('installment_amount', 12, 2)->default(0);
            $table->unsignedInteger('installments_count')->default(1);
            $table->date('first_due_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('ativa');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('user_id');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('campaign_sponsors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reminders_enabled')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_sponsor_id');
            $table->unsignedInteger('installment_number');
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();
            $table->string('status')->default('pendente');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('receipt_number')->nullable();
            $table->timestamp('receipt_sent_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->date('reminder_sent_on')->nullable();
            $table->timestamps();
        });

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@adelss.test',
            'password' => Hash::make('secret123'),
            'is_admin' => true,
        ]);

        $this->member = User::query()->create([
            'name' => 'Ana',
            'email' => 'ana@adelss.test',
            'password' => Hash::make('secret123'),
            'is_admin' => false,
        ]);
    }

    public function test_modulo_financeiro_respeita_permissao(): void
    {
        $this->actingAs($this->member, 'sanctum')->getJson('/api/v1/financial/module')
            ->assertStatus(403)
            ->assertJsonPath('code', 'financial_forbidden');

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/module')
            ->assertOk()
            ->assertJsonPath('data.capabilities.can_view_summary', true)
            ->assertJsonPath('data.capabilities.can_view_transactions', true)
            ->assertJsonCount(12, 'data.nav');
    }

    public function test_resumo_transacoes_catalogos_e_operacoes(): void
    {
        $account = FinancialAccount::query()->create([
            'name' => 'Caixa',
            'type' => FinancialAccount::TYPE_CAIXA,
            'initial_balance' => 100,
            'color' => '#22c55e',
            'is_active' => true,
        ]);
        $category = FinancialCategory::query()->create([
            'name' => 'Dízimo',
            'type' => 'receita',
        ]);

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/summary')
            ->assertOk()
            ->assertJsonPath('data.in_total', 0);

        $created = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/financial/transactions', [
            'type' => 'receita',
            'transaction_date' => now()->toDateString(),
            'description' => 'Dízimo do mês',
            'amount' => 150.5,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'is_paid' => true,
            'received_from_other' => 'Visitante',
        ]);
        $created->assertCreated()->assertJsonPath('data.description', 'Dízimo do mês');
        $id = $created->json('data.id');

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/transactions')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.summary.total_received', 150.5);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/financial/transactions/'.$id.'/correction', [
                'description' => 'Dízimo corrigido',
                'amount' => 200,
            ])
            ->assertOk()
            ->assertJsonPath('data.amount', 200);

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/accounts')
            ->assertOk()
            ->assertJsonPath('data.items.0.name', 'Caixa');

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/financial/categories', [
            'name' => 'Energia',
            'type' => 'despesa',
        ])->assertCreated()->assertJsonPath('data.name', 'Energia');

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/financial/contacts', [
            'name' => 'Fornecedor Luz',
            'type' => 'pessoa_juridica',
        ])->assertCreated();

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/financial/cost-centers', [
            'name' => 'Templo',
        ])->assertCreated();

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/financial/fixed-expenses', [
            'description' => 'Aluguel',
            'amount' => 800,
            'due_day' => 10,
            'category_id' => $category->id,
            'account_id' => $account->id,
        ])->assertCreated();

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/fixed-expenses')
            ->assertOk()
            ->assertJsonPath('data.items.0.description', 'Aluguel');

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/financial/campaigns', [
            'name' => 'Reforma',
            'installment_amount' => 50,
            'installments_count' => 12,
        ])->assertCreated()->assertJsonPath('data.name', 'Reforma');

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/campaigns')
            ->assertOk()
            ->assertJsonPath('data.items.0.name', 'Reforma');

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/reports')
            ->assertOk()
            ->assertJsonPath('data.in_total', 200);

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/receipts')
            ->assertOk();

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/automations')
            ->assertOk()
            ->assertJsonPath('data.can_manage', true);
    }

    public function test_transferencia_entre_contas(): void
    {
        $from = FinancialAccount::query()->create([
            'name' => 'Caixa',
            'type' => FinancialAccount::TYPE_CAIXA,
            'initial_balance' => 200,
            'color' => '#22c55e',
            'is_active' => true,
        ]);
        $to = FinancialAccount::query()->create([
            'name' => 'Mercado Pago',
            'type' => FinancialAccount::TYPE_MERCADO_PAGO,
            'initial_balance' => 0,
            'color' => '#3b82f6',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/financial/transfers', [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'transfer_date' => now()->toDateString(),
            'amount' => 80,
        ])->assertCreated();

        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/financial/accounts')
            ->assertOk()
            ->assertJsonPath('data.transfers.0.amount', 80);
    }

    public function test_upload_e_exclusao_de_recibo(): void
    {
        Storage::fake('public');

        $account = FinancialAccount::query()->create([
            'name' => 'Caixa',
            'type' => FinancialAccount::TYPE_CAIXA,
            'initial_balance' => 0,
            'color' => '#22c55e',
            'is_active' => true,
        ]);
        $tx = FinancialTransaction::query()->create([
            'type' => 'despesa',
            'transaction_date' => now()->toDateString(),
            'description' => 'Energia',
            'amount' => 90,
            'is_paid' => true,
            'status' => 'pago',
            'account_id' => $account->id,
        ]);

        $upload = $this->actingAs($this->admin, 'sanctum')->post(
            '/api/v1/financial/transactions/'.$tx->id.'/attachments',
            ['file' => UploadedFile::fake()->image('recibo.jpg', 80, 60)],
            ['Accept' => 'application/json']
        );
        $upload->assertCreated()
            ->assertJsonPath('data.attachments_count', 1)
            ->assertJsonPath('data.attachments.0.file_name', 'recibo.jpg');

        $attachmentId = $upload->json('data.attachments.0.id');
        Storage::disk('public')->assertExists(
            $tx->attachments()->first()->file_path
        );

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/v1/financial/transactions/'.$tx->id.'/attachments/'.$attachmentId)
            ->assertOk()
            ->assertJsonPath('data.attachments_count', 0);

        $this->actingAs($this->admin, 'sanctum')->post(
            '/api/v1/financial/transactions/'.$tx->id.'/attachments',
            ['files' => [UploadedFile::fake()->image('nota.png', 40, 40)]],
            ['Accept' => 'application/json']
        )->assertCreated()
            ->assertJsonPath('data.attachments_count', 1)
            ->assertJsonPath('data.attachments.0.file_name', 'nota.png');
    }

    public function test_lista_e_abre_recibos_arquivados(): void
    {
        Storage::fake('public');

        $account = FinancialAccount::query()->create([
            'name' => 'Caixa',
            'type' => FinancialAccount::TYPE_CAIXA,
            'initial_balance' => 0,
            'color' => '#22c55e',
            'is_active' => true,
        ]);
        $tx = FinancialTransaction::query()->create([
            'type' => 'despesa',
            'transaction_date' => now()->toDateString(),
            'description' => 'Água',
            'amount' => 45,
            'is_paid' => true,
            'status' => 'pago',
            'account_id' => $account->id,
        ]);
        $path = UploadedFile::fake()->image('agua.jpg', 40, 40)
            ->store('financial/transactions/attachments', 'public');
        $attachment = FinancialTransactionAttachment::query()->create([
            'transaction_id' => $tx->id,
            'file_path' => $path,
            'file_name' => 'agua.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 1200,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/financial/receipts?tab=arquivos')
            ->assertOk()
            ->assertJsonPath('data.tab', 'arquivos')
            ->assertJsonPath('data.items.0.file_name', 'agua.jpg')
            ->assertJsonPath('data.items.0.available', true)
            ->assertJsonPath('data.items.0.can_open', true);

        $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/financial/transactions/'.$tx->id.'/attachments/'.$attachment->id, [
                'Accept' => '*/*',
            ])
            ->assertOk();

        $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/financial/transactions/'.$tx->id.'/receipt')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
