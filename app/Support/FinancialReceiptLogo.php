<?php

namespace App\Support;

class FinancialReceiptLogo
{
    public static function relativePath(): string
    {
        return (string) config('financial.receipt.logo', 'images/logo-cdel.png');
    }

    public static function backgroundRelativePath(): string
    {
        return (string) config('financial.receipt.background', 'images/recibo-fundo.png');
    }

    public static function absolutePath(): ?string
    {
        return self::absoluteFromRelative(self::relativePath());
    }

    public static function backgroundAbsolutePath(): ?string
    {
        return self::absoluteFromRelative(self::backgroundRelativePath());
    }

    public static function htmlSrc(): ?string
    {
        return self::dataUri(self::absolutePath());
    }

    public static function backgroundHtmlSrc(): ?string
    {
        return self::dataUri(self::backgroundAbsolutePath());
    }

    /**
     * O DomPDF lê a arte do talão do disco; a cópia em storage evita depender
     * do caminho público durante a renderização.
     */
    public static function backgroundPathForPdf(): ?string
    {
        $source = self::backgroundAbsolutePath();
        if (! $source) {
            return null;
        }

        $dir = storage_path('app/temp/receipts');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $copy = $dir.DIRECTORY_SEPARATOR.'recibo-fundo.png';
        if (! is_file($copy) || filemtime($copy) < filemtime($source)) {
            @copy($source, $copy);
        }

        return str_replace('\\', '/', $copy);
    }

    /**
     * Papel do PDF na mesma proporção do talão (1447×1087), em pontos.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public static function pdfPaper(): array
    {
        $widthPx = (int) config('financial.receipt.width_px', 1447);
        $heightPx = (int) config('financial.receipt.height_px', 1087);
        $widthPt = 595.28;
        $heightPt = $widthPx > 0 ? $widthPt * ($heightPx / $widthPx) : 447.2;

        return [0, 0, $widthPt, $heightPt];
    }

    private static function absoluteFromRelative(string $relative): ?string
    {
        $path = public_path($relative);

        return is_file($path) ? str_replace('\\', '/', $path) : null;
    }

    private static function dataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $mime = @mime_content_type($path) ?: 'image/png';
        $bin = @file_get_contents($path);
        if ($bin === false || $bin === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($bin);
    }
}
