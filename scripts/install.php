<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$options = getopt('', [
    'host::', 'port::', 'database::', 'username::', 'password::',
    'admin-user::', 'admin-name::', 'admin-pass::',
    'force', 'no-create-db',
]);

function prompt(string $label, ?string $default = null): string
{
    $suffix = $default !== null && $default !== '' ? " [{$default}]" : '';
    fwrite(STDOUT, "{$label}{$suffix}: ");
    $line = fgets(STDIN);
    if ($line === false) {
        return (string) $default;
    }
    $line = trim($line);
    return $line === '' ? (string) $default : $line;
}

function value(array $options, string $key, string $label, ?string $default = null): string
{
    if (isset($options[$key])) {
        return (string) $options[$key];
    }
    return prompt($label, $default);
}

$host = value($options, 'host', 'Host MySQL', '127.0.0.1');
$port = (int) value($options, 'port', 'Puerto MySQL', '3306');
$database = value($options, 'database', 'Base de datos', 'carnets_vr');
$username = value($options, 'username', 'Usuario MySQL', 'root');
$password = value($options, 'password', 'Clave MySQL', '');
$adminUser = value($options, 'admin-user', 'Usuario administrador', 'admin');
$adminName = value($options, 'admin-name', 'Nombre del administrador', 'Administrador');
$adminPass = value($options, 'admin-pass', 'Clave del administrador', bin2hex(random_bytes(4)));

try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "ERROR: no se pudo conectar a MySQL: {$e->getMessage()}\n");
    exit(1);
}

if (!isset($options['no-create-db'])) {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Base de datos '{$database}' lista.\n";
}

$pdo->exec("USE `{$database}`");
$schema = file_get_contents($root . '/database/schema.sql');
$pdo->exec($schema);
echo "Esquema cargado.\n";

$count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($count === 0) {
    $stmt = $pdo->prepare(
        'INSERT INTO users (nombre, username, password_hash, role, activo, created_at, updated_at)
         VALUES (?, ?, ?, "admin", 1, NOW(), NOW())'
    );
    $stmt->execute([$adminName, $adminUser, password_hash($adminPass, PASSWORD_DEFAULT)]);
    echo "Administrador creado: {$adminUser} / {$adminPass}\n";
} else {
    echo "Ya existen usuarios; no se creó administrador.\n";
}

$configFile = $root . '/config/config.php';
if (is_file($configFile) && !isset($options['force'])) {
    echo "config/config.php ya existe (use --force para sobrescribir).\n";
} else {
    $appName = 'Carnets Venezuela Renace';
    $verifyBase = 'http://carnets-venezuela-renace.local/verificar';
    $template = "<?php\n\nreturn " . var_export([
        'app' => [
            'name' => $appName,
            'timezone' => 'America/Caracas',
            'debug' => false,
        ],
        'db' => [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
        ],
        'carnet' => [
            'verify_base' => $verifyBase,
            'qr_size' => 700,
            'qr_margin' => 4,
        ],
    ], true) . ";\n";
    file_put_contents($configFile, $template);
    echo "Configuración escrita en config/config.php.\n";
}

foreach (['uploads/photos', 'generated', 'logs'] as $dir) {
    $path = $root . '/storage/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

echo "\nInstalación completada.\n";
echo "Verifique el valor de carnet.verify_base en config/config.php con el dominio real.\n";
