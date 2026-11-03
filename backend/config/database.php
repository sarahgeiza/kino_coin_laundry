<?php

declare(strict_types=1);

$vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->safeLoad();
    }
} elseif (file_exists(dirname(__DIR__, 2) . '/.env')) {
    $lines = file(dirname(__DIR__, 2) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

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

    $localConfig = __DIR__ . '/database.local.php';
    $cfg = file_exists($localConfig) ? require $localConfig : [];

    $host = $cfg['host'] ?? $_ENV['DB_HOST'] ?? 'localhost';
    $port = (string)($cfg['port'] ?? $_ENV['DB_PORT'] ?? '5432');
    $dbname = $cfg['dbname'] ?? $_ENV['DB_DATABASE'] ?? '';
    $username = $cfg['username'] ?? $_ENV['DB_USERNAME'] ?? '';
    $password = $cfg['password'] ?? $_ENV['DB_PASSWORD'] ?? '';

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
