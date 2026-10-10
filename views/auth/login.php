<div class="login-card">
    <div class="login-head">
        <img src="<?= e(asset('img/logo.png')) ?>" alt="Venezuela Renace" class="login-logo">
        <h1>Sistema de Carnets</h1>
        <p>Plan Venezuela Renace</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('login')) ?>" class="form">
        <?= csrf_field() ?>
        <label>
            <span>Usuario</span>
            <input type="text" name="username" value="<?= e(old('username')) ?>" required autofocus>
        </label>
        <label>
            <span>Contraseña</span>
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary btn-block">Iniciar sesión</button>
    </form>
</div>
