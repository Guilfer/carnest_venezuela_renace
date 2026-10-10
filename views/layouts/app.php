<?php
use App\Support\Auth;

$currentUser = Auth::user();
$path = \App\Support\Request::path();
$success = get_flash('success');
$error = get_flash('error');

$isActive = function (string $prefix) use ($path): string {
    if ($prefix === '') {
        return $path === '/' ? 'active' : '';
    }
    return str_starts_with($path, '/' . $prefix) ? 'active' : '';
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Sistema de carnets') ?> · Venezuela Renace</title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <img src="<?= e(asset('img/logo.png')) ?>" alt="Venezuela Renace" class="brand-logo">
            <div class="brand-text">
                <strong>Carnets</strong>
                <span>Venezuela Renace</span>
            </div>
        </div>
        <nav class="nav">
            <a class="<?= $isActive('') ?>" href="<?= e(url('')) ?>">Panel</a>
            <a class="<?= $isActive('carnets') ?>" href="<?= e(url('carnets')) ?>">Carnets</a>
            <a class="<?= $isActive('carnets/crear') ?>" href="<?= e(url('carnets/crear')) ?>">Nuevo carnet</a>
            <?php if (Auth::isAdmin()): ?>
                <a class="<?= $isActive('diseno') ?>" href="<?= e(url('diseno')) ?>">Diseño</a>
                <a class="<?= $isActive('usuarios') ?>" href="<?= e(url('usuarios')) ?>">Usuarios</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-chip">
                <span class="user-name"><?= e($currentUser['nombre'] ?? '') ?></span>
                <span class="user-role"><?= e(ucfirst($currentUser['role'] ?? '')) ?></span>
            </div>
            <form method="post" action="<?= e(url('logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-block">Cerrar sesión</button>
            </form>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <h1><?= e($title ?? '') ?></h1>
        </header>
        <div class="content">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </div>
    </main>
</div>
</body>
</html>
