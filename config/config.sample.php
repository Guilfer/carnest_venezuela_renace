<?php

return [
    'app' => [
        'name' => 'Carnets Venezuela Renace',
        'timezone' => 'America/Caracas',
        'debug' => false,
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'carnets_vr',
        'username' => 'carnets_vr',
        'password' => 'CAMBIAR',
        'charset' => 'utf8mb4',
    ],
    'carnet' => [
        'verify_base' => 'http://carnets-venezuela-renace.local/verificar',
        'qr_size' => 700,
        'qr_margin' => 4,
    ],
];
