<?php

namespace App\Services;

use App\Kernel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

final class QrService
{
    public function png(string $data, int $size = 700, int $margin = 4, string $foreground = '#273376'): string
    {
        [$r, $g, $b] = $this->hexToRgb($foreground);

        $qr = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: $margin,
            foregroundColor: new Color($r, $g, $b),
            backgroundColor: new Color(255, 255, 255),
        );

        return (new PngWriter())->write($qr)->getString();
    }

    public function verifyUrl(string $token): string
    {
        $base = rtrim((string) config('carnet.verify_base', ''), '/');
        return $base . '/' . $token;
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
