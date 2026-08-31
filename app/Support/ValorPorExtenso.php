<?php

namespace App\Support;

use NumberFormatter;
use RuntimeException;

class ValorPorExtenso
{
    public static function reais(float $valor): string
    {
        if (! class_exists(NumberFormatter::class)) {
            throw new RuntimeException('A extensão intl do PHP é necessária para o valor por extenso.');
        }

        $valor = round(abs($valor), 2);
        $reais = (int) floor($valor);
        $centavos = (int) round(($valor - $reais) * 100);
        if ($centavos === 100) {
            $reais++;
            $centavos = 0;
        }

        $fmt = new NumberFormatter('pt_BR', NumberFormatter::SPELLOUT);
        $partes = [];

        if ($reais > 0 || $centavos === 0) {
            $partes[] = $fmt->format($reais).($reais === 1 ? ' real' : ' reais');
        }

        if ($centavos > 0) {
            $centavosTxt = $fmt->format($centavos).($centavos === 1 ? ' centavo' : ' centavos');
            $partes[] = $centavosTxt;
        }

        if (count($partes) === 2) {
            return $partes[0].' e '.$partes[1];
        }

        return $partes[0] ?? 'zero reais';
    }
}
