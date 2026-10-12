<?php

namespace App\Controllers;

use App\Kernel;
use App\Services\CarnetRenderer;
use App\Support\Auth;
use App\Support\Layout;
use App\Support\Request;
use App\Support\View;

final class DesignController
{
    public function edit(): void
    {
        Auth::requireAdmin();
        echo View::render('design.edit', [
            'title' => 'Diseño del carnet',
            'layout' => Layout::all(),
            'sample' => $this->sampleCarnet(),
        ]);
    }

    public function background(string $face): void
    {
        Auth::requireAdmin();
        $config = Layout::face($face);
        $path = Kernel::templates((string) ($config['background'] ?? ''));

        if (!is_file($path)) {
            abort(404);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: image/png');
        header('Cache-Control: no-store');
        readfile($path);
        exit;
    }

    public function font(string $font): void
    {
        Auth::requireAdmin();
        $layout = Layout::all();
        $file = $layout['fonts'][$font] ?? null;

        if ($file === null) {
            abort(404);
        }

        $path = Kernel::templates('fonts/' . $file);
        if (!is_file($path)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $extension === 'otf' ? 'font/otf' : 'font/ttf';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Cache-Control: max-age=86400');
        readfile($path);
        exit;
    }

    public function asset(): void
    {
        Auth::requireAdmin();

        $base = realpath(Kernel::templates());
        $path = realpath(Kernel::templates((string) Request::input('file', '')));

        if ($base === false || $path === false || !str_starts_with($path, $base) || !is_file($path)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Cache-Control: no-store');
        readfile($path);
        exit;
    }

    public function preview(): void
    {
        Auth::requireAdmin();

        $layout = json_decode((string) Request::input('layout', '{}'), true);
        if (!is_array($layout) || !isset($layout['faces'])) {
            abort(400, 'Diseño inválido.');
        }

        $face = (string) Request::input('face', 'frontal');
        if (!isset($layout['faces'][$face])) {
            abort(400, 'Cara inválida.');
        }

        $renderer = new CarnetRenderer();
        $image = $renderer->renderFace($face, $this->sampleCarnet(), $layout);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: image/png');
        header('Cache-Control: no-store');
        echo $image->getImageBlob();
        $image->clear();
        exit;
    }

    public function current(): void
    {
        Auth::requireAdmin();
        $layout = Layout::all();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        header('Cache-Control: no-store');
        echo json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function save(): void
    {
        Auth::requireAdmin();

        $layout = json_decode((string) Request::input('layout', '{}'), true);
        if (!is_array($layout) || !isset($layout['faces'])) {
            flash('error', 'No se pudo guardar: diseño inválido.');
            Request::redirect('diseno');
        }

        Layout::save($layout);
        flash('success', 'Diseño guardado. Regenera los carnets para aplicar los cambios.');
        Request::redirect('diseno');
    }

    public function reset(): void
    {
        Auth::requireAdmin();
        Layout::reset();
        flash('success', 'Diseño restaurado a los valores por defecto.');
        Request::redirect('diseno');
    }

    private function sampleCarnet(): array
    {
        return [
            'codigo' => 'VR-2026-000123',
            'qr_token' => 'DEMO0000000000000000000000000000000000',
            'cedula' => 'V-12345678',
            'nombres' => 'María',
            'apellidos' => 'González',
            'cargo' => 'Analista de Sistemas',
            'dependencia' => 'Plan Venezuela Renace',
            'foto' => null,
            'estado' => 'activo',
        ];
    }
}
