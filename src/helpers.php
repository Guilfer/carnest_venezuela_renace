<?php

use App\Kernel;

function config(?string $key = null, mixed $default = null): mixed
{
    return Kernel::config($key, $default);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = str_replace('\\', '/', dirname($script));
        $base = ($dir === '.' || $dir === '/') ? '' : rtrim($dir, '/');
    }
    return $base . '/' . ltrim($path, '/');
}

function url(string $path = ''): string
{
    return base_url($path);
}

function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

function old(string $key, mixed $default = ''): mixed
{
    $old = $_SESSION['_old'] ?? [];
    return $old[$key] ?? $default;
}

function keep_old(array $data): void
{
    unset($data['_token'], $data['password'], $data['password_confirm']);
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function flash(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function get_flash(string $key, mixed $default = null): mixed
{
    $value = $_SESSION['_flash'][$key] ?? $default;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    $messages = [403 => 'Acceso denegado', 404 => 'Recurso no encontrado', 500 => 'Error interno'];
    $title = $messages[$code] ?? 'Error';
    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $code . ' - ' . e($title) . '</title>'
        . '<link rel="stylesheet" href="' . e(asset('css/app.css')) . '"></head>'
        . '<body class="error-page"><div class="error-box"><h1>' . $code . '</h1>'
        . '<p>' . e($message !== '' ? $message : $title) . '</p>'
        . '<a class="btn" href="' . e(url('')) . '">Volver al inicio</a></div></body></html>';
    echo $html;
    exit;
}
