<?php

namespace Tests\Unit\Support;

use App\Support\ValorPorExtenso;
use PHPUnit\Framework\TestCase;

class ValorPorExtensoTest extends TestCase
{
    public function test_um_real(): void
    {
        $this->assertSame('um real', ValorPorExtenso::reais(1.00));
    }

    public function test_cinquenta_reais(): void
    {
        $this->assertSame('cinquenta reais', ValorPorExtenso::reais(50.00));
    }

    public function test_reais_e_centavos(): void
    {
        $this->assertSame(
            'cento e vinte e três reais e quarenta e cinco centavos',
            ValorPorExtenso::reais(123.45)
        );
    }

    public function test_um_centavo(): void
    {
        $this->assertSame('um centavo', ValorPorExtenso::reais(0.01));
    }

    public function test_um_real_e_um_centavo(): void
    {
        $this->assertSame('um real e um centavo', ValorPorExtenso::reais(1.01));
    }
}
