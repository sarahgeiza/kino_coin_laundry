<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/config/database.php';

try {
    $connection = getConnection();
    $result = pg_query($connection, 'SELECT current_database() AS database_name, current_user AS database_user, version() AS version');
    if ($result === false) {
        throw new RuntimeException(pg_last_error($connection));
    }
    $row = pg_fetch_assoc($result);
    echo "Koneksi PostgreSQL berhasil.\n";
    echo "Database: " . $row['database_name'] . "\n";
    echo "User: " . $row['database_user'] . "\n";
    echo "Server: " . $row['version'] . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Koneksi gagal: " . $e->getMessage() . "\n";
}
