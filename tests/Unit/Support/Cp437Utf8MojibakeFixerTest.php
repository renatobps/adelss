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
        $this->assertSame('Conta de água', $fixer->repair('Conta de ├ígua'));
    }

    public function test_nao_altera_texto_ja_correto(): void
    {
        $fixer = new Cp437Utf8MojibakeFixer;

        $this->assertSame('Dízimo', $fixer->repair('Dízimo'));
        $this->assertSame('Oferta', $fixer->repair('Oferta'));
        $this->assertSame('Zeladoria', $fixer->repair('Zeladoria'));
        $this->assertSame('mercado (pago a cleia)', $fixer->repair('mercado (pago a cleia)'));
    }
}
