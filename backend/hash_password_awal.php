<?php
// Jalankan SEKALI via browser/CLI, lalu HAPUS file ini.
// Mengganti password 'sementara' (plain text) menjadi hash bcrypt.
// Ganti password baru di bawah sebelum dijalankan.

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/BaseModel.php';

$passwordBaru = [
    'pemilik' => 'pemilik',
    'kasir'   => 'kasir',
];

foreach ($passwordBaru as $username => $plain) {
    if (str_starts_with($plain, 'GANTI_')) {
        die("Isi dulu password baru untuk '$username' di file ini.");
    }
    pg_query_params(
        $conn,
        'UPDATE pengguna SET password = $1 WHERE username = $2',
        [password_hash($plain, PASSWORD_DEFAULT), $username]
    );
    echo "Password '$username' sudah di-hash.<br>";
}
