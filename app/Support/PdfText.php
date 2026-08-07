<?php

namespace App\Support;

/**
 * Utilidades de texto para PDFs gerados com DomPDF.
 */
class PdfText
{
    /**
     * Remove blocos Unicode de emoji, preservando acentos e pontuação normal.
     * O DomPDF não possui fonte com suporte a emoji e renderiza quadrados vazios;
     * o texto original (com emoji) continua sendo usado nas mensagens de WhatsApp.
     */
    public static function stripEmoji(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $clean = preg_replace(
            '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{2190}-\x{21FF}\x{2B00}-\x{2BFF}\x{200D}]/u',
            '',
            $text
        );

        // Colapsa espaços duplicados deixados pela remoção
        return trim(preg_replace('/[ \t]{2,}/', ' ', $clean ?? ''));
    }
}
