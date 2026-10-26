<?php
$page_title = 'Riwayat Transaksi - Kino Coin Laundry';
$header_role = 'Pelanggan';
$header_user = 'Budi';
$header_avatar = 'B';
$header_theme = 'orange';
$header_title = 'Riwayat Transaksi';
$header_subtitle = 'Daftar transaksi cucian Anda di Kino Laundry';
$current_role = 'pelanggan';
$active_tab = 'riwayat';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-title">
        <span>Cucian Budi Santoso</span>
    </div>

    <?php 
    $user_trxs = array_filter($_SESSION['transactions'], function($t) {
        return stripos($t['pelanggan'], 'Budi') !== false;
    });

    if (empty($user_trxs)): ?>
        <p style="font-size: 13px; color: var(--text-muted); text-align: center; padding: 20px;">Belum ada riwayat transaksi.</p>
    <?php else:
        foreach ($user_trxs as $trx):
    ?>
    <div class="transaction-item">
        <div class="trx-header">
            <div>
                <span class="trx-id"><?= htmlspecialchars($trx['id']) ?></span>
                <div class="trx-customer"><?= htmlspecialchars($trx['layanan']) ?> (<?= $trx['berat'] ?> kg)</div>
            </div>
            <span class="badge badge-success"><?= htmlspecialchars($trx['status']) ?></span>
        </div>
        <div class="trx-footer">
            <div>
                <div class="trx-amount"><?= format_rupiah($trx['total_biaya']) ?></div>
                <div class="trx-meta"><?= htmlspecialchars($trx['tanggal']) ?> • <?= htmlspecialchars($trx['waktu']) ?></div>
            </div>
        </div>
    </div>
    <?php 
        endforeach;
    endif; 
    ?>
</div>

<a href="index.php" class="btn btn-outline">Kembali ke Antrian</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
