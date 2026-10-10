<?php

namespace App\Controllers;

use App\Repositories\CarnetRepository;
use App\Services\CarnetService;
use App\Services\QrService;
use App\Support\Auth;
use App\Support\Request;
use App\Support\View;

final class CarnetController
{
    private CarnetRepository $repo;
    private CarnetService $service;
    private QrService $qr;

    public function __construct()
    {
        $this->repo = new CarnetRepository();
        $this->service = new CarnetService();
        $this->qr = new QrService();
    }

    public function index(): void
    {
        $search = (string) Request::input('q', '');
        $estado = (string) Request::input('estado', '');
        $page = (int) Request::input('pagina', 1);

        $result = $this->repo->paginate($search, $estado, $page, 12);

        echo View::render('carnets.index', [
            'title' => 'Carnets',
            'result' => $result,
            'search' => $search,
            'estado' => $estado,
        ]);
    }

    public function create(): void
    {
        echo View::render('carnets.form', $this->formData(null));
    }

    public function store(): void
    {
        $input = $this->input();
        $errors = $this->service->validate($input);

        if ($errors) {
            keep_old($input);
            flash('errors', $errors);
            flash('error', 'Revise los datos marcados.');
            Request::redirect('carnets/crear');
        }

        try {
            $carnet = $this->service->create($input, Request::file('foto'), Auth::id());
            flash('success', 'Carnet ' . $carnet['codigo'] . ' creado correctamente.');
            Request::redirect('carnets/' . $carnet['id']);
        } catch (\Throwable $e) {
            keep_old($input);
            flash('error', $e->getMessage());
            Request::redirect('carnets/crear');
        }
    }

    public function show(string $id): void
    {
        $carnet = $this->repo->find((int) $id);
        if (!$carnet) {
            abort(404, 'Carnet no encontrado.');
        }

        echo View::render('carnets.show', [
            'title' => 'Carnet ' . $carnet['codigo'],
            'carnet' => $carnet,
            'verifyUrl' => $this->qr->verifyUrl($carnet['qr_token']),
            'stamp' => time(),
        ]);
    }

    public function edit(string $id): void
    {
        $carnet = $this->repo->find((int) $id);
        if (!$carnet) {
            abort(404, 'Carnet no encontrado.');
        }

        echo View::render('carnets.form', $this->formData($carnet));
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $carnet = $this->repo->find($id);
        if (!$carnet) {
            abort(404, 'Carnet no encontrado.');
        }

        $input = $this->input();
        $errors = $this->service->validate($input, $id);

        if ($errors) {
            keep_old($input);
            flash('errors', $errors);
            flash('error', 'Revise los datos marcados.');
            Request::redirect('carnets/' . $id . '/editar');
        }

        try {
            $this->service->update($id, $input, Request::file('foto'));
            flash('success', 'Carnet actualizado correctamente.');
            Request::redirect('carnets/' . $id);
        } catch (\Throwable $e) {
            keep_old($input);
            flash('error', $e->getMessage());
            Request::redirect('carnets/' . $id . '/editar');
        }
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        $carnet = $this->repo->find($id);
        if ($carnet) {
            $this->service->delete($id);
            flash('success', 'Carnet eliminado.');
        }
        Request::redirect('carnets');
    }

    public function regenerate(string $id): void
    {
        $id = (int) $id;
        if (!$this->repo->find($id)) {
            abort(404, 'Carnet no encontrado.');
        }
        $this->service->generate($id);
        flash('success', 'Imágenes regeneradas.');
        Request::redirect('carnets/' . $id);
    }

    public function file(string $id, string $face): void
    {
        $id = (int) $id;
        $carnet = $this->repo->find($id);
        if (!$carnet || !in_array($face, ['frontal', 'reverso'], true)) {
            abort(404);
        }

        $paths = $this->service->generatedPaths($id);
        $file = $paths[$face];
        if (!is_file($file)) {
            $this->service->generate($id);
        }

        $this->stream($file, 'image/png', $carnet['codigo'] . '-' . $face . '.png');
    }

    public function pdf(string $id): void
    {
        $id = (int) $id;
        $carnet = $this->repo->find($id);
        if (!$carnet) {
            abort(404, 'Carnet no encontrado.');
        }

        $paths = $this->service->generatedPaths($id);
        if (!is_file($paths['pdf'])) {
            $this->service->generate($id);
        }

        $this->stream($paths['pdf'], 'application/pdf', $carnet['codigo'] . '.pdf', (bool) Request::input('descargar', 1));
    }

    private function formData(?array $carnet): array
    {
        return [
            'title' => $carnet ? 'Editar carnet' : 'Nuevo carnet',
            'carnet' => $carnet,
            'old' => [
                'cedula' => old('cedula', $carnet['cedula'] ?? ''),
                'nombres' => old('nombres', $carnet['nombres'] ?? ''),
                'apellidos' => old('apellidos', $carnet['apellidos'] ?? ''),
                'cargo' => old('cargo', $carnet['cargo'] ?? ''),
                'dependencia' => old('dependencia', $carnet['dependencia'] ?? ''),
                'estado' => old('estado', $carnet['estado'] ?? 'activo'),
                'emitido_en' => old('emitido_en', $carnet['emitido_en'] ?? date('Y-m-d')),
                'vence_en' => old('vence_en', $carnet['vence_en'] ?? ''),
            ],
            'errors' => get_flash('errors', []),
        ];
    }

    private function input(): array
    {
        return [
            'cedula' => (string) Request::input('cedula', ''),
            'nombres' => (string) Request::input('nombres', ''),
            'apellidos' => (string) Request::input('apellidos', ''),
            'cargo' => (string) Request::input('cargo', ''),
            'dependencia' => (string) Request::input('dependencia', ''),
            'estado' => (string) Request::input('estado', 'activo'),
            'emitido_en' => (string) Request::input('emitido_en', date('Y-m-d')),
            'vence_en' => (string) Request::input('vence_en', ''),
        ];
    }

    private function stream(string $file, string $mime, string $filename, bool $download = false): void
    {
        if (!is_file($file)) {
            abort(404, 'Archivo no disponible.');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: private, max-age=0, no-cache');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        readfile($file);
        exit;
    }
}
