<?php

namespace App\Repositories;

use App\Support\Database;

final class UserRepository
{
    public function findById(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM users WHERE id = :id', ['id' => $id]);
    }

    public function findByUsername(string $username): ?array
    {
        return Database::selectOne('SELECT * FROM users WHERE username = :u', ['u' => $username]);
    }

    public function all(): array
    {
        return Database::select('SELECT id, nombre, username, role, activo, created_at FROM users ORDER BY nombre');
    }

    public function create(array $data): int
    {
        return Database::insert(
            'INSERT INTO users (nombre, username, password_hash, role, activo, created_at, updated_at)
             VALUES (:nombre, :username, :password_hash, :role, :activo, NOW(), NOW())',
            [
                'nombre' => $data['nombre'],
                'username' => $data['username'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => $data['role'] ?? 'operador',
                'activo' => $data['activo'] ?? 1,
            ]
        );
    }

    public function updatePassword(int $id, string $password): void
    {
        Database::execute(
            'UPDATE users SET password_hash = :h, updated_at = NOW() WHERE id = :id',
            ['h' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]
        );
    }

    public function updateProfile(int $id, array $data): void
    {
        Database::execute(
            'UPDATE users SET nombre = :nombre, role = :role, activo = :activo, updated_at = NOW() WHERE id = :id',
            [
                'nombre' => $data['nombre'],
                'role' => $data['role'],
                'activo' => $data['activo'],
                'id' => $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        Database::execute('DELETE FROM users WHERE id = :id', ['id' => $id]);
    }

    public function count(): int
    {
        $row = Database::selectOne('SELECT COUNT(*) AS c FROM users');
        return (int) ($row['c'] ?? 0);
    }
}
