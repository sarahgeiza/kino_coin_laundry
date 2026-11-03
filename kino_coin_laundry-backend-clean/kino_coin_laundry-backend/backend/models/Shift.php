<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Shift extends BaseModel
{
    public function getAll(): array
    {
        return $this->fetchAll('SELECT * FROM shift ORDER BY id_shift');
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM shift WHERE id_shift = $1', [$id]);
    }
}
