<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Layanan.php';

$layanan = new Layanan($conn);

foreach ($layanan->getAll() as $row) {
    echo htmlspecialchars($row['nama_layanan']) . ' - Rp ' . $row['harga'] . '<br>';
}
