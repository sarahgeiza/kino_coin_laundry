<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Pelanggan extends BaseModel
{
    public function getAll(): array
    {
        return $this->fetchAll('SELECT * FROM pelanggan ORDER BY id_pelanggan');
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM pelanggan WHERE id_pelanggan = $1', [$id]);
    }

    public function create(string $nama_pelanggan, string $no_hp): ?array
    {
        return $this->fetchOne(
            'INSERT INTO pelanggan (nama_pelanggan, no_hp)
             VALUES ($1, $2) RETURNING id_pelanggan',
            [$nama_pelanggan, $no_hp]
        );
    }

    public function update(int $id, string $nama_pelanggan, string $no_hp): bool
    {
        return $this->execute(
            'UPDATE pelanggan SET nama_pelanggan = $1, no_hp = $2 WHERE id_pelanggan = $3',
            [$nama_pelanggan, $no_hp, $id]
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM pelanggan WHERE id_pelanggan = $1', [$id]) > 0;
    }
}
