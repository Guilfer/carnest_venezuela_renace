<?php

namespace App\Services;

use App\Kernel;
use App\Support\Layout;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use RuntimeException;

final class CarnetRenderer
{
    private const BRAND = '#273376';
    private const ACCENT = '#A70336';
    private const GOLD = '#FFC907';

    private QrService $qr;

    public function __construct()
    {
        $this->qr = new QrService();
    }

    public function renderFace(string $face, array $carnet, ?array $layout = null): Imagick
    {
        $layout ??= Layout::all();
        $config = $layout['faces'][$face] ?? null;
        if ($config === null) {
            throw new RuntimeException("Cara no configurada: {$face}");
        }

        $background = Kernel::templates($config['background']);
        if (!is_file($background)) {
            throw new RuntimeException("Plantilla no encontrada: {$config['background']}");
        }

        $im = new Imagick($background);
        $width = $im->getImageWidth();
        $height = $im->getImageHeight();

        foreach ($config['elements'] as $element) {
            if (($element['enabled'] ?? true) === false) {
                continue;
            }

            match ($element['kind']) {
                'image' => $this->drawImage($im, $element, $carnet, $width, $height),
                'text' => $this->drawText($im, $element, $carnet, $layout, $width, $height),
                'qrcode' => $this->drawQr($im, $element, $carnet, $width, $height),
                default => null,
            };
        }

        $im->setImageBackgroundColor(new ImagickPixel('white'));
        $flat = $im->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $im->clear();
        return $flat;
    }

    public function renderAll(array $carnet, ?array $layout = null): array
    {
        return [
            'frontal' => $this->renderFace('frontal', $carnet, $layout),
            'reverso' => $this->renderFace('reverso', $carnet, $layout),
        ];
    }

    public function savePng(Imagick $image, string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $image->setImageFormat('png');
        $image->setImageCompressionQuality(95);
        $image->writeImage($path);
    }

    private function drawImage(Imagick $im, array $el, array $carnet, int $width, int $height): void
    {
        $boxW = (int) round($el['w'] * $width);
        $boxH = (int) round($el['h'] * $height);
        $boxW = max(1, $boxW);
        $boxH = max(1, $boxH);

        $relative = $carnet['foto'] ?? null;
        $photoPath = $relative ? Kernel::storage('uploads/' . $relative) : null;

        if ($photoPath && is_file($photoPath)) {
            $photo = new Imagick($photoPath);
            $this->fitCover($photo, $boxW, $boxH);
        } else {
            $photo = $this->placeholderImage($carnet, $boxW, $boxH);
        }

        $radius = (int) round(($el['radius'] ?? 0) * $width);
        if ($radius > 0) {
            $photo->roundCorners($radius, $radius);
        }

        $borderWidth = (int) ($el['border_width'] ?? 0);
        if ($borderWidth > 0) {
            $bordered = new Imagick();
            $bordered->newImage($boxW, $boxH, new ImagickPixel('transparent'), 'png');
            $bordered->compositeImage($photo, Imagick::COMPOSITE_OVER, 0, 0);
            $draw = new ImagickDraw();
            $draw->setFillColor(new ImagickPixel('none'));
            $draw->setStrokeColor(new ImagickPixel($el['border_color'] ?? '#FFFFFF'));
            $draw->setStrokeWidth($borderWidth);
            $half = (int) ceil($borderWidth / 2);
            $draw->rectangle($half, $half, $boxW - $half, $boxH - $half);
            $bordered->drawImage($draw);
            $photo->clear();
            $photo = $bordered;
        }

        $x = (int) round($el['x'] * $width - $boxW / 2);
        $y = (int) round($el['y'] * $height - $boxH / 2);
        $im->compositeImage($photo, Imagick::COMPOSITE_OVER, $x, $y);
        $photo->clear();
    }

