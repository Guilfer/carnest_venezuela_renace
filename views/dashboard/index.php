<div class="stat-grid">
    <div class="stat-card">
        <span class="stat-label">Carnets emitidos</span>
        <span class="stat-value"><?= e($totalCarnets) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Activos</span>
        <span class="stat-value text-ok"><?= e($porEstado['activo']) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Suspendidos</span>
        <span class="stat-value text-warn"><?= e($porEstado['suspendido']) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Anulados</span>
        <span class="stat-value text-danger"><?= e($porEstado['anulado']) ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Usuarios</span>
        <span class="stat-value"><?= e($totalUsuarios) ?></span>
    </div>
</div>

<section class="panel">
    <div class="panel-head">
        <h2>Carnets recientes</h2>
        <a class="btn btn-primary" href="<?= e(url('carnets/crear')) ?>">Emitir carnet</a>
    </div>

    <?php if (empty($recientes)): ?>
        <p class="empty">Aún no hay carnets registrados.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Titular</th>
                    <th>Cédula</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recientes as $c): ?>
                    <tr>
                        <td class="mono"><?= e($c['codigo']) ?></td>
                        <td><?= e($c['nombres'] . ' ' . $c['apellidos']) ?></td>
                        <td><?= e($c['cedula']) ?></td>
                        <td><span class="badge badge-<?= e($c['estado']) ?>"><?= e(ucfirst($c['estado'])) ?></span></td>
                        <td class="text-right"><a href="<?= e(url('carnets/' . $c['id'])) ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
