<?php

namespace App\Support;

class Cp437Utf8MojibakeFixer
{
    /**
     * Caracteres típicos de UTF-8 lido como CP437 (í → ├¡, á → ├í, ã → ├ú).
     */
    public function looksBroken(string $text): bool
    {
        return (bool) preg_match('/[\x{2500}-\x{257F}\x{00A1}\x{00AA}\x{00BA}\x{2591}-\x{2593}]/u', $text);
    }

    public function repair(string $text): string
    {
        if ($text === '' || ! $this->looksBroken($text)) {
            return $text;
        }

        $map = $this->cp437ReverseMap();
        $bytes = '';
        $length = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            $code = mb_ord($char, 'UTF-8');
            if ($code < 128) {
                $bytes .= chr($code);
                continue;
            }
            if (! isset($map[$code])) {
                return $text;
            }
            $bytes .= chr($map[$code]);
        }

        if ($bytes === $text || ! mb_check_encoding($bytes, 'UTF-8')) {
            return $text;
        }

        if ($this->looksBroken($bytes)) {
            return $text;
        }

        return $bytes;
    }

    /**
     * @return array<int, int> Unicode code point => byte CP437
     */
    private function cp437ReverseMap(): array
    {
        static $map = null;
        if (is_array($map)) {
            return $map;
        }

        $chars = [
            0x80 => 'Ç', 0x81 => 'ü', 0x82 => 'é', 0x83 => 'â', 0x84 => 'ä', 0x85 => 'à', 0x86 => 'å', 0x87 => 'ç',
            0x88 => 'ê', 0x89 => 'ë', 0x8A => 'è', 0x8B => 'ï', 0x8C => 'î', 0x8D => 'ì', 0x8E => 'Ä', 0x8F => 'Å',
            0x90 => 'É', 0x91 => 'æ', 0x92 => 'Æ', 0x93 => 'ô', 0x94 => 'ö', 0x95 => 'ò', 0x96 => 'û', 0x97 => 'ù',
            0x98 => 'ÿ', 0x99 => 'Ö', 0x9A => 'Ü', 0x9B => '¢', 0x9C => '£', 0x9D => '¥', 0x9E => '₧', 0x9F => 'ƒ',
            0xA0 => 'á', 0xA1 => 'í', 0xA2 => 'ó', 0xA3 => 'ú', 0xA4 => 'ñ', 0xA5 => 'Ñ', 0xA6 => 'ª', 0xA7 => 'º',
            0xA8 => '¿', 0xA9 => '⌐', 0xAA => '¬', 0xAB => '½', 0xAC => '¼', 0xAD => '¡', 0xAE => '«', 0xAF => '»',
            0xB0 => '░', 0xB1 => '▒', 0xB2 => '▓', 0xB3 => '│', 0xB4 => '┤', 0xB5 => '╡', 0xB6 => '╢', 0xB7 => '╖',
            0xB8 => '╕', 0xB9 => '╣', 0xBA => '║', 0xBB => '╗', 0xBC => '╝', 0xBD => '╜', 0xBE => '╛', 0xBF => '┐',
            0xC0 => '└', 0xC1 => '┴', 0xC2 => '┬', 0xC3 => '├', 0xC4 => '─', 0xC5 => '┼', 0xC6 => '╞', 0xC7 => '╟',
            0xC8 => '╚', 0xC9 => '╔', 0xCA => '╩', 0xCB => '╦', 0xCC => '╠', 0xCD => '═', 0xCE => '╬', 0xCF => '╧',
            0xD0 => '╨', 0xD1 => '╤', 0xD2 => '╥', 0xD3 => '╙', 0xD4 => '╘', 0xD5 => '╒', 0xD6 => '╓', 0xD7 => '╫',
            0xD8 => '╪', 0xD9 => '┘', 0xDA => '┌', 0xDB => '█', 0xDC => '▄', 0xDD => '▌', 0xDE => '▐', 0xDF => '▀',
            0xE0 => 'α', 0xE1 => 'ß', 0xE2 => 'Γ', 0xE3 => 'π', 0xE4 => 'Σ', 0xE5 => 'σ', 0xE6 => 'µ', 0xE7 => 'τ',
            0xE8 => 'Φ', 0xE9 => 'Θ', 0xEA => 'Ω', 0xEB => 'δ', 0xEC => '∞', 0xED => 'φ', 0xEE => 'ε', 0xEF => '∩',
            0xF0 => '≡', 0xF1 => '±', 0xF2 => '≥', 0xF3 => '≤', 0xF4 => '⌠', 0xF5 => '⌡', 0xF6 => '÷', 0xF7 => '≈',
            0xF8 => '°', 0xF9 => '∙', 0xFA => '·', 0xFB => '√', 0xFC => 'ⁿ', 0xFD => '²', 0xFE => '■', 0xFF => ' ',
        ];

        $map = [];
        foreach ($chars as $byte => $char) {
            $map[mb_ord($char, 'UTF-8')] = $byte;
        }

        return $map;
    }
}
