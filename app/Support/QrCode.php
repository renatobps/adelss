<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode as ChillerlanQrCode;
use chillerlan\QRCode\QROptions;

/**
 * Geração de QR Codes (PNG via GD e SVG) sem depender da extensão imagick.
 */
class QrCode
{
    /**
     * PNG binário.
     */
    public static function png(string $data, int $scale = 10): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => false,
            'scale' => $scale,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
        ]);

        return (new ChillerlanQrCode($options))->render($data);
    }

    /**
     * Data URI PNG (para embutir em PDFs/HTML).
     */
    public static function pngDataUri(string $data, int $scale = 10): string
    {
        return 'data:image/png;base64,' . base64_encode(self::png($data, $scale));
    }

    /**
     * Markup SVG.
     */
    public static function svg(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'svgAddXmlHeader' => true,
        ]);

        return (new ChillerlanQrCode($options))->render($data);
    }
}