    private function drawText(Imagick $im, array $el, array $carnet, array $layout, int $width, int $height): void
    {
        $text = $this->resolveValue($el, $carnet);
        if ($text === '') {
            return;
        }

        $bold = (bool) ($el['bold'] ?? false);
        $draw = new ImagickDraw();
        $draw->setFont($this->fontPath($layout, ($el['font'] ?? 'karvest') . ($bold ? '_bold' : '')));
        $draw->setFillColor(new ImagickPixel($el['color'] ?? self::BRAND));
        $draw->setTextAlignment(Imagick::ALIGN_LEFT);

        [$lines, $size] = $this->fitText($im, $draw, $el, $text, $width, $height);
        $draw->setFontSize($size);

        $metrics = $im->queryFontMetrics($draw, $lines[0]);
        $textHeight = (float) ($metrics['textHeight'] ?? $size);
        $ascender = (float) ($metrics['ascender'] ?? $size * 0.8);
        $lineHeight = $textHeight * 1.18;

        $x = (float) $el['x'] * $width;
        $yCenter = (float) $el['y'] * $height;
        $totalHeight = count($lines) * $lineHeight;

        $alignment = match ($el['align'] ?? 'center') {
            'left' => Imagick::ALIGN_LEFT,
            'right' => Imagick::ALIGN_RIGHT,
            default => Imagick::ALIGN_CENTER,
        };

        foreach ($lines as $i => $line) {
            $draw->setTextAlignment($alignment);
            $lineCenter = $yCenter - $totalHeight / 2 + $lineHeight * ($i + 0.5);
            $baseline = $lineCenter - $textHeight / 2 + $ascender;
            $im->annotateImage($draw, $x, $baseline, 0, $line);
        }
    }

    private function drawQr(Imagick $im, array $el, array $carnet, int $width, int $height): void
    {
        $boxW = (int) round($el['w'] * $width);
        $boxH = (int) round($el['h'] * $height);
        $size = min($boxW, $boxH);

        $token = $carnet['qr_token'] ?? 'DEMO';
        $url = $this->qr->verifyUrl($token);
        $sizeOpt = (int) config('carnet.qr_size', 700);
        $margin = (int) config('carnet.qr_margin', 4);
        $png = $this->qr->png($url, max($size, $sizeOpt), $margin, self::BRAND);

        $qr = new Imagick();
        $qr->readImageBlob($png);
        $qr->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);

        $x = (int) round($el['x'] * $width - $size / 2);
        $y = (int) round($el['y'] * $height - $size / 2);

        $canvas = new Imagick();
        $canvas->newImage($boxW, $boxH, new ImagickPixel('white'), 'png');
        $canvas->compositeImage($qr, Imagick::COMPOSITE_OVER, (int) (($boxW - $size) / 2), (int) (($boxH - $size) / 2));
        $qr->clear();

