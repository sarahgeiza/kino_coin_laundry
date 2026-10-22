<?php

require_once __DIR__ . '/backend/config/database.php';

$result = pg_query($conn, 'SELECT * FROM layanan ORDER BY id_layanan');

if (!$result) {
    die('Query gagal: ' . pg_last_error($conn));
}

echo 'Database berhasil terhubung.<br>';

while ($row = pg_fetch_assoc($result)) {
    echo htmlspecialchars($row['nama_layanan']) . ' - Rp ' . $row['harga'] . '<br>';
}
