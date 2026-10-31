<?php

declare(strict_types=1);

use PgSql\Connection;

abstract class BaseModel
{
    protected Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /** Jalankan query dan lempar exception jika gagal. */
    protected function run(string $sql, array $params = []): \PgSql\Result
    {
        $result = $params === []
            ? @pg_query($this->conn, $sql)
            : @pg_query_params($this->conn, $sql, $params);

        if ($result === false) {
            throw new RuntimeException('Query gagal: ' . pg_last_error($this->conn));
        }

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return pg_fetch_all($this->run($sql, $params)) ?: [];
    }

    /** @return array<string, mixed>|null null jika data tidak ditemukan */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $row = pg_fetch_assoc($this->run($sql, $params));

        return $row === false ? null : $row;
    }

    /** Untuk INSERT/UPDATE/DELETE. Mengembalikan jumlah baris yang terpengaruh. */
    protected function execute(string $sql, array $params = []): int
    {
        return pg_affected_rows($this->run($sql, $params));
    }
}
