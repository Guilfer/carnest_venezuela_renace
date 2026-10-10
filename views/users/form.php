<?php
$editing = $usuario !== null;
$action = $editing ? url('usuarios/' . $usuario['id'] . '/editar') : url('usuarios/crear');
?>
<section class="panel">
    <form method="post" action="<?= e($action) ?>" class="form form-grid">
        <?= csrf_field() ?>

        <label>
            <span>Nombre completo <em>*</em></span>
            <input type="text" name="nombre" value="<?= e(old('nombre', $usuario['nombre'] ?? '')) ?>" required>
            <?php if (isset($errors['nombre'])): ?><small class="field-error"><?= e($errors['nombre']) ?></small><?php endif; ?>
        </label>

        <label>
            <span>Usuario <em>*</em></span>
            <input type="text" name="username" value="<?= e(old('username', $usuario['username'] ?? '')) ?>" <?= $editing ? '' : 'required' ?> <?= $editing ? 'readonly' : '' ?>>
            <?php if (isset($errors['username'])): ?><small class="field-error"><?= e($errors['username']) ?></small><?php endif; ?>
        </label>

        <label>
            <span>Contraseña <?= $editing ? '(dejar vacío para no cambiar)' : '<em>*</em>' ?></span>
            <input type="password" name="password" <?= $editing ? '' : 'required' ?>>
            <?php if (isset($errors['password'])): ?><small class="field-error"><?= e($errors['password']) ?></small><?php endif; ?>
        </label>

        <label>
            <span>Rol</span>
            <select name="role">
                <?php $role = old('role', $usuario['role'] ?? 'operador'); ?>
                <option value="operador" <?= $role === 'operador' ? 'selected' : '' ?>>Operador</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
        </label>

        <label>
            <span>Activo</span>
            <?php $activo = (int) old('activo', $usuario['activo'] ?? 1); ?>
            <select name="activo">
                <option value="1" <?= $activo === 1 ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= $activo === 0 ? 'selected' : '' ?>>No</option>
            </select>
        </label>

        <div class="form-actions span-2">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Guardar cambios' : 'Crear usuario' ?></button>
            <a class="btn btn-ghost" href="<?= e(url('usuarios')) ?>">Cancelar</a>
        </div>
    </form>
</section>
