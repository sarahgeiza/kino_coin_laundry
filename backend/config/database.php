<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(
    dirname(__DIR__, 2)
);

$dotenv->safeLoad();

/**
 * Membuat koneksi PostgreSQL.
 * Koneksi hanya dibuat satu kali dalam satu request.
 */
function getConnection(): \PgSql\Connection
{
    static $conn = null;

    if ($conn instanceof \PgSql\Connection) {
        return $conn;
    }

    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '5432';
    $dbname = $_ENV['DB_DATABASE'] ?? '';
    $username = $_ENV['DB_USERNAME'] ?? '';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    if ($dbname === '' || $username === '') {
        throw new RuntimeException(
            'Konfigurasi database belum lengkap.'
        );
    }

    // Mengamankan nilai konfigurasi untuk connection string PostgreSQL.
    $quote = static function (string $value): string {
        return "'" . str_replace(
            ["\\", "'"],
            ["\\\\", "\\'"],
            $value
        ) . "'";
    };

    $connectionString =
        'host=' . $quote($host) .
        ' port=' . $quote($port) .
        ' dbname=' . $quote($dbname) .
        ' user=' . $quote($username) .
        ' password=' . $quote($password);

    $conn = @pg_connect($connectionString);

    if ($conn === false) {
        error_log('Koneksi PostgreSQL gagal.');

        throw new RuntimeException(
            'Tidak dapat terhubung ke database.'
        );
    }

    return $conn;
}
