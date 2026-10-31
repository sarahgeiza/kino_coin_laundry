<?php

declare(strict_types=1);

use PgSql\Connection;

/**
 * Membuat koneksi PostgreSQL.
 * File ini TIDAK boleh mencetak apa pun (echo) agar aman dipakai di API/JSON.
 * Kredensial dibaca dari config/database.local.php (tidak di-commit).
 */
function getConnection(): Connection
{
    static $conn = null;

    if ($conn instanceof Connection) {
        return $conn;
    }

    $configFile = __DIR__ . '/database.local.php';
    if (!is_file($configFile)) {
        throw new RuntimeException(
            'File config/database.local.php belum ada. Salin dari database.example.php.'
        );
    }

    $cfg = require $configFile;

    $dsn = sprintf(
        'host=%s port=%s dbname=%s user=%s password=%s',
        $cfg['host'],
        $cfg['port'],
        $cfg['dbname'],
        $cfg['username'],
        $cfg['password']
    );

    $conn = @pg_connect($dsn);

    if ($conn === false) {
        throw new RuntimeException('Koneksi database gagal.');
    }

    return $conn;
}

$conn = getConnection();
