<?php

declare(strict_types=1);

use App\Kernel;
use App\Repositories\CarnetRepository;
use App\Support\Database;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$options = getopt('', ['file::', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo "Uso: php scripts/migrate-listado.php [--file=info/LISTADO...xlsx] [--dry-run]\n";
    exit(0);
}

$configFile = $root . '/config/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "ERROR: falta config/config.php.\n");
    exit(1);
}
Kernel::boot(require $configFile);

$file = $options['file'] ?? $root . '/info/LISTADO DE PERSONAL PARA ELABORACION DE CARNET.xlsx';
if (!is_file($file)) {
    fwrite(STDERR, "ERROR: no se encuentra el archivo: {$file}\n");
    exit(1);
}

$dryRun = isset($options['dry-run']);

function readXlsx(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('No se pudo abrir el XLSX.');
    }

    $strings = [];
    $ss = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
    if ($ss !== false) {
        foreach ($ss->si as $si) {
            $t = (string) $si->t;
            if (isset($si->r)) {
                $t = '';
                foreach ($si->r as $r) {
                    $t .= (string) $r->t;
                }
            }
            $strings[] = trim($t);
        }
    }

    $sheetName = 'xl/worksheets/sheet1.xml';
    $xml = simplexml_load_string($zip->getFromName($sheetName));
    $zip->close();
    if ($xml === false) {
        throw new RuntimeException('No se pudo leer el contenido del XLSX.');
    }

    $rows = [];
    foreach ($xml->sheetData->row as $row) {
        $r = (int) $row['r'];
        $cells = [];
        foreach ($row->c as $c) {
            $ref = (string) $c['r'];
            if (preg_match('/^[A-Z]+/', $ref, $m) !== 1) {
                continue;
            }
            $col = 0;
            $len = strlen($m[0]);
            for ($i = 0; $i < $len; $i++) {
                $col = $col * 26 + (ord($m[0][$i]) - 64);
            }
            $type = (string) $c['t'];
            if ($type === 's') {
                $val = $strings[(int) $c->v] ?? '';
            } elseif ($type === 'inlineStr') {
                $val = trim((string) $c->is->t);
            } else {
                $val = trim((string) $c->v);
            }
            $cells[$col - 1] = $val;
        }
        ksort($cells);
        $rows[$r] = $cells;
    }

    return $rows;
}

$rows = readXlsx($file);

$header = $rows[1] ?? [];
$colOf = [];
foreach ($header as $idx => $label) {
    $key = mb_strtoupper(trim((string) $label));
    if (!isset($colOf[$key])) {
        $colOf[$key] = $idx;
    }
}

$overrides = [
    '5484462' => ['SIBONEY DEL VALLE', 'TINEO'],
    '7614951' => ['MARIA DEL PILAR', 'BAENA'],
    '13515900' => ['EVETSY', 'TORRES HIDALGO'],
    '13740766' => ['ZULAY DEL CARMEN', 'BASTIDAS HIDALGO'],
    '14095993' => ['JONATHAN ERNESTO', 'MACHADO RAVELO'],
    '17862297' => ['ANGELO', 'PEREZ SARRA'],
    '19658795' => ['TRINIDAD DEL CARMEN', 'PEREZ CARREÑO'],
    '25385230' => ['IVANI', 'VALSINT ALCAZAR DIAZ'],
    '27048271' => ['ANA DEL CARMEN', 'PEÑA'],
];

$fNro = $colOf['N°'] ?? 0;
$fCed = $colOf['CEDULA DE IDENTIDAD'] ?? null;
$fName = $colOf['NOMBRES Y APELLIDOS'] ?? null;
$fCargo = $colOf['CARGO'] ?? null;
$fDep = $colOf['OFICINA DE  ADSCRIPCION'] ?? $colOf['OFICINA DE ADSCRIPCION'] ?? null;

if ($fCed === null || $fName === null || $fCargo === null || $fDep === null) {
    fwrite(STDERR, "ERROR: el XLSX no tiene las columnas esperadas (encabezado en fila 1).\n");
    exit(1);
}

function normalizeCedula(string $cedula): string
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

