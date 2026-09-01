<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\User;
use App\Services\FinancialNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class FinancialCorrectionInlineUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        $this->app->instance(FinancialNotificationService::class, Mockery::mock(FinancialNotificationService::class));

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

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(true);
            $table->date('due_date')->nullable();
            $table->string('status')->default('recebido');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('received_from_other')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('payment_type')->default('unico');
            $table->unsignedInteger('installments_count')->nullable();
            $table->unsignedInteger('installment_number')->nullable();
            $table->unsignedBigInteger('parent_transaction_id')->nullable();
            $table->string('document_number')->nullable();
            $table->text('notes')->nullable();
            $table->date('competence_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    private function actingAsAdmin(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin-correcao@test.local',
            'password' => bcrypt('secret'),
            'is_admin' => true,
        ]);

        $this->actingAs($user);
    }

    private function makeTransaction(array $overrides = []): FinancialTransaction
    {
        return FinancialTransaction::create(array_merge([
            'type' => 'receita',
            'transaction_date' => now()->toDateString(),
            'description' => 'Dízimo',
            'amount' => 65,
            'is_paid' => true,
            'status' => 'recebido',
            'received_from_other' => 'Importação 2026',
        ], $overrides));
    }

    public function test_atualiza_nome_para_membro_sem_notificar(): void
    {
        $this->actingAsAdmin();
        $member = Member::create(['name' => 'Ana Dizimista']);
        $tx = $this->makeTransaction();

        $this->patchJson(route('financial.correction.update-name', $tx), [
            'member_id' => $member->id,
        ])->assertOk()->assertJson(['success' => true, 'name' => 'Ana Dizimista']);

        $tx->refresh();
        $this->assertSame($member->id, $tx->member_id);
        $this->assertNull($tx->received_from_other);
    }

    public function test_limpa_nome_da_despesa(): void
    {
        $this->actingAsAdmin();
        $member = Member::create(['name' => 'João']);
        $tx = $this->makeTransaction([
            'type' => 'despesa',
            'description' => 'Material',
            'status' => 'pago',
            'member_id' => $member->id,
            'received_from_other' => null,
        ]);

        $this->patchJson(route('financial.correction.update-name', $tx), [
            'member_id' => '',
        ])->assertOk()->assertJson(['success' => true, 'name' => '']);

        $tx->refresh();
        $this->assertNull($tx->member_id);
    }

    public function test_atualiza_valor_na_tabela(): void
    {
        $this->actingAsAdmin();
        $tx = $this->makeTransaction(['amount' => 65]);

        $this->patchJson(route('financial.correction.update-amount', $tx), [
            'amount' => '130,50',
        ])->assertOk()->assertJson(['success' => true, 'amount' => '130,50']);

        $this->assertSame('130.50', $tx->fresh()->amount);
    }

    public function test_atualiza_descricao_na_tabela(): void
    {
        $this->actingAsAdmin();
        $tx = $this->makeTransaction(['description' => 'Oferta']);

        $this->patchJson(route('financial.correction.update-description', $tx), [
            'description' => 'Dízimo',
        ])->assertOk()->assertJson(['success' => true, 'description' => 'Dízimo']);

        $this->assertSame('Dízimo', $tx->fresh()->description);
    }
}
