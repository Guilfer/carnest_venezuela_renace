<?php

namespace App\Controllers;

use App\Support\Auth;
use App\Support\Request;
use App\Support\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Request::redirect('');
        }

        echo View::render('auth.login', [
            'title' => 'Iniciar sesión',
            'error' => get_flash('error'),
        ], 'layouts.guest');
    }

    public function login(): void
    {
        $username = (string) Request::input('username', '');
        $password = (string) Request::input('password', '');

        if ($username === '' || $password === '' || !Auth::attempt($username, $password)) {
            keep_old(['username' => $username]);
            flash('error', 'Usuario o contraseña incorrectos.');
            Request::redirect('login');
        }

        clear_old();
        flash('success', 'Bienvenido, ' . (Auth::user()['nombre'] ?? ''));
        Request::redirect('');
    }

    public function logout(): void
    {
        Auth::logout();
        Request::redirect('login');
    }
}
