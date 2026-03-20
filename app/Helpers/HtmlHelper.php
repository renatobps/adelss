<?php

namespace App\Helpers;

/**
 * Helper para sanitização segura de HTML em conteúdos ricos.
 * Usa HTMLPurifier quando disponível; fallback para sanitização básica.
 */
class HtmlHelper
{
    /** Tags permitidas para conteúdo devocional/observações */
    protected static array $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike',
        'h1', 'h2', 'h3', 'h4',
        'ul', 'ol', 'li',
        'a', 'span', 'div',
        'blockquote', 'hr',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
    ];

    /**
     * Sanitiza HTML para evitar XSS, mantendo formatação permitida.
     */
    public static function sanitize(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Usar HTMLPurifier se disponível (composer require mews/purifier)
        if (class_exists(\Mews\Purifier\Facades\Purifier::class)) {
            return \Mews\Purifier\Facades\Purifier::clean($html, self::purifierConfig());
        }

        return self::sanitizeFallback($html);
    }

    /**
     * Configuração para HTMLPurifier (conteúdo devocional).
     */
    protected static function purifierConfig(): array
    {
        return [
            'HTML.Allowed' => 'p,br,strong,b,em,i,u,s,strike,h1,h2,h3,h4,ul,ol,li,a[href|title],span[style],div[style],blockquote,hr,table[border|width|style],thead,tbody,tfoot,tr,th[colspan|rowspan|style],td[colspan|rowspan|style],caption',
            'HTML.Nofollow' => true,
            'HTML.TargetBlank' => false,
            'AutoFormat.RemoveEmpty' => true,
            'CSS.AllowedProperties' => 'text-align,color,background-color',
        ];
    }

    /**
     * Fallback quando HTMLPurifier não está instalado.
     */
    protected static function sanitizeFallback(string $html): string
    {
        // Remove scripts, iframes, objects
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $html);
        $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
        $html = preg_replace('/<embed\b[^>]*>/i', '', $html);
        $html = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/on\w+\s*=\s*[^\s>]*/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);

        $tags = implode('><', self::$allowedTags);
        $html = strip_tags($html, '<' . $tags . '>');

        // Limpar href perigosos em links
        $html = preg_replace_callback('/<a\s+([^>]+)>/i', function ($m) {
            if (preg_match('/href\s*=\s*["\']([^"\']+)["\']/', $m[1], $href)) {
                $url = trim($href[1]);
                if (preg_match('/^(https?:\/\/|mailto:|#)/i', $url)) {
                    return '<a href="' . htmlspecialchars($url) . '">';
                }
            }
            return '<span>'; // link inválido vira span
        }, $html);

        return trim($html);
    }

    /**
     * Prepara HTML para renderização em PDF.
     * DomPDF não suporta emojis nativamente (aparecem como quadrados).
     *
     * @param string|null $html Conteúdo HTML
     * @param bool $keepEmojis true = manter emojis (podem aparecer como quadrados),
     *                         false = remover emojis para PDF limpo
     */
    public static function prepareForPdf(?string $html, bool $keepEmojis = false): string
    {
        if (empty($html)) {
            return '';
        }

        if (!$keepEmojis) {
            return self::stripEmojis($html);
        }

        return $html;
    }

    /**
     * Remove caracteres emoji do texto (DomPDF não os exibe corretamente).
     * Baseado em ranges Unicode de emojis e símbolos pictóricos.
     */
    public static function stripEmojis(string $text): string
    {
        $emojiRanges = [
            '\x{1F300}-\x{1F9FF}',   // Misc Symbols, Emoticons, etc.
            '\x{1F600}-\x{1F64F}',   // Emoticons
            '\x{1F680}-\x{1F6FF}',   // Transport, Map
            '\x{1F1E0}-\x{1F1FF}',   // Flags
            '\x{2600}-\x{26FF}',     // Misc symbols
            '\x{2700}-\x{27BF}',     // Dingbats
            '\x{FE00}-\x{FE0F}',     // Variation selectors
            '\x{1F900}-\x{1F9FF}',   // Supplemental Symbols
        ];

        $pattern = '/[' . implode('', $emojiRanges) . ']/u';

        return preg_replace($pattern, '', $text);
    }
}
