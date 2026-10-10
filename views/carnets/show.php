<div class="detail-grid">
    <section class="panel">
        <div class="panel-head">
            <h2><?= e($carnet['nombres'] . ' ' . $carnet['apellidos']) ?></h2>
            <span class="badge badge-<?= e($carnet['estado']) ?>"><?= e(ucfirst($carnet['estado'])) ?></span>
        </div>
        <dl class="detail-list">
            <div><dt>Código</dt><dd class="mono"><?= e($carnet['codigo']) ?></dd></div>
            <div><dt>Cédula</dt><dd><?= e($carnet['cedula']) ?></dd></div>
            <div><dt>Cargo</dt><dd><?= e($carnet['cargo']) ?></dd></div>
            <div><dt>Dependencia</dt><dd><?= e($carnet['dependencia']) ?></dd></div>
            <div><dt>Emitido</dt><dd><?= e($carnet['emitido_en']) ?></dd></div>
            <?php if (!empty($carnet['vence_en'])): ?>
                <div><dt>Vence</dt><dd><?= e($carnet['vence_en']) ?></dd></div>
            <?php endif; ?>
            <div><dt>Verificación</dt><dd><a href="<?= e($verifyUrl) ?>" target="_blank"><?= e($verifyUrl) ?></a></dd></div>
        </dl>

        <div class="actions">
            <a class="btn btn-primary" href="<?= e(url('carnets/' . $carnet['id'] . '/pdf')) ?>">Descargar PDF</a>
            <a class="btn" href="<?= e(url('carnets/' . $carnet['id'] . '/frontal?descargar=1')) ?>" download>PNG frontal</a>
            <a class="btn" href="<?= e(url('carnets/' . $carnet['id'] . '/reverso?descargar=1')) ?>" download>PNG reverso</a>
            <a class="btn btn-ghost" href="<?= e(url('carnets/' . $carnet['id'] . '/editar')) ?>">Editar</a>
            <form method="post" action="<?= e(url('carnets/' . $carnet['id'] . '/regenerar')) ?>" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost">Regenerar</button>
            </form>
            <form method="post" action="<?= e(url('carnets/' . $carnet['id'] . '/eliminar')) ?>" class="inline" onsubmit="return confirm('¿Eliminar definitivamente este carnet?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </form>
        </div>
    </section>

    <section class="panel preview-panel">
        <h2>Vista previa</h2>
        <div class="cards">
            <figure class="card-face">
                <img src="<?= e(url('carnets/' . $carnet['id'] . '/frontal') . '?v=' . $stamp) ?>" alt="Carnet frontal">
                <figcaption>Frontal</figcaption>
            </figure>
            <figure class="card-face">
                <img src="<?= e(url('carnets/' . $carnet['id'] . '/reverso') . '?v=' . $stamp) ?>" alt="Carnet reverso">
                <figcaption>Reverso</figcaption>
            </figure>
        </div>
    </section>
</div>
