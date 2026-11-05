<?php
$page_title = 'Beranda Pelanggan - Kino Coin Laundry';
$app_role = 'pelanggan';
$active_tab = 'riwayat';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$filter = $_GET['filter'] ?? 'Semua';
$customerId = (int)$_SESSION['customer_id'];
$current_member = one(
    'SELECT id_pelanggan AS id, nama_pelanggan AS nama, no_hp AS wa, kode_kartu
     FROM pelanggan
     WHERE id_pelanggan = ?',
    [$customerId]
);
if (!$current_member) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$filterSql = $filter === '7 Hari'
    ? ' AND t.tanggal_transaksi >= CURRENT_DATE - INTERVAL \'7 days\''
    : '';
$customer_txs = rows(
    'SELECT t.id_transaksi,
            COALESCE(t.no_invoice, \'INV-\' || t.id_transaksi::text) AS id,
            t.tanggal_transaksi,
            t.total_bayar,
            t.jenis_pembayaran,
            t.status_pembayaran,
            COALESCE(STRING_AGG(l.nama_layanan, \' + \' ORDER BY d.id_detail), \'Transaksi laundry\') AS layanan,
            COALESCE(SUM(d.berat), 0) AS total_berat,
            COALESCE(SUM(d.jumlah), 0) AS total_jumlah,
            COALESCE(SUM(d.jumlah_koin), 0) AS total_koin,
            COALESCE(SUM(CASE
                WHEN LOWER(l.nama_layanan) = \'cuci\' THEN d.jumlah_koin
                WHEN LOWER(l.nama_layanan) LIKE \'%cuci kering%\' AND LOWER(l.nama_layanan) NOT LIKE \'%drop off%\' THEN d.jumlah
                ELSE 0
            END), 0) AS koin_cuci,
            COALESCE(SUM(CASE
                WHEN LOWER(l.nama_layanan) = \'kering\' THEN d.jumlah_koin
                WHEN LOWER(l.nama_layanan) LIKE \'%cuci kering%\' AND LOWER(l.nama_layanan) NOT LIKE \'%drop off%\' THEN d.jumlah
                ELSE 0
            END), 0) AS koin_kering
     FROM transaksi t
     LEFT JOIN detail_transaksi d ON d.id_transaksi = t.id_transaksi
     LEFT JOIN layanan l ON l.id_layanan = d.id_layanan
     WHERE t.id_pelanggan = ?' . $filterSql . '
     GROUP BY t.id_transaksi
     ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC',
    [$customerId]
);

$total_tx_count = count($customer_txs);
$total_wash_count = 0;
$total_dry_count = 0;
foreach ($customer_txs as &$transaction) {
    $total_wash_count += (int)$transaction['koin_cuci'];
    $total_dry_count += (int)$transaction['koin_kering'];
    $transaction['status'] = ucwords(str_replace('_', ' ', $transaction['status_pembayaran']));
    $transaction['kuantitas'] = (float)$transaction['total_berat'] > 0
        ? rtrim(rtrim(number_format((float)$transaction['total_berat'], 2, '.', ''), '0'), '.') . ' kg'
        : (int)$transaction['total_jumlah'] . ' pcs';
    $transaction['metode'] = strtoupper($transaction['jenis_pembayaran']);
    $transaction['tanggal'] = date('d M Y', strtotime($transaction['tanggal_transaksi']));
    $transaction['waktu'] = date('H:i', strtotime($transaction['tanggal_transaksi']));
    $transaction['total_bayar'] = (int)$transaction['total_bayar'];
}
unset($transaction);
$disp_tx = $total_tx_count . 'x';
$disp_wash = $total_wash_count . ' pcs';
$disp_dry = $total_dry_count . ' pcs';
?>

<script>
    // Pass current member code to client JS for dynamic QR generation
    window.__CURRENT_MEMBER_CODE = "<?= htmlspecialchars($current_member['kode_kartu']) ?>";
    window.__CURRENT_MEMBER_NAME = "<?= htmlspecialchars($current_member['nama']) ?>";
</script>

<!-- Banner Kartu Member Saya (Dinamis Sesuai Akun Pelanggan yang Login) -->
<div style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); border-radius: var(--radius-md); padding: 16px; color: #ffffff; display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; box-shadow: 0 4px 14px rgba(30, 58, 138, 0.25);">
    <div>
        <span style="font-size: 11px; font-weight: 700; opacity: 0.85;">ID MEMBER: <?= htmlspecialchars($current_member['kode_kartu']) ?></span>
        <h3 style="font-size: 16px; font-weight: 900; margin-top: 2px;">Kartu Member Saya</h3>
        <p style="font-size: 11px; opacity: 0.85; margin-top: 2px;">Tunjukkan QR ke kasir saat memesan koin</p>
    </div>
    <button type="button" class="btn" style="width: auto; background: #f59e0b; color: #1e3a8a; padding: 8px 14px; font-size: 12px; font-weight: 800; border-radius: 10px;" onclick="handleOpenCustomerQR('<?= htmlspecialchars($current_member['kode_kartu']) ?>')">
        Lihat QR →
    </button>
</div>

<!-- Metrik Pribadi Pelanggan Dinamis -->
<div class="metrics-row" style="margin-bottom: 14px;">
    <div class="metric-box blue">
        <div class="label">Total Transaksi</div>
        <div class="value"><?= $disp_tx ?></div>
        <div class="sub">Cuci &amp; Kering</div>
    </div>
    <div class="metric-box orange">
        <div class="label">Coin Cuci</div>
        <div class="value"><?= $disp_wash ?></div>
        <div class="sub">Telah digunakan</div>
    </div>
    <div class="metric-box amber">
        <div class="label">Coin Kering</div>
        <div class="value"><?= $disp_dry ?></div>
        <div class="sub">Telah digunakan</div>
    </div>
</div>

<!-- Riwayat Cucian & Filter (C1, C1b) -->
<div class="card">
    <div class="card-title">
        <span>Riwayat Cucian: <?= htmlspecialchars($current_member['nama']) ?></span>
        <span style="font-size: 11px; color: var(--text-muted);">Real-time tracking</span>
    </div>

    <!-- Filter chips -->
    <div class="filter-scroll" style="margin-bottom: 12px;">
        <a href="index.php?filter=Semua" class="chip chip-orange <?= $filter === 'Semua' ? 'active' : '' ?>">Semua</a>
        <a href="index.php?filter=7+Hari" class="chip chip-orange <?= $filter === '7 Hari' ? 'active' : '' ?>">7 Hari</a>
    </div>

    <?php if (empty($customer_txs)): ?>
        <!-- Empty State C1b -->
        <div style="text-align: center; padding: 30px 10px; color: var(--text-muted); font-size: 13px;">
            <div style="margin-bottom: 8px;"><?= get_icon('master', 32, '#94a3b8') ?></div>
            <strong style="color: var(--text-main);">Belum ada transaksi pada periode ini.</strong>
            <p style="font-size: 11px; margin-top: 4px;">Pilih filter "Semua" atau pesan koin cuci di kasir.</p>
        </div>
    <?php else: ?>
        <?php foreach ($customer_txs as $ctx): ?>
        <div class="trx-item" style="cursor: default;">
            <div class="trx-top">
                <span class="trx-id"><?= htmlspecialchars($ctx['id']) ?></span>
                <span class="badge <?= $ctx['status_pembayaran'] === 'lunas' ? 'badge-lunas' : 'badge-kurang' ?>">
                    <?= htmlspecialchars($ctx['status']) ?>
                </span>
            </div>
            <div class="trx-name" style="font-size: 13px; margin: 2px 0;"><?= htmlspecialchars($ctx['layanan']) ?></div>
            <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 6px;">
                <?= htmlspecialchars($ctx['kuantitas']) ?> • <?= htmlspecialchars($ctx['metode']) ?>
            </div>
            <div class="trx-bottom" style="padding-top: 6px;">
                <span style="font-size: 11px; color: var(--text-muted);"><?= $ctx['tanggal'] ?> • <?= $ctx['waktu'] ?></span>
                <strong style="font-size: 14px; color: var(--primary-dark);"><?= format_rupiah($ctx['total_bayar']) ?></strong>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal C2: Kartu Member Digital Pelanggan (Dinamis Sesuai Nama & QR Akun yang Login) -->
<div class="sheet-overlay" id="modalCustomerQrCard">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Kartu Pelanggan</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalCustomerQrCard')">&times;</button>
        </div>

        <div class="member-card-blue">
            <div class="store-name">KINO COIN LAUNDRY</div>
            <div class="cust-name" id="custModalName"><?= htmlspecialchars($current_member['nama']) ?></div>
            
            <div class="qr-box-white" id="customerQrContainer" style="display:inline-flex; align-items:center; justify-content:center; min-width:130px; min-height:130px;"></div>

            <div class="cust-id" id="custModalId"><?= htmlspecialchars($current_member['kode_kartu']) ?></div>
            <div class="tip">Tingkatkan kecerahan layar saat kasir memindai kode</div>
        </div>

        <button type="button" class="btn btn-outline" onclick="closeModal('modalCustomerQrCard')">
            Tutup
        </button>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
