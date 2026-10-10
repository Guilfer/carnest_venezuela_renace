<?php

namespace App\Controllers;

use App\Repositories\CarnetRepository;
use App\Support\View;

final class VerifyController
{
    public function show(string $token): void
    {
        $repo = new CarnetRepository();
        $carnet = $repo->findByToken($token);

        echo View::render('verify.result', [
            'title' => 'Verificación de carnet',
            'carnet' => $carnet,
            'token' => $token,
        ], 'layouts.guest');
    }
}
