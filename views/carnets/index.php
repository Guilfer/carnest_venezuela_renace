<section class="panel">
    <form method="get" action="<?= e(url('carnets')) ?>" class="filters">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar por nombre, cédula, cargo o código">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (['activo', 'suspendido', 'anulado'] as $opt): ?>
                <option value="<?= e($opt) ?>" <?= $estado === $opt ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filtrar</button>
        <a class="btn btn-primary" href="<?= e(url('carnets/crear')) ?>">Nuevo carnet</a>
    </form>

    <?php if (empty($result['data'])): ?>
        <p class="empty">No se encontraron carnets.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Titular</th>
                    <th>Cédula</th>
                    <th>Cargo</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['data'] as $c): ?>
                    <tr>
                        <td class="mono"><?= e($c['codigo']) ?></td>
                        <td><?= e($c['nombres'] . ' ' . $c['apellidos']) ?></td>
                        <td><?= e($c['cedula']) ?></td>
                        <td><?= e($c['cargo']) ?></td>
                        <td><span class="badge badge-<?= e($c['estado']) ?>"><?= e(ucfirst($c['estado'])) ?></span></td>
                        <td class="text-right">
                            <a href="<?= e(url('carnets/' . $c['id'])) ?>">Ver</a>
                            <a href="<?= e(url('carnets/' . $c['id'] . '/editar')) ?>">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($result['pages'] > 1): ?>
            <nav class="pagination">
                <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                    <?php
                    $query = http_build_query(['q' => $search, 'estado' => $estado, 'pagina' => $p]);
                    ?>
                    <a class="<?= $p === $result['page'] ? 'active' : '' ?>" href="<?= e(url('carnets?' . $query)) ?>"><?= e($p) ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
