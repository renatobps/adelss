<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreDespesaPayeeTest extends TestCase
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
            $table->boolean('is_paid')->default(false);
            $table->date('due_date')->nullable();
            $table->string('status')->default('pago');
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

        Schema::create('financial_transaction_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('financial_transaction_attachments');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    private function actingAsAdmin(): User
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin-despesa@test.local',
            'password' => bcrypt('secret'),
            'is_admin' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }

    public function test_pagamento_a_membro_exige_recibo_assinado(): void
    {
        $this->actingAsAdmin();
        $member = Member::create(['name' => 'João da Silva']);

        $this->from(route('financial.transactions.index'))
            ->post(route('financial.transactions.store.despesa'), [
                'transaction_date' => now()->toDateString(),
                'description' => 'Ajuda de custo',
                'amount' => 50,
                'is_paid' => 1,
                'member_id' => $member->id,
            ])
            ->assertSessionHasErrors('attachments');

        $this->assertSame(0, FinancialTransaction::count());
    }

    public function test_salva_despesa_para_membro_com_recibo(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $member = Member::create(['name' => 'Maria Souza']);

        $this->post(route('financial.transactions.store.despesa'), [
            'transaction_date' => now()->toDateString(),
            'description' => 'Ajuda de custo',
            'amount' => 80,
            'is_paid' => 1,
            'member_id' => $member->id,
            'attachments' => [
                UploadedFile::fake()->create('recibo.pdf', 20, 'application/pdf'),
            ],
        ])->assertRedirect(route('financial.transactions.index'));

        $tx = FinancialTransaction::first();
        $this->assertNotNull($tx);
        $this->assertSame('despesa', $tx->type);
        $this->assertSame($member->id, $tx->member_id);
        $this->assertNull($tx->received_from_other);
        $this->assertSame('Maria Souza', $tx->source_name);
        $this->assertSame(1, $tx->attachments()->count());
    }

    public function test_salva_despesa_para_pessoa_fora_da_lista(): void
    {
        $this->actingAsAdmin();

        $this->post(route('financial.transactions.store.despesa'), [
            'transaction_date' => now()->toDateString(),
            'description' => 'Serviço avulso',
            'amount' => 30,
            'is_paid' => 1,
            'member_id' => 'other',
            'received_from_other' => 'Fornecedor XYZ',
        ])->assertRedirect(route('financial.transactions.index'));

        $tx = FinancialTransaction::first();
        $this->assertNull($tx->member_id);
        $this->assertSame('Fornecedor XYZ', $tx->received_from_other);
        $this->assertSame('Fornecedor XYZ', $tx->source_name);
    }
}
