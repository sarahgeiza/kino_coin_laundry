<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Pengguna extends BaseModel
{
    // Kolom password sengaja TIDAK ikut di-SELECT agar tidak bocor ke API/tampilan.
    private const COLUMNS = 'id_pengguna, nama, username, role';

    public function getAll(): array
    {
        return $this->fetchAll('SELECT ' . self::COLUMNS . ' FROM pengguna ORDER BY id_pengguna');
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT ' . self::COLUMNS . ' FROM pengguna WHERE id_pengguna = $1',
            [$id]
        );
    }

    public function create(string $nama, string $username, string $role, string $password): ?array
    {
        return $this->fetchOne(
            'INSERT INTO pengguna (nama, username, role, password)
             VALUES ($1, $2, $3, $4) RETURNING id_pengguna',
            [$nama, $username, $role, password_hash($password, PASSWORD_DEFAULT)]
        );
    }

    /** Jika $password null/kosong, password lama tidak diubah. */
    public function update(int $id, string $nama, string $username, string $role, ?string $password = null): bool
    {
        if ($password !== null && $password !== '') {
            return $this->execute(
                'UPDATE pengguna SET nama = $1, username = $2, role = $3, password = $4
                 WHERE id_pengguna = $5',
                [$nama, $username, $role, password_hash($password, PASSWORD_DEFAULT), $id]
            ) > 0;
        }

        return $this->execute(
            'UPDATE pengguna SET nama = $1, username = $2, role = $3 WHERE id_pengguna = $4',
            [$nama, $username, $role, $id]
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM pengguna WHERE id_pengguna = $1', [$id]) > 0;
    }

    /** Untuk login. Mengembalikan data pengguna (tanpa password) atau null. */
    public function login(string $username, string $password): ?array
    {
        $row = $this->fetchOne('SELECT * FROM pengguna WHERE username = $1', [$username]);

        if ($row === null || !password_verify($password, $row['password'])) {
            return null;
        }

        unset($row['password']);

        return $row;
    }
}