        $im->compositeImage($canvas, Imagick::COMPOSITE_OVER, (int) round($el['x'] * $width - $boxW / 2), (int) round($el['y'] * $height - $boxH / 2));
        $canvas->clear();
    }

    private function resolveValue(array $el, array $carnet): string
    {
        if (isset($el['text'])) {
            return (string) $el['text'];
        }

        return match ($el['bind'] ?? '') {
            'nombres' => trim(($carnet['nombres'] ?? '') . ' ' . ($carnet['apellidos'] ?? '')),
            'cedula' => $this->formatCedula($carnet['cedula'] ?? ''),
            'cargo' => (string) ($carnet['cargo'] ?? ''),
            'dependencia' => (string) ($carnet['dependencia'] ?? ''),
            'codigo' => (string) ($carnet['codigo'] ?? ''),
            default => '',
        };
    }

    private function formatCedula(string $cedula): string
    {
        $raw = trim($cedula ?? '');
        $letter = 'V';
        if (preg_match('/^([VEJG])/i', $raw, $m) === 1) {
            $letter = strtoupper($m[1]);
        }
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return '';
        }
        return $letter . '- ' . number_format((float) $digits, 0, ',', '.');
    }

    private function fontPath(array $layout, string $font): string
    {
        $file = $layout['fonts'][$font] ?? 'Georama.ttf';
        $path = Kernel::templates('fonts/' . $file);
        return is_file($path) ? $path : Kernel::templates('fonts/Georama.ttf');
    }

    private function wrap(Imagick $im, ImagickDraw $draw, string $text, ?float $maxWidth): array
    {
        if ($maxWidth === null || $maxWidth <= 0) {
            return [$text];
        }

        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            $metrics = $im->queryFontMetrics($draw, $candidate);
            if ((float) $metrics['textWidth'] <= $maxWidth || $current === '') {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [$text];
    }

    private function fitText(Imagick $im, ImagickDraw $draw, array $el, string $text, int $width, int $height): array
    {
        $baseSize = (float) $el['size'];
        $maxWidth = isset($el['max_width']) ? (float) $el['max_width'] * $width : null;
        $maxLines = isset($el['max_lines']) ? (int) $el['max_lines'] : null;
        $maxHeight = isset($el['max_height']) ? (float) $el['max_height'] * $height : null;
        $minSize = (float) ($el['min_size'] ?? $baseSize * 0.4);

        $draw->setFontSize($baseSize);
        if ($maxLines === null && $maxHeight === null) {
            return [$this->wrap($im, $draw, $text, $maxWidth), $baseSize];
        }

        $size = $baseSize;
        while ($size >= $minSize) {
            $draw->setFontSize($size);
            $lines = $this->wrap($im, $draw, $text, $maxWidth);
            if ($this->fits($im, $draw, $lines, $maxLines, $maxHeight)) {
                return [$lines, $size];
            }
            $next = round($size * 0.92, 1);
            if ($next < $minSize) {
                break;
            }
            $size = $next;
        }

        $draw->setFontSize($minSize);
        return [$this->wrap($im, $draw, $text, $maxWidth), $minSize];
    }

    private function fits(Imagick $im, ImagickDraw $draw, array $lines, ?int $maxLines, ?float $maxHeight): bool
    {
        if ($maxLines !== null && count($lines) > $maxLines) {
            return false;
        }
        if ($maxHeight === null) {
            return true;
        }
        $metrics = $im->queryFontMetrics($draw, $lines[0]);
        $lineHeight = (float) ($metrics['textHeight'] ?? 0) * 1.18;
        return count($lines) * $lineHeight <= $maxHeight;
    }

    private function fitCover(Imagick $img, int $boxW, int $boxH): void
    {
        $srcW = max(1, $img->getImageWidth());
        $srcH = max(1, $img->getImageHeight());
        $scale = max($boxW / $srcW, $boxH / $srcH);

        $img->resizeImage((int) ceil($srcW * $scale), (int) ceil($srcH * $scale), Imagick::FILTER_LANCZOS, 1);
        $img->cropImage($boxW, $boxH, (int) (($img->getImageWidth() - $boxW) / 2), (int) (($img->getImageHeight() - $boxH) / 2));
        $img->setImagePage(0, 0, 0, 0);
    }

    private function placeholderImage(array $carnet, int $boxW, int $boxH): Imagick
    {
        $im = new Imagick();
        $im->newImage($boxW, $boxH, new ImagickPixel('#E9ECF5'), 'png');

        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel(self::BRAND));
        $initial = mb_substr(trim($carnet['nombres'] ?? ''), 0, 1);
        $draw->setFontSize($boxH * 0.4);
        $font = Kernel::templates('fonts/Georama.ttf');
        if (is_file($font)) {
            $draw->setFont($font);
        }
        $metrics = $im->queryFontMetrics($draw, $initial);
        $im->annotateImage(
            $draw,
            $boxW / 2 - (float) $metrics['textWidth'] / 2,
            $boxH / 2 + (float) $metrics['textHeight'] / 2 - (float) $metrics['descender'],
            0,
            $initial
        );

        return $im;
    }
}
