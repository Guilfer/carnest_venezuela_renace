<?php

namespace App\Services;

use App\Kernel;
use App\Repositories\CarnetRepository;
use App\Support\Database;

final class CarnetService
{
    private CarnetRepository $repo;
    private CarnetRenderer $renderer;
    private PdfService $pdf;

    public function __construct()
    {
        $this->repo = new CarnetRepository();
        $this->renderer = new CarnetRenderer();
        $this->pdf = new PdfService();
    }

    public function validate(array $input, ?int $id = null): array
    {
        $errors = [];
        $cedula = $this->normalizeCedula((string) ($input['cedula'] ?? ''));

        if ($cedula === '') {
            $errors['cedula'] = 'La cédula es obligatoria.';
        } elseif ($this->repo->findByCedula($cedula, $id)) {
            $errors['cedula'] = 'Ya existe un carnet con esa cédula.';
        }

        if (trim((string) ($input['nombres'] ?? '')) === '') {
            $errors['nombres'] = 'El nombre es obligatorio.';
        }
        if (trim((string) ($input['apellidos'] ?? '')) === '') {
            $errors['apellidos'] = 'El apellido es obligatorio.';
        }
        if (trim((string) ($input['cargo'] ?? '')) === '') {
            $errors['cargo'] = 'El cargo es obligatorio.';
        }
        if (trim((string) ($input['dependencia'] ?? '')) === '') {
            $errors['dependencia'] = 'La dependencia es obligatoria.';
        }

        return $errors;
    }

    public function create(array $input, ?array $file, int $userId): array
    {
        $foto = $this->savePhoto($file);

        $id = Database::transaction(function () use ($input, $foto, $userId) {
            $newId = $this->repo->create([
                'codigo' => '',
                'qr_token' => bin2hex(random_bytes(20)),
                'cedula' => $this->normalizeCedula((string) $input['cedula']),
                'nombres' => trim((string) $input['nombres']),
                'apellidos' => trim((string) $input['apellidos']),
                'cargo' => trim((string) $input['cargo']),
                'dependencia' => trim((string) $input['dependencia']),
                'foto' => $foto,
                'estado' => $input['estado'] ?? 'activo',
                'emitido_en' => $input['emitido_en'] ?? date('Y-m-d'),
                'vence_en' => !empty($input['vence_en']) ? $input['vence_en'] : null,
                'created_by' => $userId,
            ]);

            $this->repo->updateCodigo($newId, $this->buildCodigo($newId));
            return $newId;
        });

        $carnet = $this->repo->find($id);
        $this->generate($id);
        return $carnet;
    }

    public function update(int $id, array $input, ?array $file): array
    {
        $carnet = $this->repo->find($id);
        if (!$carnet) {
            throw new \RuntimeException('Carnet no encontrado.');
        }

        $foto = $carnet['foto'];
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $foto = $this->savePhoto($file) ?? $foto;
        }

        $this->repo->update($id, [
            'cedula' => $this->normalizeCedula((string) $input['cedula']),
            'nombres' => trim((string) $input['nombres']),
            'apellidos' => trim((string) $input['apellidos']),
            'cargo' => trim((string) $input['cargo']),
            'dependencia' => trim((string) $input['dependencia']),
            'foto' => $foto,
            'estado' => $input['estado'] ?? 'activo',
            'emitido_en' => $input['emitido_en'] ?? date('Y-m-d'),
            'vence_en' => !empty($input['vence_en']) ? $input['vence_en'] : null,
        ]);

        $this->generate($id);
        return $this->repo->find($id);
    }

    public function delete(int $id): void
    {
        $carnet = $this->repo->find($id);
        if (!$carnet) {
            return;
        }

        $dir = Kernel::storage('generated/' . $id);
        $this->deleteDirectory($dir);
        $this->repo->delete($id);
    }

    public function generate(int $id): array
    {
        $carnet = $this->repo->find($id);
        if (!$carnet) {
            throw new \RuntimeException('Carnet no encontrado.');
        }

        $dir = Kernel::storage('generated/' . $id);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $frontalPath = $dir . '/frontal.png';
        $reversoPath = $dir . '/reverso.png';
        $pdfPath = $dir . '/carnet.pdf';

        $faces = $this->renderer->renderAll($carnet);
        $this->renderer->savePng($faces['frontal'], $frontalPath);
        $this->renderer->savePng($faces['reverso'], $reversoPath);
        $faces['frontal']->clear();
        $faces['reverso']->clear();

        $widthMm = (float) config('carnet.pdf_width_mm', 70.0);
        $this->pdf->build($frontalPath, $reversoPath, $pdfPath, $widthMm);

        return [
            'frontal' => $frontalPath,
            'reverso' => $reversoPath,
            'pdf' => $pdfPath,
        ];
    }

    public function generatedPaths(int $id): array
    {
        $dir = Kernel::storage('generated/' . $id);
        return [
            'frontal' => $dir . '/frontal.png',
            'reverso' => $dir . '/reverso.png',
            'pdf' => $dir . '/carnet.pdf',
        ];
    }

    private function savePhoto(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > 8 * 1024 * 1024) {
            throw new \RuntimeException('La foto supera el máximo de 8 MB.');
        }

        $info = @getimagesize($file['tmp_name']);
        $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
        if ($info === false || !in_array($info[2], $allowed, true)) {
            throw new \RuntimeException('La foto debe ser JPG, PNG o WEBP.');
        }

        $dir = Kernel::storage('uploads/photos');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = bin2hex(random_bytes(16)) . '.jpg';
        $destination = $dir . '/' . $name;

        $image = new \Imagick($file['tmp_name']);
        $image->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
        $image->stripImage();
        $maxDim = 1200;
        if ($image->getImageWidth() > $maxDim || $image->getImageHeight() > $maxDim) {
            $image->thumbnailImage($maxDim, $maxDim, true);
        }
        $image->setImageFormat('jpeg');
        $image->setImageCompressionQuality(90);
        $image->setImageBackgroundColor('white');
        $flat = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
        $flat->writeImage($destination);
        $image->clear();
        $flat->clear();

        return 'photos/' . $name;
    }

    private function buildCodigo(int $id): string
    {
        return sprintf('VR-%s-%06d', date('Y'), $id);
    }

    private function normalizeCedula(string $cedula): string
    {
        $cedula = strtoupper(trim($cedula));
        if ($cedula === '') {
            return '';
        }
        if (preg_match('/^\s*([VEJPG])?\s*[-.]?\s*(\d+)\s*$/', $cedula, $m)) {
            $prefix = $m[1] ?? '';
            $digits = $m[2];
            return $prefix === '' ? $digits : $prefix . '-' . $digits;
        }
        return preg_replace('/\s+/', '', $cedula);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
