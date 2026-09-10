<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberRole;
use App\Services\Financial\PdfSignatureService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PdfSignatureServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('member_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('ativo');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('members');
        Schema::dropIfExists('member_roles');
        parent::tearDown();
    }

    public function test_pastor_dirigente_usa_nome_do_membro_com_cargo_pastor(): void
    {
        $pastor = MemberRole::create(['name' => 'Pastor', 'is_active' => true]);
        $auxiliar = MemberRole::create(['name' => 'Pastor Auxiliar', 'is_active' => true]);
        Member::create(['name' => 'RODRIGO BARBOSA DA SILVA', 'status' => 'ativo', 'role_id' => $pastor->id]);
        Member::create(['name' => 'Pastor Auxiliar Teste', 'status' => 'ativo', 'role_id' => $auxiliar->id]);

        $this->assertSame(
            'Rodrigo Barbosa da Silva',
            app(PdfSignatureService::class)->pastorNome()
        );
    }

    public function test_aceita_cargo_pastor_entre_parenteses(): void
    {
        $role = MemberRole::create(['name' => 'Pastor(a)', 'is_active' => true]);
        Member::create(['name' => 'Maria Pastora', 'status' => 'ativo', 'role_id' => $role->id]);

        $this->assertSame('Maria Pastora', app(PdfSignatureService::class)->pastorNome());
    }

    public function test_nao_usa_pastor_presidente_no_lugar_do_dirigente(): void
    {
        $presidente = MemberRole::create(['name' => 'Pastor Presidente', 'is_active' => true]);
        $pastor = MemberRole::create(['name' => 'Pastor', 'is_active' => true]);
        Member::create(['name' => 'Sebastião Presidente', 'status' => 'ativo', 'role_id' => $presidente->id]);
        Member::create(['name' => 'João Dirigente', 'status' => 'ativo', 'role_id' => $pastor->id]);

        $this->assertSame('João Dirigente', app(PdfSignatureService::class)->pastorNome());
    }

    public function test_html_do_demonstrativo_exibe_nome_do_pastor(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('transaction_date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_paid')->default(false);
            $table->string('status')->default('recebido');
            $table->timestamps();
            $table->softDeletes();
        });

        $pastor = MemberRole::create(['name' => 'Pastor', 'is_active' => true]);
        Member::create(['name' => 'RODRIGO BARBOSA DA SILVA', 'status' => 'ativo', 'role_id' => $pastor->id]);

        $html = view('financial.reports.pdf.matrix-demonstrativo', app(\App\Services\Financial\MatrixFinancialReportService::class)->buildYear(2026))->render();

        $this->assertStringContainsString('Rodrigo Barbosa da Silva', $html);
        $this->assertStringContainsString('Pastor Dirigente', $html);

        Schema::dropIfExists('financial_transactions');
    }
}
