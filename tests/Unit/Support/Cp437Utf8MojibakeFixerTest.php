<?php

namespace Tests\Unit\Support;

use App\Support\Cp437Utf8MojibakeFixer;
use Tests\TestCase;

class Cp437Utf8MojibakeFixerTest extends TestCase
{
    public function test_reverte_acentos_lidos_como_cp437(): void
    {
        $fixer = new Cp437Utf8MojibakeFixer;

        $this->assertSame('Dízimo', $fixer->repair('D├¡zimo'));
        $this->assertSame('dízimo', $fixer->repair('d├¡zimo'));
        $this->assertSame('Gás + Água', $fixer->repair('G├ís + ├ügua'));
        $this->assertSame('Cesta básica', $fixer->repair('Cesta b├ísica'));
        $this->assertSame('Mão de obra', $fixer->repair('M├úo de obra'));
        $this->assertSame('Água', $fixer->repair('├ügua'));
        $this->assertSame('Material de Construção', $fixer->repair('Material de Constru├º├úo'));
        $this->assertSame('Materiais Escritório', $fixer->repair('Materiais Escrit├│rio'));
        $this->assertSame('Instrumentos & Acessórios', $fixer->repair('Instrumentos & Acess├│rios'));
        $this->assertSame('1º Tesoureiro(a)', $fixer->repair('1┬║ Tesoureiro(a)'));
        $this->assertSame('2º Tesoureiro(a)', $fixer->repair('2┬║ Tesoureiro(a)'));
        $this->assertSame('Apóstolo', $fixer->repair('Ap├│stolo'));
        $this->assertSame('Diácono', $fixer->repair('Di├ícono'));
        $this->assertSame('Líder de Crianças', $fixer->repair('L├¡der de Crian├ºas'));
        $this->assertSame('Líder de Intercessão', $fixer->repair('L├¡der de Intercess├úo'));
        $this->assertSame('Líder do Ministério de Louvor', $fixer->repair('L├¡der do Minist├⌐rio de Louvor'));
        $this->assertSame('Operador de Transmissão', $fixer->repair('Operador de Transmiss├úo'));
    }

    public function test_repara_trechos_mistos_sem_abortar_no_resto_do_texto(): void
    {
        $fixer = new Cp437Utf8MojibakeFixer;
        $suffix = "\u{00AD}\u{0192}\u{00F6}\u{00D1}";

        $this->assertSame(
            'Formando uma geração comprometida com Cristo, Seu Reino e Sua missão.'.$suffix,
            $fixer->repair('Formando uma gera├º├úo comprometida com Cristo, Seu Reino e Sua miss├úo.'.$suffix)
        );
        $this->assertSame(
            'Conduzindo a igreja à adoração e servindo com excelência para a glória de Deus.',
            $fixer->repair('Conduzindo a igreja ├á adora├º├úo e servindo com excel├¬ncia para a glória de Deus.')
        );
        $this->assertSame(
            'Edificando mulheres para viverem seu chamado com sabedoria e fé',
            $fixer->repair("Edificando mulheres para viverem seu chamado com sabedoria e f├\u{00AE}")
        );
    }

    public function test_nao_altera_texto_ja_correto(): void
    {
        $fixer = new Cp437Utf8MojibakeFixer;

        $this->assertSame('Dízimo', $fixer->repair('Dízimo'));
        $this->assertSame('Oferta', $fixer->repair('Oferta'));
        $this->assertSame('Zeladoria', $fixer->repair('Zeladoria'));
        $this->assertSame('1º Tesoureiro(a)', $fixer->repair('1º Tesoureiro(a)'));
        $this->assertSame('Diácono', $fixer->repair('Diácono'));
        $this->assertSame('Líder', $fixer->repair('Líder'));
    }
}
