<?php
$layoutJson = json_encode($layout, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$sampleJson = json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<div class="design-toolbar">
    <div class="design-hint">Arrastra los elementos sobre la plantilla y ajusta sus propiedades. Usa "Vista previa real" para ver el render definitivo del servidor.</div>
    <div class="design-buttons">
        <button type="button" class="btn btn-primary" id="btn-preview">Vista previa real</button>
        <button type="button" class="btn" id="btn-save">Guardar diseño</button>
        <button type="button" class="btn" id="btn-restore">Restaurar</button>
        <form method="post" action="<?= e(url('diseno/restaurar')) ?>" class="inline" onsubmit="return confirm('¿Volver a los valores de fábrica? Se perderá el diseño guardado.');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger">Valores de fábrica</button>
        </form>
    </div>
</div>

<form method="post" action="<?= e(url('diseno')) ?>" id="save-form" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="layout" id="layout-input">
</form>

<div class="design-grid">
    <aside class="design-side">
        <div class="face-tabs" id="face-tabs">
            <button type="button" class="active" data-face="frontal">Frontal</button>
            <button type="button" data-face="reverso">Reverso</button>
        </div>
        <div class="elements-list" id="elements-list"></div>
        <div class="props" id="props">
            <p class="empty">Selecciona un elemento para editarlo.</p>
        </div>
    </aside>

    <main class="design-stage-wrap">
        <div class="stage" id="stage" style="aspect-ratio: 2596 / 4016;">
            <img id="stage-bg" class="stage-bg" alt="Plantilla">
            <div class="stage-layer" id="layer"></div>
        </div>
    </main>
</div>

<div class="modal hidden" id="preview-modal">
    <div class="modal-box">
        <button type="button" class="modal-close" id="preview-close">&times;</button>
        <img id="preview-img" alt="Vista previa real">
    </div>
</div>

<script type="application/json" id="layout-data"><?= $layoutJson ?></script>
<script type="application/json" id="sample-data"><?= $sampleJson ?></script>
<script type="application/json" id="design-config"><?= json_encode([
    'background' => url('diseno/fondo/{face}'),
    'font' => url('diseno/fuente/{font}'),
    'preview' => url('diseno/previa'),
    'current' => url('diseno/actual'),
    'token' => csrf_token(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= e(asset('js/design.js')) ?>"></script>
