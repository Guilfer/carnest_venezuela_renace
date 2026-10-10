<section class="panel">
    <div class="panel-head">
        <h2>Usuarios del sistema</h2>
        <a class="btn btn-primary" href="<?= e(url('usuarios/crear')) ?>">Nuevo usuario</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Activo</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['nombre']) ?></td>
                    <td class="mono"><?= e($u['username']) ?></td>
                    <td><?= e(ucfirst($u['role'])) ?></td>
                    <td><?= ((int) $u['activo'] === 1) ? 'Sí' : 'No' ?></td>
                    <td class="text-right">
                        <a href="<?= e(url('usuarios/' . $u['id'] . '/editar')) ?>">Editar</a>
                        <form method="post" action="<?= e(url('usuarios/' . $u['id'] . '/eliminar')) ?>" class="inline" onsubmit="return confirm('¿Eliminar este usuario?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="link-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
