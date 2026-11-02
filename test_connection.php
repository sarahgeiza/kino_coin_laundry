<?php

require_once __DIR__ . '/backend/config/database.php';

try {
    $conn = getConnection();

    $result = pg_query(
        $conn,
        'SELECT current_database() AS database_name'
    );

    if ($result === false) {
        throw new RuntimeException(
            pg_last_error($conn)
        );
    }

    $data = pg_fetch_assoc($result);

    echo "Koneksi PostgreSQL berhasil!<br>";
    echo "Database: " . htmlspecialchars(
        $data['database_name']
    );

} catch (Throwable $e) {
    echo "Koneksi gagal: " . htmlspecialchars(
        $e->getMessage()
    );
}