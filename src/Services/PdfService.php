<?php

namespace App\Services;

use FPDF;

final class PdfService
{
    public function build(string $frontalPath, string $reversoPath, string $outputPath, float $widthMm = 70.0): void
    {
        $size = getimagesize($frontalPath);
        if ($size === false) {
            throw new \RuntimeException('No se pudo leer la imagen frontal para el PDF.');
        }

        $widthPx = max(1, $size[0]);
        $heightPx = max(1, $size[1]);
        $heightMm = $widthMm * ($heightPx / $widthPx);

        $pdf = new FPDF('P', 'mm', [$widthMm, $heightMm]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pdf->AddPage();
        $pdf->Image($frontalPath, 0, 0, $widthMm, $heightMm);

        $pdf->AddPage();
        $pdf->Image($reversoPath, 0, 0, $widthMm, $heightMm);

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $pdf->Output('F', $outputPath);
    }
}
