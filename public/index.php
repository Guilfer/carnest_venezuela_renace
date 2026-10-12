<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CarnetController;
use App\Controllers\DashboardController;
use App\Controllers\DesignController;
use App\Controllers\UserController;
use App\Controllers\VerifyController;
use App\Kernel;
use App\Support\Router;

if (PHP_SAPI === 'cli-server') {
    $requested = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($requested !== '/' && is_file(__DIR__ . $requested)) {
        return false;
    }
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$configFile = $root . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo 'Falta el archivo config/config.php. Ejecute: php scripts/install.php';
    exit;
}

Kernel::boot(require $configFile);

$router = new Router();

$router->get('login', [AuthController::class, 'showLogin'], auth: false);
$router->post('login', [AuthController::class, 'login'], auth: false);
$router->post('logout', [AuthController::class, 'logout']);

$router->get('', [DashboardController::class, 'index']);
$router->get('/', [DashboardController::class, 'index']);

$router->get('carnets', [CarnetController::class, 'index']);
$router->get('carnets/crear', [CarnetController::class, 'create']);
$router->post('carnets/crear', [CarnetController::class, 'store']);
$router->get('carnets/{id}', [CarnetController::class, 'show']);
$router->get('carnets/{id}/editar', [CarnetController::class, 'edit']);
$router->post('carnets/{id}/editar', [CarnetController::class, 'update']);
$router->post('carnets/{id}/eliminar', [CarnetController::class, 'destroy']);
$router->post('carnets/{id}/regenerar', [CarnetController::class, 'regenerate']);
$router->get('carnets/{id}/frontal', fn ($id) => (new CarnetController())->file($id, 'frontal'));
$router->get('carnets/{id}/reverso', fn ($id) => (new CarnetController())->file($id, 'reverso'));
$router->get('carnets/{id}/pdf', [CarnetController::class, 'pdf']);

$router->get('verificar/{token}', [VerifyController::class, 'show'], auth: false);

$router->get('diseno', [DesignController::class, 'edit']);
$router->get('diseno/fondo/{face}', [DesignController::class, 'background']);
$router->get('diseno/fuente/{font}', [DesignController::class, 'font']);
$router->get('diseno/recurso', [DesignController::class, 'asset']);
$router->get('diseno/actual', [DesignController::class, 'current']);
$router->post('diseno', [DesignController::class, 'save']);
$router->post('diseno/previa', [DesignController::class, 'preview']);
$router->post('diseno/restaurar', [DesignController::class, 'reset']);

$router->get('usuarios', [UserController::class, 'index']);
$router->get('usuarios/crear', [UserController::class, 'create']);
$router->post('usuarios/crear', [UserController::class, 'store']);
$router->get('usuarios/{id}/editar', [UserController::class, 'edit']);
$router->post('usuarios/{id}/editar', [UserController::class, 'update']);
$router->post('usuarios/{id}/eliminar', [UserController::class, 'destroy']);

try {
    $router->dispatch();
} catch (Throwable $e) {
    if ((bool) config('app.debug', false)) {
        http_response_code(500);
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        error_log('[carnets] ' . $e->getMessage());
        abort(500, 'Ocurrió un error al procesar la solicitud.');
    }
}