function splitName(string $full): array
{
    $tokens = preg_split('/\s+/', trim($full));
    $tokens = array_values(array_filter($tokens, fn ($t) => $t !== ''));
    if (!$tokens) {
        return ['', ''];
    }
    if (count($tokens) === 1) {
        return [$tokens[0], ''];
    }
    if (count($tokens) === 2) {
        return [$tokens[0], $tokens[1]];
    }
    return [implode(' ', array_slice($tokens, 0, 2)), implode(' ', array_slice($tokens, 2))];
}

$registros = [];
foreach ($rows as $r => $cells) {
    if ($r === 1) {
        continue;
    }
    $nombre = trim((string) ($cells[$fName] ?? ''));
    $nro = trim((string) ($cells[$fNro] ?? ''));
    if ($nombre === '' || $nro === '') {
        continue;
    }

    [$nombres, $apellidos] = splitName($nombre);
    $cedula = normalizeCedula((string) ($cells[$fCed] ?? ''));
    if (isset($overrides[$cedula])) {
        [$nombres, $apellidos] = $overrides[$cedula];
    }
    $registros[] = [
        'nro' => $nro,
        'linea_excel' => $r,
        'cedula' => $cedula,
        'nombres' => mb_strtoupper($nombres),
        'apellidos' => mb_strtoupper($apellidos),
        'cargo' => mb_strtoupper(trim((string) ($cells[$fCargo] ?? ''))),
        'dependencia' => mb_strtoupper(trim((string) ($cells[$fDep] ?? ''))),
    ];
}

echo 'Registros a migrar: ' . count($registros) . "\n";
echo str_repeat('-', 160) . "\n";
printf("%-4s | %-14s | %-28s | %-28s | %-30s | %s\n", '#', 'CÉDULA', 'NOMBRES', 'APELLIDOS', 'CARGO', 'DEPENDENCIA');
echo str_repeat('-', 160) . "\n";

$sinCedula = 0;
foreach ($registros as $reg) {
    printf(
        "%-4s | %-14s | %-28s | %-28s | %-30s | %s\n",
        $reg['nro'],
        $reg['cedula'],
        mb_substr($reg['nombres'], 0, 28),
        mb_substr($reg['apellidos'], 0, 28),
        mb_substr($reg['cargo'], 0, 30),
        mb_substr($reg['dependencia'], 0, 60)
    );
    if ($reg['cedula'] === '') {
        $sinCedula++;
    }
}

if ($sinCedula > 0) {
    fwrite(STDERR, "ERROR: hay {$sinCedula} registro(s) sin cédula. Corrija el XLSX.\n");
    exit(1);
}

if ($dryRun) {
    echo "\nMODO DRY-RUN: no se insertó nada.\n";
    exit(0);
}

$admin = Database::selectOne('SELECT id FROM users WHERE role = :role AND activo = 1 ORDER BY id ASC LIMIT 1', ['role' => 'admin']);
$userId = $admin['id'] ?? null;

$repo = new CarnetRepository();
$insertados = 0;
$omitidos = [];

Database::transaction(function () use ($repo, $registros, $userId, &$insertados, &$omitidos): void {
    foreach ($registros as $reg) {
        if ($repo->findByCedula($reg['cedula'])) {
            $omitidos[] = "{$reg['cedula']} ({$reg['nombres']} {$reg['apellidos']})";
            continue;
        }

        $id = $repo->create([
            'codigo' => '',
            'qr_token' => bin2hex(random_bytes(20)),
            'cedula' => $reg['cedula'],
            'nombres' => $reg['nombres'],
            'apellidos' => $reg['apellidos'],
            'cargo' => $reg['cargo'],
            'dependencia' => $reg['dependencia'],
            'foto' => null,
            'estado' => 'activo',
            'emitido_en' => date('Y-m-d'),
            'vence_en' => null,
            'created_by' => $userId,
        ]);

        $repo->updateCodigo($id, sprintf('VR-%s-%06d', date('Y'), $id));
        $insertados++;
    }
});

$total = (int) (Database::selectOne('SELECT COUNT(*) AS c FROM carnets')['c'] ?? 0);
echo "\nInsertados: {$insertados} | Omitidos (cédula ya existe): " . count($omitidos) . "\n";
foreach ($omitidos as $o) {
    echo "  - omitido: {$o}\n";
}
echo "Total de carnets en BD: {$total}\n";