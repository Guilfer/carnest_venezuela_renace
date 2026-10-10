<?php

namespace App\Repositories;

use App\Support\Database;

final class CarnetRepository
{
    public function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM carnets WHERE id = :id', ['id' => $id]);
    }

    public function findByToken(string $token): ?array
    {
        return Database::selectOne('SELECT * FROM carnets WHERE qr_token = :t', ['t' => $token]);
    }

    public function findByCedula(string $cedula, ?int $exceptId = null): ?array
    {
        $sql = 'SELECT * FROM carnets WHERE cedula = :c';
        $params = ['c' => $cedula];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        return Database::selectOne($sql, $params);
    }

    public function paginate(string $search = '', string $estado = '', int $page = 1, int $perPage = 12): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(c.cedula LIKE :s1 OR c.nombres LIKE :s2 OR c.apellidos LIKE :s3 OR c.codigo LIKE :s4 OR c.cargo LIKE :s5 OR c.dependencia LIKE :s6)';
            $like = '%' . $search . '%';
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
            $params['s4'] = $like;
            $params['s5'] = $like;
            $params['s6'] = $like;
        }
        if (in_array($estado, ['activo', 'suspendido', 'anulado'], true)) {
            $where[] = 'c.estado = :estado';
            $params['estado'] = $estado;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalRow = Database::selectOne("SELECT COUNT(*) AS total FROM carnets c {$whereSql}", $params);
        $total = (int) ($totalRow['total'] ?? 0);

        $perPage = max(1, $perPage);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $rows = Database::select(
            "SELECT c.* FROM carnets c {$whereSql}
             ORDER BY c.created_at DESC, c.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public function create(array $data): int
    {
        return Database::insert(
            'INSERT INTO carnets
                (codigo, qr_token, cedula, nombres, apellidos, cargo, dependencia, foto, estado, emitido_en, vence_en, created_by, created_at, updated_at)
             VALUES
                (:codigo, :qr_token, :cedula, :nombres, :apellidos, :cargo, :dependencia, :foto, :estado, :emitido_en, :vence_en, :created_by, NOW(), NOW())',
            [
                'codigo' => $data['codigo'],
                'qr_token' => $data['qr_token'],
                'cedula' => $data['cedula'],
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'cargo' => $data['cargo'],
                'dependencia' => $data['dependencia'],
                'foto' => $data['foto'] ?? null,
                'estado' => $data['estado'] ?? 'activo',
                'emitido_en' => $data['emitido_en'] ?? date('Y-m-d'),
                'vence_en' => $data['vence_en'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]
        );
    }

    public function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE carnets SET
                cedula = :cedula, nombres = :nombres, apellidos = :apellidos,
                cargo = :cargo, dependencia = :dependencia, foto = :foto,
                estado = :estado, emitido_en = :emitido_en, vence_en = :vence_en,
                updated_at = NOW()
             WHERE id = :id',
            [
                'cedula' => $data['cedula'],
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'cargo' => $data['cargo'],
                'dependencia' => $data['dependencia'],
                'foto' => $data['foto'] ?? null,
                'estado' => $data['estado'] ?? 'activo',
                'emitido_en' => $data['emitido_en'] ?? date('Y-m-d'),
                'vence_en' => $data['vence_en'] ?? null,
                'id' => $id,
            ]
        );
    }

    public function updateCodigo(int $id, string $codigo): void
    {
        Database::execute('UPDATE carnets SET codigo = :c WHERE id = :id', ['c' => $codigo, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        Database::execute('DELETE FROM carnets WHERE id = :id', ['id' => $id]);
    }

    public function count(): int
    {
        $row = Database::selectOne('SELECT COUNT(*) AS c FROM carnets');
        return (int) ($row['c'] ?? 0);
    }

    public function countByEstado(): array
    {
        $rows = Database::select('SELECT estado, COUNT(*) AS c FROM carnets GROUP BY estado');
        $result = ['activo' => 0, 'suspendido' => 0, 'anulado' => 0];
        foreach ($rows as $row) {
            $result[$row['estado']] = (int) $row['c'];
        }
        return $result;
    }
}
