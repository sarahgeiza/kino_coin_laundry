<?php

require_once 'config/database.php';

require_once 'models/Pengguna.php';
require_once 'models/Pelanggan.php';
require_once 'models/Layanan.php';
require_once 'models/Shift.php';
require_once 'models/Transaksi.php';
require_once 'models/DetailTransaksi.php';

echo "<h2>Test Models Kino Coin Laundry</h2>";

$pengguna = new Pengguna($conn);
$pelanggan = new Pelanggan($conn);
$layanan = new Layanan($conn);
$shift = new Shift($conn);
$transaksi = new Transaksi($conn);
$detailTransaksi = new DetailTransaksi($conn);


// Test Pengguna
echo "<h3>1. Pengguna</h3>";
print_r($pengguna->getAll());


// Test Pelanggan
echo "<h3>2. Pelanggan</h3>";
print_r($pelanggan->getAll());


// Test Layanan
echo "<h3>3. Layanan</h3>";
print_r($layanan->getAll());


// Test Shift
echo "<h3>4. Shift</h3>";
print_r($shift->getAll());


// Test Transaksi
echo "<h3>5. Transaksi</h3>";
print_r($transaksi->getAll());


// Test Detail Transaksi
echo "<h3>6. Detail Transaksi</h3>";
print_r($detailTransaksi->getAll());

?>