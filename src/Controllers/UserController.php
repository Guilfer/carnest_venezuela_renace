<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Support\Auth;
use App\Support\Request;
use App\Support\View;

final class UserController
{
    private UserRepository $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();
    }

    public function index(): void
    {
        Auth::requireAdmin();
        echo View::render('users.index', [
            'title' => 'Usuarios',
            'users' => $this->repo->all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireAdmin();
        echo View::render('users.form', [
            'title' => 'Nuevo usuario',
            'usuario' => null,
            'errors' => get_flash('errors', []),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        $data = $this->input();
        $errors = $this->validate($data);

        if ($errors) {
            keep_old($data);
            flash('errors', $errors);
            Request::redirect('usuarios/crear');
        }

        $this->repo->create($data);
        flash('success', 'Usuario creado correctamente.');
        Request::redirect('usuarios');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();
        $usuario = $this->repo->findById((int) $id);
        if (!$usuario) {
            abort(404, 'Usuario no encontrado.');
        }

        echo View::render('users.form', [
            'title' => 'Editar usuario',
            'usuario' => $usuario,
            'errors' => get_flash('errors', []),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        $id = (int) $id;
        $usuario = $this->repo->findById($id);
        if (!$usuario) {
            abort(404, 'Usuario no encontrado.');
        }

        $data = $this->input();
        if (!empty($data['password'])) {
            $errors = [];
            if (strlen($data['password']) < 6) {
                $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
            }
            if ($errors) {
                keep_old($data);
                flash('errors', $errors);
                Request::redirect('usuarios/' . $id . '/editar');
            }
            $this->repo->updatePassword($id, $data['password']);
        }

        $this->repo->updateProfile($id, [
            'nombre' => $data['nombre'],
            'role' => $data['role'],
            'activo' => $data['activo'],
        ]);

        flash('success', 'Usuario actualizado.');
        Request::redirect('usuarios');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        $id = (int) $id;

        if ($id === Auth::id()) {
            flash('error', 'No puede eliminar su propio usuario.');
            Request::redirect('usuarios');
        }

        $this->repo->delete($id);
        flash('success', 'Usuario eliminado.');
        Request::redirect('usuarios');
    }

    private function input(): array
    {
        return [
            'nombre' => (string) Request::input('nombre', ''),
            'username' => (string) Request::input('username', ''),
            'password' => (string) Request::input('password', ''),
            'role' => (string) Request::input('role', 'operador'),
            'activo' => (int) Request::input('activo', 1),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (trim($data['nombre']) === '') {
            $errors['nombre'] = 'El nombre es obligatorio.';
        }
        if (trim($data['username']) === '') {
            $errors['username'] = 'El usuario es obligatorio.';
        } elseif ($this->repo->findByUsername($data['username'])) {
            $errors['username'] = 'Ese usuario ya existe.';
        }
        if (strlen($data['password']) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
        }
        return $errors;
    }
}
