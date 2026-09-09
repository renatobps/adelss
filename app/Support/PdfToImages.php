<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Transforma um PDF em arquivos de imagem para o fechamento de caixa.
 *
 * O FPDI gratuito não importa PDF 1.5+ (object streams) — o formato da
 * maioria dos comprovantes de banco e WhatsApp. Imagick não está no servidor,
 * então a ordem é: Ghostscript (página inteira) e, na falta dele, JPEGs/PNGs
 * já embutidos no arquivo.
 */
class PdfToImages
{
    private const MIN_BYTES = 8_000;

    private const MIN_WIDTH = 80;

    /**
     * @return list<string> Caminhos temporários (jpg/png). O chamador apaga.
     */
    public function convert(string $pdfPath): array
    {
        if (! is_file($pdfPath) || filesize($pdfPath) < 8) {
            return [];
        }

        $fromGs = $this->viaGhostscript($pdfPath);
        if ($fromGs !== []) {
            return $fromGs;
        }

        return $this->embeddedBitmaps($pdfPath);
    }

    /**
     * Regrava o PDF em 1.4 sem object streams, para o FPDI conseguir importar.
     * Devolve null quando o Ghostscript não está disponível ou falha.
     */
    public function flattenToPdf14(string $pdfPath): ?string
    {
        $gs = $this->ghostscriptBinary();
        if ($gs === null) {
            return null;
        }

        $out = tempnam(sys_get_temp_dir(), 'pdf14-');
        if ($out === false) {
            return null;
        }
        $outPdf = $out.'.pdf';
        @unlink($out);

        $process = new Process([
            $gs,
            '-dSAFER', '-dBATCH', '-dNOPAUSE', '-dQUIET',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-sOutputFile='.$outPdf,
            $pdfPath,
        ]);
        $process->setTimeout(60);

        try {
            $process->run();
        } catch (Throwable $e) {
            @unlink($outPdf);
            Log::info('Ghostscript não conseguiu achatar o PDF.', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $process->isSuccessful() || ! is_file($outPdf) || filesize($outPdf) < 8) {
            @unlink($outPdf);

            return null;
        }

        return $outPdf;
    }

    /**
     * @return list<string>
     */
    private function viaGhostscript(string $pdfPath): array
    {
        $gs = $this->ghostscriptBinary();
        if ($gs === null) {
            return [];
        }

        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pdfimg-'.bin2hex(random_bytes(4));
        if (! @mkdir($dir, 0700) && ! is_dir($dir)) {
            return [];
        }

        $pattern = $dir.DIRECTORY_SEPARATOR.'p-%02d.jpg';
        $process = new Process([
            $gs,
            '-dSAFER', '-dBATCH', '-dNOPAUSE', '-dQUIET',
            '-sDEVICE=jpeg',
            '-dJPEGQ=80',
            '-r120',
            '-dTextAlphaBits=4',
            '-dGraphicsAlphaBits=4',
            '-sOutputFile='.$pattern,
            $pdfPath,
        ]);
        $process->setTimeout(90);

        try {
            $process->run();
        } catch (Throwable $e) {
            $this->rrmdir($dir);
            Log::info('Ghostscript não rasterizou o PDF.', ['error' => $e->getMessage()]);

            return [];
        }

        if (! $process->isSuccessful()) {
            $this->rrmdir($dir);

            return [];
        }

        $files = glob($dir.DIRECTORY_SEPARATOR.'p-*.jpg') ?: [];
        natsort($files);

        $moved = [];
        foreach ($files as $file) {
            if (! $this->usableImage($file)) {
                @unlink($file);
                continue;
            }
            $dest = tempnam(sys_get_temp_dir(), 'pdfgs-');
            if ($dest === false) {
                continue;
            }
            $named = $dest.'.jpg';
            @unlink($dest);
            if (@rename($file, $named) || @copy($file, $named)) {
                @unlink($file);
                $moved[] = $named;
            }
        }

        $this->rrmdir($dir);

        return $moved;
    }

    /**
     * @return list<string>
     */
    private function embeddedBitmaps(string $pdfPath): array
    {
        $binary = @file_get_contents($pdfPath);
        if (! is_string($binary) || $binary === '') {
            return [];
        }

        $paths = [];
        foreach ($this->extractByMarkers($binary, "\xFF\xD8\xFF", "\xFF\xD9", 'jpg') as $blob) {
            $path = $this->writeTemp($blob, 'jpg');
            if ($path && $this->usableImage($path)) {
                $paths[] = $path;
            } elseif ($path) {
                @unlink($path);
            }
        }

        foreach ($this->extractByMarkers($binary, "\x89PNG\r\n\x1a\n", "IEND\xaeB`\x82", 'png') as $blob) {
            $path = $this->writeTemp($blob, 'png');
            if ($path && $this->usableImage($path)) {
                $paths[] = $path;
            } elseif ($path) {
                @unlink($path);
            }
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function extractByMarkers(string $binary, string $start, string $end, string $ext): array
    {
        $blobs = [];
        $offset = 0;
        $endLen = strlen($end);

        while (($from = strpos($binary, $start, $offset)) !== false) {
            $to = strpos($binary, $end, $from + strlen($start));
            if ($to === false) {
                break;
            }
            $blob = substr($binary, $from, $to + $endLen - $from);
            $offset = $to + $endLen;
            if (strlen($blob) >= 800) {
                $blobs[] = $blob;
            }
            if (count($blobs) >= 10) {
                break;
            }
        }

        return $blobs;
    }

    private function writeTemp(string $blob, string $ext): ?string
    {
        $path = tempnam(sys_get_temp_dir(), 'pdfbm-');
        if ($path === false) {
            return null;
        }
        $named = $path.'.'.$ext;
        @unlink($path);
        if (file_put_contents($named, $blob) === false) {
            return null;
        }

        return $named;
    }

    private function usableImage(string $path): bool
    {
        if (! is_file($path) || filesize($path) < 100) {
            return false;
        }
        $info = @getimagesize($path);
        if (! is_array($info)) {
            return false;
        }
        $width = (int) ($info[0] ?? 0);

        return $width >= self::MIN_WIDTH || filesize($path) >= self::MIN_BYTES;
    }

    private function ghostscriptBinary(): ?string
    {
        $configured = config('services.ghostscript.binary');
        if (is_string($configured) && $configured !== '' && $this->isRunnable($configured)) {
            return $configured;
        }

        foreach (['gs', 'gswin64c', 'gswin32c'] as $bin) {
            $found = $this->resolveOnPath($bin);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function resolveOnPath(string $bin): ?string
    {
        $command = PHP_OS_FAMILY === 'Windows'
            ? ['where', $bin]
            : ['which', $bin];

        $process = new Process($command);
        $process->setTimeout(5);

        try {
            $process->run();
        } catch (Throwable) {
            return null;
        }

        if (! $process->isSuccessful()) {
            return null;
        }

        $line = trim(strtok($process->getOutput(), "\r\n") ?: '');
        if ($line !== '' && $this->isRunnable($line)) {
            return $line;
        }

        return null;
    }

    private function isRunnable(string $path): bool
    {
        return is_file($path) || (PHP_OS_FAMILY !== 'Windows' && is_executable($path));
    }

    private function rrmdir(string $dir): void
    {
        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
