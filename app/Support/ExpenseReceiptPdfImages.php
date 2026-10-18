<?php

namespace App\Support;

use App\Models\CampaignExpense;
use App\Models\CampaignExpenseAttachment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Prepara os comprovantes de gastos para serem anexados ao PDF da prestação de contas.
 *
 * Imagens são redimensionadas (GD) e convertidas para JPEG em base64, já com as
 * dimensões calculadas para caber na área útil da página A4. Arquivos que não podem
 * ser incorporados (PDF, HEIC sem suporte, etc.) viram um cartão de referência.
 */
class ExpenseReceiptPdfImages
{
    /** Maior lado da imagem incorporada, em pixels (controla o peso do PDF). */
    private const MAX_SIDE_PX = 1400;

    private const JPEG_QUALITY = 72;

    /** Área útil disponível para a imagem na página (mm). */
    private const BOX_WIDTH_MM = 186;
    private const BOX_HEIGHT_MM = 225;

    /**
     * @param  Collection<int, CampaignExpense>  $expenses  Gastos já com `attachments` carregados.
     * @return array<int, array{
     *     expense: CampaignExpense,
     *     attachment: CampaignExpenseAttachment,
     *     index: int,
     *     total: int,
     *     embeddable: bool,
     *     src: ?string,
     *     width_mm: ?float,
     *     height_mm: ?float,
     *     reason: ?string
     * }>
     */
    public static function build(Collection $expenses): array
    {
        $items = [];

        foreach ($expenses as $expense) {
            $attachments = $expense->attachments->values();
            $total = $attachments->count();

            foreach ($attachments as $i => $attachment) {
                $items[] = array_merge(
                    [
                        'expense' => $expense,
                        'attachment' => $attachment,
                        'index' => $i + 1,
                        'total' => $total,
                    ],
                    self::prepare($attachment)
                );
            }
        }

        return $items;
    }

    /**
     * @return array{embeddable: bool, src: ?string, width_mm: ?float, height_mm: ?float, reason: ?string}
     */
    private static function prepare(CampaignExpenseAttachment $attachment): array
    {
        $none = fn (string $reason) => [
            'embeddable' => false,
            'src' => null,
            'width_mm' => null,
            'height_mm' => null,
            'reason' => $reason,
        ];

        if (! $attachment->isImage()) {
            return $none('Arquivo em PDF — disponível para download no sistema.');
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($attachment->file_path)) {
            return $none('Arquivo não encontrado no armazenamento.');
        }

        if (! function_exists('imagecreatefromstring')) {
            return $none('Servidor sem suporte a imagens (GD).');
        }

        $contents = $disk->get($attachment->file_path);
        $image = @imagecreatefromstring($contents);
        unset($contents);

        if ($image === false) {
            return $none('Formato de imagem não suportado para incorporação (ex.: HEIC).');
        }

        // Corrige orientação de fotos tiradas pelo celular (EXIF), quando possível.
        $image = self::fixOrientation($image, $disk->path($attachment->file_path));

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_SIDE_PX / max($width, $height));

        if ($scale < 1) {
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);
            if ($resized !== false) {
                imagedestroy($image);
                $image = $resized;
                $width = imagesx($image);
                $height = imagesy($image);
            }
        }

        // Fundo branco para PNG/WebP com transparência.
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
        imagedestroy($image);

        ob_start();
        imagejpeg($canvas, null, self::JPEG_QUALITY);
        $jpeg = (string) ob_get_clean();
        imagedestroy($canvas);

        // Ajusta para caber na área útil da página mantendo a proporção.
        $ratio = min(self::BOX_WIDTH_MM / $width, self::BOX_HEIGHT_MM / $height);

        return [
            'embeddable' => true,
            'src' => 'data:image/jpeg;base64,' . base64_encode($jpeg),
            'width_mm' => round($width * $ratio, 1),
            'height_mm' => round($height * $ratio, 1),
            'reason' => null,
        ];
    }

    /**
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private static function fixOrientation($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
