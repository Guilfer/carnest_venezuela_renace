<?php

namespace App\Controllers;

use App\Repositories\CarnetRepository;
use App\Repositories\UserRepository;
use App\Support\Auth;
use App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        $carnets = new CarnetRepository();
        $users = new UserRepository();

        $recent = array_slice($carnets->paginate('', '', 1, 6)['data'], 0, 6);

        echo View::render('dashboard.index', [
            'title' => 'Panel',
            'user' => Auth::user(),
            'totalCarnets' => $carnets->count(),
            'porEstado' => $carnets->countByEstado(),
            'totalUsuarios' => $users->count(),
            'recientes' => $recent,
        ]);
    }
}
