<?php

declare(strict_types=1);

namespace LaundryMvc\Config;

use PDO;
use RuntimeException;

function database(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $password = getenv('DB_PASSWORD');
    if ($password === false) {
        throw new RuntimeException('Environment variable DB_PASSWORD belum diatur.');
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '5432';
    $name = getenv('DB_NAME') ?: 'kino_coin';
    $user = getenv('DB_USER') ?: 'postgres';

    $pdo = new PDO(
        sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name),
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}
