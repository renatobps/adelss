<?php

namespace Tests\Feature;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportLegacyFinancialTransactionsTest extends TestCase
{
    private string $csvPath;

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
            $table->timestamps();
        });

        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
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
            $table->boolean('is_paid')->default(false);
            $table->date('due_date')->nullable();
            $table->string('status')->default('pago');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('received_from_other')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('payment_type')->default('unico');
            $table->string('document_number')->nullable();
            $table->string('external_ref', 64)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->date('competence_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->csvPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'financial_import_test.csv';
        file_put_contents($this->csvPath, implode("\n", [
            'mes_aba,data,descricao,valor,tipo,categoria,status_import,member_id,received_from_other',
            'JAN25,2025-01-05,Dízimo,119.0,receita,Dízimo,OK,,true',
            'JAN25,2025-01-05,Oferta,55.3,receita,Oferta,OK,,true',
            'JAN25,2025-01-03,,3400.0,despesa,Material de Construção,OK,,',
            'JAN25,2025-01-12,copo descartáveis,10.0,despesa,,REVISAR,,',
        ])."\n");
    }

    protected function tearDown(): void
    {
        if (is_file($this->csvPath)) {
            unlink($this->csvPath);
        }

        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('financial_categories');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_dry_run_nao_grava_no_banco(): void
    {
        $this->artisan('financial:import-legacy', [
            'caminho' => $this->csvPath,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, FinancialTransaction::count());
        $this->assertSame(0, FinancialCategory::count());
    }

    public function test_importa_linhas_ok_pula_revisar_e_e_idempotente(): void
    {
        $this->artisan('financial:import-legacy', [
            'caminho' => $this->csvPath,
        ])->assertSuccessful();

        $this->assertSame(3, FinancialTransaction::count());
        $this->assertSame(0, FinancialTransaction::whereNotNull('member_id')->count());
        $this->assertSame(2, FinancialTransaction::where('type', 'receita')->count());
        $this->assertTrue(
            FinancialTransaction::where('type', 'receita')->get()->every(
                fn (FinancialTransaction $tx) => $tx->received_from_other === 'Importação 2025'
            )
        );
        $this->assertSame(0, FinancialTransaction::where('description', 'like', '%copo%')->count());
        $this->assertNotNull(FinancialCategory::where('name', 'Dízimo')->where('type', 'receita')->first());
        $this->assertTrue((bool) FinancialCategory::where('name', 'Dízimo')->first()->sends_receipt);

        $this->artisan('financial:import-legacy', [
            'caminho' => $this->csvPath,
        ])->assertSuccessful();

        $this->assertSame(3, FinancialTransaction::count());
    }
}
