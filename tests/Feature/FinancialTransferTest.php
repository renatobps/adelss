<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransfer;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialTransferTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('financial.whatsapp.expense_alert_enabled', false);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 32)->default('caixa');
            $table->string('bank_name')->nullable();
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->string('color', 16)->default('#ef4444');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('type');
            $table->date('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_account_id');
            $table->unsignedBigInteger('to_account_id');
            $table->date('transfer_date');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_transfers');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('financial_accounts');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    private function actingAsAdmin(): User
    {
        $user = User::create([
            'name' => 'Tesoureiro',
            'email' => 'tesoureiro-transferencia@test.local',
            'password' => bcrypt('secret'),
            'is_admin' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }

    private function caixa(): FinancialAccount
    {
        return FinancialAccount::create([
            'name' => 'Caixa',
            'type' => FinancialAccount::TYPE_CAIXA,
            'initial_balance' => 500,
            'color' => '#ef4444',
            'is_active' => true,
        ]);
    }

    private function mercadoPago(): FinancialAccount
    {
        return FinancialAccount::create([
            'name' => 'Mercado Pago',
            'type' => FinancialAccount::TYPE_MERCADO_PAGO,
            'initial_balance' => 100,
            'color' => '#3b82f6',
            'is_active' => true,
        ]);
    }

    public function test_deposito_do_caixa_na_mercado_pago_move_o_saldo(): void
    {
        $this->actingAsAdmin();
        $caixa = $this->caixa();
        $mp = $this->mercadoPago();

        $this->post(route('financial.transfers.store'), [
            'from_account_id' => $caixa->id,
            'to_account_id' => $mp->id,
            'transfer_date' => now()->toDateString(),
            'amount' => 200,
            'notes' => 'Depósito do caixa da semana',
        ])->assertRedirect(route('financial.accounts.index', ['status' => 'ativas']));

        $this->assertSame(1, FinancialTransfer::count());
        $this->assertSame(300.0, $caixa->fresh()->currentBalance());
        $this->assertSame(300.0, $mp->fresh()->currentBalance());
    }

    public function test_transferencia_nao_vira_receita_nem_despesa(): void
    {
        $this->actingAsAdmin();

        $this->post(route('financial.transfers.store'), [
            'from_account_id' => $this->caixa()->id,
            'to_account_id' => $this->mercadoPago()->id,
            'transfer_date' => now()->toDateString(),
            'amount' => 50,
        ]);

        $this->assertSame(0, FinancialTransaction::count());
    }

    public function test_origem_e_destino_precisam_ser_diferentes(): void
    {
        $this->actingAsAdmin();
        $caixa = $this->caixa();

        $this->from(route('financial.accounts.index'))
            ->post(route('financial.transfers.store'), [
                'from_account_id' => $caixa->id,
                'to_account_id' => $caixa->id,
                'transfer_date' => now()->toDateString(),
                'amount' => 100,
            ])
            ->assertSessionHasErrors('from_account_id');

        $this->assertSame(0, FinancialTransfer::count());
    }

    public function test_estorno_devolve_o_saldo_para_a_conta_de_origem(): void
    {
        $this->actingAsAdmin();
        $caixa = $this->caixa();
        $mp = $this->mercadoPago();

        $transfer = FinancialTransfer::create([
            'from_account_id' => $caixa->id,
            'to_account_id' => $mp->id,
            'transfer_date' => now()->toDateString(),
            'amount' => 150,
        ]);

        $this->delete(route('financial.transfers.destroy', $transfer))
            ->assertRedirect(route('financial.accounts.index', ['status' => 'ativas']));

        $this->assertSame(500.0, $caixa->fresh()->currentBalance());
        $this->assertSame(100.0, $mp->fresh()->currentBalance());
    }

    public function test_rotulo_do_campo_mostra_pix_e_dinheiro(): void
    {
        $this->assertSame('Dinheiro (Caixa)', $this->caixa()->paymentOptionLabel());
        $this->assertSame('PIX (Mercado Pago)', $this->mercadoPago()->paymentOptionLabel());
    }
}
