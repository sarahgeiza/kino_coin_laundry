<?php

declare(strict_types=1);

namespace LaundryMvc\Models;

use PDO;

final class Pelanggan
{
    public function __construct(private PDO $pdo)
    {
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
    }

    public function findByCode(string $code): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM pelanggan WHERE kode_pelanggan = :code'
        );
        $statement->execute(['code' => $code]);
        $customer = $statement->fetch();

        return is_array($customer) ? $customer : null;
    }

    public function findByPhone(string $phone): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM pelanggan WHERE no_hp = :phone'
        );
        $statement->execute(['phone' => $phone]);
        $customer = $statement->fetch();

        return is_array($customer) ? $customer : null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM pelanggan WHERE id_pelanggan = :id'
        );
        $statement->execute(['id' => $id]);
        $customer = $statement->fetch();

        return is_array($customer) ? $customer : null;
    }

    public function create(string $name, string $phone): ?array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO pelanggan (kode_pelanggan, nama_pelanggan, no_hp)
             VALUES (:code, :name, :phone)
             RETURNING *'
        );
        $statement->execute([
            'code' => bin2hex(random_bytes(8)),
            'name' => $name,
            'phone' => $phone,
        ]);
        $customer = $statement->fetch();

        return is_array($customer) ? $customer : null;
    }
}
