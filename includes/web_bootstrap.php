<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/middleware/session.php';

function web_query(string $sql, array $params = []): \PgSql\Result
{
    $index = 0;
    $sql = preg_replace_callback('/\?/', static function () use (&$index) {
        return '$' . (++$index);
    }, $sql);
    try {
        $conn = getConnection();
        $result = $params
            ? pg_query_params($conn, $sql, $params)
            : pg_query($conn, $sql);
        if ($result === false) {
            error_log('Kino DB query failed: ' . pg_last_error($conn));
            http_response_code(500);
            exit('Terjadi kesalahan pada server.');
        }
        return $result;
    } catch (Throwable $e) {
        error_log('Kino DB unavailable: ' . $e->getMessage());
        http_response_code(503);
        exit('Layanan database belum tersedia.');
    }
}
function web_rows(string $sql, array $params = []): array
{
    return pg_fetch_all(web_query($sql, $params)) ?: [];
}
function web_one(string $sql, array $params = []): ?array
{
    $row = pg_fetch_assoc(web_query($sql, $params));
    return $row === false ? null : $row;
}
function web_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function web_require_role(array $roles): array
{
    if (empty($_SESSION['user_id']) && empty($_SESSION['customer_id'])) {
        header('Location: ' . (str_contains($_SERVER['PHP_SELF'] ?? '', '/admin/') || str_contains($_SERVER['PHP_SELF'] ?? '', '/kasir/') || str_contains($_SERVER['PHP_SELF'] ?? '', '/pelanggan/') ? '../index.php' : 'index.php'));
        exit;
    }
    if (!empty($_SESSION['customer_id'])) {
        $user = web_one('SELECT id_pelanggan AS id, nama_pelanggan AS nama, no_hp, ?::text AS role FROM pelanggan WHERE id_pelanggan = ?', ['pelanggan', (int)$_SESSION['customer_id']]);
    } else {
        $user = web_one('SELECT id_pengguna AS id, nama, username, role FROM pengguna WHERE id_pengguna = ?', [(int)$_SESSION['user_id']]);
    }
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Anda tidak memiliki akses ke halaman ini.');
    }
    return $user;
}
function web_csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function web_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
function web_take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function format_rupiah(mixed $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}
