<?php

$root = dirname(__DIR__);
$layout = require $root . '/config/layout.php';

$template = $layout['faces']['frontal']['background'] ?? 'frontal.png';
$templatePath = $root . '/templates/' . $template;
$templateImage = new Imagick($templatePath);
$templateWidth = $templateImage->getImageWidth();
$templateHeight = $templateImage->getImageHeight();

$photo = null;
foreach (($layout['faces']['frontal']['elements'] ?? []) as $element) {
    if (($element['id'] ?? null) === 'foto') {
        $photo = $element;
        break;
    }
}

if ($photo === null) {
    fwrite(STDERR, "No se encontro el elemento 'foto' en el layout.\n");
    exit(1);
}

$boxW = max(1, (int) round($photo['w'] * $templateWidth));
$boxH = max(1, (int) round($photo['h'] * $templateHeight));

$masksDir = $root . '/templates/masks';
if (!is_dir($masksDir)) {
    mkdir($masksDir, 0775, true);
}

// Geometria del circulo/emblema incrustado en templates/frontal.png
// (medido en el carnet): centro (0.5112, 0.6431), radio 323px.
$circleCenterX = 0.5112;
$circleCenterY = 0.6431;
$circleRadius  = 323.0;

// La zona anaranjada de la plantilla marca exactamente el area donde va la
// foto (recuadro + onda inferior + hueco del circulo/emblema). Se construye la
// mascara clasificando ese naranja pixel a pixel y luego se perfora el circulo
// completo para que el emblema quede siempre sobre la foto.
$boxLeft = (int) round($photo['x'] * $templateWidth - $boxW / 2);
$boxTop  = (int) round($photo['y'] * $templateHeight - $boxH / 2);
$cx = (int) round($circleCenterX * $templateWidth) - $boxLeft;
$cy = (int) round($circleCenterY * $templateHeight) - $boxTop;
$radius = (int) $circleRadius;

$crop = new Imagick();
$crop->readImageBlob($templateImage->getImageBlob());
$crop->cropImage($boxW, $boxH, $boxLeft, $boxTop);
$crop->setImagePage($boxW, $boxH, 0, 0);

$mask = new Imagick();
$mask->newImage($boxW, $boxH, new ImagickPixel('transparent'), 'png');
$mask->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

$sourceIterator = $crop->getPixelIterator();
$maskIterator = $mask->getPixelIterator();
foreach ($sourceIterator as $pixels) {
    $maskRow = $maskIterator->getNextIteratorRow();
    foreach ($pixels as $index => $pixel) {
        $r = $pixel->getColorValue(Imagick::COLOR_RED) * 255;
        $g = $pixel->getColorValue(Imagick::COLOR_GREEN) * 255;
        $b = $pixel->getColorValue(Imagick::COLOR_BLUE) * 255;
        if ($r > 185 && $g > 35 && $g < 150 && $b < 120 && ($r - $b) > 80) {
            $maskRow[$index]->setColor('white');
        } else {
            $maskRow[$index]->setColorValue(Imagick::COLOR_ALPHA, 0.0);
        }
    }
    $maskIterator->syncIterator();
}
$maskIterator->syncIterator();
$crop->clear();

$disc = new Imagick();
$disc->newImage($boxW, $boxH, new ImagickPixel('transparent'), 'png');
$disc->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
$draw = new ImagickDraw();
$draw->setFillColor(new ImagickPixel('black'));
$draw->circle($cx, $cy, $cx + $radius, $cy);
$disc->drawImage($draw);

$mask->compositeImage($disc, Imagick::COMPOSITE_DSTOUT, 0, 0);
$mask->writeImage($masksDir . '/foto-frontal.png');
$mask->clear();
$disc->clear();

printf("mascara generada: templates/masks/foto-frontal.png (%dx%d) - forma del area de foto + circulo en (%.4f, %.4f) R=%d\n", $boxW, $boxH, $circleCenterX, $circleCenterY, $radius);
