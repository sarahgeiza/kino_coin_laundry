<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Layanan extends BaseModel
{
    public function getAll(): array
    {
        return $this->fetchAll('SELECT * FROM layanan ORDER BY id_layanan');
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM layanan WHERE id_layanan = $1', [$id]);
    }

    public function create(string $nama_layanan, int $harga, ?float $berat_standar): ?array
    {
        return $this->fetchOne(
            'INSERT INTO layanan (nama_layanan, harga, berat_standar)
             VALUES ($1, $2, $3) RETURNING id_layanan',
            [$nama_layanan, $harga, $berat_standar]
        );
    }

    public function update(int $id, string $nama_layanan, int $harga, ?float $berat_standar): bool
    {
        return $this->execute(
            'UPDATE layanan SET nama_layanan = $1, harga = $2, berat_standar = $3
             WHERE id_layanan = $4',
            [$nama_layanan, $harga, $berat_standar, $id]
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM layanan WHERE id_layanan = $1', [$id]) > 0;
    }
}
