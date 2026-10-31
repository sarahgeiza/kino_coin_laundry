<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Pengguna.php';
require_once __DIR__ . '/models/Pelanggan.php';
require_once __DIR__ . '/models/Layanan.php';
require_once __DIR__ . '/models/Shift.php';
require_once __DIR__ . '/models/Transaksi.php';
require_once __DIR__ . '/models/DetailTransaksi.php';

echo '<h2>Test Models Kino Coin Laundry</h2>';

$tests = [
    '1. Pengguna'         => new Pengguna($conn),
    '2. Pelanggan'        => new Pelanggan($conn),
    '3. Layanan'          => new Layanan($conn),
    '4. Shift'            => new Shift($conn),
    '5. Transaksi'        => new Transaksi($conn),
    '6. Detail Transaksi' => new DetailTransaksi($conn),
];

foreach ($tests as $title => $model) {
    echo '<h3>' . $title . '</h3><pre>';
    print_r($model->getAll());
    echo '</pre>';
}
