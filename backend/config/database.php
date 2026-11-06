<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$vendorAutoload = $projectRoot . '/vendor/autoload.php';
$dotenvLoaded = false;
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
        $dotenv->safeLoad();
        $dotenvLoaded = true;
    }
}

if (!$dotenvLoaded && file_exists($projectRoot . '/.env')) {
    $lines = file($projectRoot . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $key = trim($k);
            if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                $_ENV[$key] = trim($v, " \t\n\r\0\x0B\"'");
            }
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

    $env = static function (string $key, string $default = ''): string {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value === false || $value === null ? $default : (string) $value;
    };

    $host = $cfg['host'] ?? $env('DB_HOST', 'localhost');
    $port = (string) ($cfg['port'] ?? $env('DB_PORT', '5432'));
    $dbname = $cfg['dbname'] ?? $env('DB_DATABASE', 'kino_coin_laundry2');
    $username = $cfg['username'] ?? $env('DB_USERNAME', 'postgres');
    $password = $cfg['password'] ?? $env('DB_PASSWORD', '12345678');

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
        ' user=' . $quote($username);

    if ($password !== '') {
        $connectionString .= ' password=' . $quote($password);
    }

    $connectionError = '';
    set_error_handler(static function (int $severity, string $message) use (&$connectionError): bool {
        $connectionError = $message;
        return true;
    });
    try {
        $conn = pg_connect($connectionString);
    } finally {
        restore_error_handler();
    }

    if ($conn === false) {
        error_log('Koneksi PostgreSQL gagal: ' . $connectionError);

        throw new RuntimeException(
            'Tidak dapat terhubung ke database.'
        );
    }

    return $conn;
}
