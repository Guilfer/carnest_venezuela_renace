<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Carnets') ?> · Venezuela Renace</title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="guest-body">
    <div class="guest-wrap">
        <?= $content ?? '' ?>
        <p class="guest-foot">Plan Venezuela Renace · Sistema de carnets</p>
    </div>
</body>
</html>
