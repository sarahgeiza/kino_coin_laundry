<?php
// CLI only: php scripts/reset_password.php USERNAME
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/../backend/config/database.php';

$username = trim((string)($argv[1] ?? ''));
if ($username === '') {
    fwrite(STDERR, "Usage: php scripts/reset_password.php USERNAME\n");
    exit(1);
}
fwrite(STDOUT, "Password baru untuk {$username}: ");
$password = trim((string)fgets(STDIN));
if (strlen($password) < 8) {
    fwrite(STDERR, "Password minimal 8 karakter.\n");
    exit(1);
}
$result = pg_query_params(
    getConnection(),
    'UPDATE pengguna SET password = $1 WHERE username = $2',
    [password_hash($password, PASSWORD_DEFAULT), $username]
);
if ($result === false) {
    fwrite(STDERR, "Gagal memperbarui password.\n");
    exit(1);
}
if (pg_affected_rows($result) < 1) {
    fwrite(STDERR, "Username tidak ditemukan.\n");
    exit(1);
}
fwrite(STDOUT, "Password berhasil diperbarui.\n");
