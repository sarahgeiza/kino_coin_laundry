<?php
use function LaundryMvc\Config\e;
use function LaundryMvc\Config\rupiah;
use function LaundryMvc\Config\tanggal;
use const LaundryMvc\Config\LAYANAN;

$data = isset($data) && is_array($data) ? $data : [];
$data += [
    'totalPelanggan' => 0,
    'pesananHariIni' => 0,
    'omzetHariIni' => 0,
    'sedangProses' => 0,
    'terbaru' => [],
];
$data['terbaru'] = is_array($data['terbaru']) ? $data['terbaru'] : [];

$title = 'Beranda';
$active = 'home';
require __DIR__ . '/partials/header.php';
?>
<h2>Beranda</h2>
<div class="stat">
    <div><small>Total pelanggan</small><b><?= (int) $data['totalPelanggan'] ?></b></div>
    <div><small>Pesanan hari ini</small><b><?= (int) $data['pesananHariIni'] ?></b></div>
    <div><small>Pendapatan hari ini</small><b><?= rupiah($data['omzetHariIni']) ?></b></div>
    <div><small>Sedang diproses</small><b><?= (int) $data['sedangProses'] ?></b></div>
</div>

<div class="card">
    <h3>Mulai cepat</h3>
    <a class="btn" href="?page=scan">Pelanggan lama (Scan QR)</a>
    <a class="btn hijau" href="?page=baru">Pelanggan baru</a>
</div>

<div class="card">
    <h3>5 pesanan terbaru</h3>
    <?php if ($data['terbaru'] === []): ?>
        <p class="muted">Belum ada pesanan.</p>
    <?php else: ?>
        <div class="tabel-scroll"><table>
            <thead><tr><th>Waktu</th><th>Pelanggan</th><th>Layanan</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($data['terbaru'] as $order): ?>
                <tr>
                    <td><?= e(tanggal($order['created_at'])) ?></td>
                    <td><a href="?page=detail&kode=<?= urlencode($order['kode_pelanggan']) ?>"><?= e($order['nama_pelanggan']) ?></a></td>
                    <td><?= e(LAYANAN[$order['layanan']]['nama'] ?? $order['layanan']) ?> x<?= (int) $order['jumlah'] ?></td>
                    <td><?= rupiah($order['total']) ?></td>
                    <td><span class="badge <?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
