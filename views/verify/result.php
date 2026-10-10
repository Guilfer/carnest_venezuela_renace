<div class="verify-card">
    <div class="verify-head">
        <img src="<?= e(asset('img/logo.png')) ?>" alt="Venezuela Renace" class="login-logo">
        <h1>Verificación de carnet</h1>
    </div>

    <?php if (!$carnet): ?>
        <div class="verify-status verify-invalid">Carnet no encontrado</div>
        <p class="verify-note">El código escaneado no corresponde a un carnet emitido por este sistema.</p>
    <?php else: ?>
        <?php $valido = $carnet['estado'] === 'activo'; ?>
        <div class="verify-status <?= $valido ? 'verify-valid' : 'verify-invalid' ?>">
            <?= $valido ? 'Carnet válido' : 'Carnet ' . e($carnet['estado']) ?>
        </div>

        <dl class="detail-list verify-data">
            <div><dt>Titular</dt><dd><?= e($carnet['nombres'] . ' ' . $carnet['apellidos']) ?></dd></div>
            <div><dt>Cédula</dt><dd><?= e($carnet['cedula']) ?></dd></div>
            <div><dt>Cargo</dt><dd><?= e($carnet['cargo']) ?></dd></div>
            <div><dt>Dependencia</dt><dd><?= e($carnet['dependencia']) ?></dd></div>
            <div><dt>Código</dt><dd class="mono"><?= e($carnet['codigo']) ?></dd></div>
            <div><dt>Emitido</dt><dd><?= e($carnet['emitido_en']) ?></dd></div>
        </dl>

        <?php if (!$valido): ?>
            <p class="verify-note">Este carnet no se encuentra activo. Contacte a la institución emisora.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
