<?php

namespace Tests\Unit\Support;

use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Support\FinancialReceiptLogo;
use App\Support\FinancialReceiptPresenter;
use Carbon\Carbon;
use Tests\TestCase;

class FinancialReceiptPresenterTest extends TestCase
{
    public function test_outros_deixa_cpf_e_endereco_em_branco(): void
    {
        $tx = new FinancialTransaction([
            'type' => 'receita',
            'amount' => 1.00,
            'received_from_other' => 'Visitante',
            'transaction_date' => Carbon::parse('2026-08-30'),
        ]);
        $tx->id = 8;
        $tx->setRelation('member', null);
        $tx->setRelation('category', null);

        $data = FinancialReceiptPresenter::from($tx);

        $this->assertSame('VISITANTE', $data['fromName']);
        $this->assertSame('um real', $data['amountExtenso']);
        $this->assertSame('', $data['memberCpfRg']);
        $this->assertSame('', $data['memberAddress']);
        $this->assertSame('000008', $data['reciboNumero']);
        $this->assertSame('Brasília - DF', $data['city']);
        $this->assertSame('30', $data['day']);
        $this->assertSame('2026', $data['year']);
    }

    public function test_membro_preenche_cpf_e_endereco(): void
    {
        $member = new Member([
            'name' => 'Paula Cristina da Conceição Silva',
            'cpf' => '123.456.789-00',
            'address' => 'SQS 101',
            'city' => 'Brasília',
            'state' => 'DF',
        ]);

        $tx = new FinancialTransaction([
            'type' => 'receita',
            'amount' => 123.45,
            'transaction_date' => Carbon::parse('2026-08-30'),
        ]);
        $tx->id = 12;
        $tx->setRelation('member', $member);
        $tx->setRelation('category', null);

        $data = FinancialReceiptPresenter::from($tx);

        $this->assertStringContainsString('CONCEIÇÃO', $data['fromName']);
        $this->assertSame('123.456.789-00', $data['memberCpfRg']);
        $this->assertStringContainsString('SQS 101', $data['memberAddress']);
        $this->assertSame(
            'cento e vinte e três reais e quarenta e cinco centavos',
            $data['amountExtenso']
        );
    }

    public function test_html_do_pdf_contem_campos_do_talao(): void
    {
        $tx = new FinancialTransaction([
            'type' => 'receita',
            'amount' => 1.00,
            'received_from_other' => 'Visitante',
            'transaction_date' => Carbon::parse('2026-08-30'),
        ]);
        $tx->id = 1;
        $tx->setRelation('member', null);
        $tx->setRelation('category', null);

        $html = view('financial.transactions.receipt-pdf', [
            'transaction' => $tx,
            'tesoureiroNome' => 'Tesoureiro',
            'tesoureiroAssinaturaSrc' => null,
            'fundoPath' => FinancialReceiptLogo::backgroundAbsolutePath(),
            'fundoSrc' => null,
        ])->render();

        $this->assertStringContainsString('VISITANTE', $html);
        $this->assertStringContainsString('um real', $html);
        $this->assertStringContainsString('Brasília - DF', $html);
        $this->assertStringContainsString('recibo-fundo', $html);
        $this->assertStringContainsString('agosto', $html);
    }
}
