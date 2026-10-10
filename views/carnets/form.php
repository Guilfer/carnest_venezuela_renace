<?php
$editing = $carnet !== null;
$action = $editing ? url('carnets/' . $carnet['id'] . '/editar') : url('carnets/crear');
?>
<section class="panel">
    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form form-grid">
        <?= csrf_field() ?>

        <label>
            <span>Cédula <em>*</em></span>
            <input type="text" name="cedula" value="<?= e($old['cedula']) ?>" placeholder="V-12345678" required>
            <?php if (isset($errors['cedula'])): ?><small class="field-error"><?= e($errors['cedula']) ?></small><?php endif; ?>
        </label>

        <label>
            <span>Nombres <em>*</em></span>
            <input type="text" name="nombres" value="<?= e($old['nombres']) ?>" required>
            <?php if (isset($errors['nombres'])): ?><small class="field-error"><?= e($errors['nombres']) ?></small><?php endif; ?>
        </label>

        <label>
            <span>Apellidos <em>*</em></span>
            <input type="text" name="apellidos" value="<?= e($old['apellidos']) ?>" required>
            <?php if (isset($errors['apellidos'])): ?><small class="field-error"><?= e($errors['apellidos']) ?></small><?php endif; ?>
        </label>

        <label>
            <span>Cargo <em>*</em></span>
            <input type="text" name="cargo" value="<?= e($old['cargo']) ?>" required>
            <?php if (isset($errors['cargo'])): ?><small class="field-error"><?= e($errors['cargo']) ?></small><?php endif; ?>
        </label>

        <label class="span-2">
            <span>Dependencia <em>*</em></span>
            <input type="text" name="dependencia" value="<?= e($old['dependencia']) ?>" required>
            <?php if (isset($errors['dependencia'])): ?><small class="field-error"><?= e($errors['dependencia']) ?></small><?php endif; ?>
        </label>

        <label>
            <span>Estado</span>
            <select name="estado">
                <?php foreach (['activo', 'suspendido', 'anulado'] as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $old['estado'] === $opt ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Fecha de emisión</span>
            <input type="date" name="emitido_en" value="<?= e($old['emitido_en']) ?>">
        </label>

        <label>
            <span>Vence (opcional)</span>
            <input type="date" name="vence_en" value="<?= e($old['vence_en']) ?>">
        </label>

        <label class="span-2">
            <span>Foto del titular</span>
            <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
            <?php if ($editing && !empty($carnet['foto'])): ?>
                <small>Actualmente: <a href="<?= e(url('carnets/' . $carnet['id'] . '/frontal')) ?>" target="_blank">ver carnet actual</a></small>
            <?php endif; ?>
        </label>

        <div class="form-actions span-2">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Guardar cambios' : 'Emitir carnet' ?></button>
            <a class="btn btn-ghost" href="<?= e($editing ? url('carnets/' . $carnet['id']) : url('carnets')) ?>">Cancelar</a>
        </div>
    </form>
</section>
