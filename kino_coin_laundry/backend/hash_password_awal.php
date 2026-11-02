<?php
// CLI only: php backend/hash_password_awal.php USERNAME
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/config/database.php';

$username = trim((string)($argv[1] ?? ''));
if ($username === '') {
    fwrite(STDERR, "Usage: php backend/hash_password_awal.php USERNAME\n");
    exit(1);
}
fwrite(STDOUT, "Password baru: ");
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
if ($result === false || pg_affected_rows($result) < 1) {
    fwrite(STDERR, "Password tidak berhasil diperbarui.\n");
    exit(1);
}
fwrite(STDOUT, "Password berhasil diperbarui.\n");
