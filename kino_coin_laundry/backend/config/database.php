<?php
declare(strict_types=1);

/**
 * Satu koneksi PostgreSQL native untuk seluruh aplikasi.
 * Model, controller, helper, dan halaman web menggunakan pg_*.
 */
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

if (!class_exists(\Dotenv\Dotenv::class)) {
    throw new RuntimeException('Dependency vlucas/phpdotenv belum tersedia. Jalankan composer install.');
}

$projectRoot = dirname(__DIR__, 2);
if (is_file($projectRoot . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
    $dotenv->safeLoad();
}

function getConnection(): \PgSql\Connection
{
    static $connection = null;

    if ($connection instanceof \PgSql\Connection) {
        return $connection;
    }

    if (!function_exists('pg_connect')) {
        throw new RuntimeException('Ekstensi PHP pgsql belum aktif pada PHP yang digunakan.');
    }

    $required = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'];
    foreach ($required as $key) {
        if (!isset($_ENV[$key]) || trim((string) $_ENV[$key]) === '') {
            throw new RuntimeException('Konfigurasi environment ' . $key . ' belum diisi.');
        }
    }

    $escape = static function (string $value): string {
        return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";
    };

    $connInfo = 'host=' . $escape((string) $_ENV['DB_HOST'])
        . ' port=' . $escape((string) $_ENV['DB_PORT'])
        . ' dbname=' . $escape((string) $_ENV['DB_DATABASE'])
        . ' user=' . $escape((string) $_ENV['DB_USERNAME'])
        . ' password=' . $escape((string) ($_ENV['DB_PASSWORD'] ?? ''));

    $connection = @pg_connect($connInfo, PGSQL_CONNECT_FORCE_NEW);
    if (!$connection instanceof \PgSql\Connection) {
        throw new RuntimeException('Koneksi PostgreSQL gagal. Periksa host, port, database, username, password, dan ekstensi pgsql.');
    }

    return $connection;
}
