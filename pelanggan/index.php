<?php
$page_title = 'Beranda Pelanggan - Kino Coin Laundry';
$app_role = 'pelanggan';
$active_tab = 'riwayat';

require_once __DIR__ . '/../includes/header.php';

$filter = $_GET['filter'] ?? 'Semua';

// Customer transaction history
$customer_txs = array_filter($_SESSION['transactions'], function($t) {
    return stripos($t['pelanggan'], 'Budi') !== false;
});

if ($filter === '7 Hari') {
    $customer_txs = []; // Empty state C1b
}
?>

<!-- Banner Kartu Member Saya (C1) -->
<div style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); border-radius: var(--radius-md); padding: 16px; color: #ffffff; display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; box-shadow: 0 4px 14px rgba(30, 58, 138, 0.25);">
    <div>
        <span style="font-size: 11px; font-weight: 700; opacity: 0.85;">ID MEMBER: KC-P-0001</span>
        <h3 style="font-size: 16px; font-weight: 900; margin-top: 2px;">Kartu Member Saya</h3>
        <p style="font-size: 11px; opacity: 0.85; margin-top: 2px;">Tunjukkan QR ke kasir saat memesan koin</p>
    </div>
    <button type="button" class="btn" style="width: auto; background: #f59e0b; color: #1e3a8a; padding: 8px 14px; font-size: 12px; font-weight: 800; border-radius: 10px;" onclick="handleOpenCustomerQR()">
        Lihat QR →
    </button>
</div>

<!-- Metrik Pribadi Pelanggan -->
<div class="metrics-row" style="margin-bottom: 14px;">
    <div class="metric-box blue">
        <div class="label">Total Transaksi</div>
        <div class="value"><?= $filter === '7 Hari' ? '0' : '4x' ?></div>
        <div class="sub">Cuci &amp; Kering</div>
    </div>
    <div class="metric-box orange">
        <div class="label">Coin Cuci</div>
        <div class="value"><?= $filter === '7 Hari' ? '0' : '3 pcs' ?></div>
        <div class="sub">Telah digunakan</div>
    </div>
    <div class="metric-box amber">
        <div class="label">Coin Kering</div>
        <div class="value"><?= $filter === '7 Hari' ? '0' : '1 pc' ?></div>
        <div class="sub">Telah digunakan</div>
    </div>
</div>

<!-- Riwayat Cucian & Filter (C1, C1b) -->
<div class="card">
    <div class="card-title">
        <span>Riwayat Cucian</span>
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
            <div style="font-size: 28px; margin-bottom: 6px;">🧺</div>
            <strong style="color: var(--text-main);">Belum ada transaksi pada periode ini.</strong>
            <p style="font-size: 11px; margin-top: 4px;">Pilih filter "Semua" untuk melihat riwayat lengkap Anda.</p>
        </div>
    <?php else: ?>
        <?php foreach ($customer_txs as $ctx): ?>
        <div class="trx-item" style="cursor: default;">
            <div class="trx-top">
                <span class="trx-id"><?= htmlspecialchars($ctx['id']) ?></span>
                <span class="badge badge-lunas"><?= $ctx['status'] ?></span>
            </div>
            <div class="trx-name" style="font-size: 13px; margin: 2px 0;"><?= htmlspecialchars($ctx['layanan']) ?></div>
            <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 6px;">
                <?= $ctx['kuantitas'] ?> • <?= $ctx['metode'] === 'Token' ? '🪙 Pembayaran Token Koin' : '💵 Tunai' ?>
            </div>
            <div class="trx-bottom" style="padding-top: 6px;">
                <span style="font-size: 11px; color: var(--text-muted);"><?= $ctx['tanggal'] ?> • <?= $ctx['waktu'] ?></span>
                <strong style="font-size: 14px; color: var(--primary-dark);"><?= format_rupiah($ctx['total_bayar']) ?></strong>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal C2: Kartu Member Digital Pelanggan -->
<div class="sheet-overlay" id="modalCustomerQrCard">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Kartu Pelanggan</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalCustomerQrCard')">&times;</button>
        </div>

        <div class="member-card-blue">
            <div class="store-name">KINO COIN LAUNDRY</div>
            <div class="cust-name">Budi Santoso</div>
            
            <div class="qr-box-white">
                <svg width="130" height="130" viewBox="0 0 120 120">
                    <rect width="120" height="120" fill="white"/>
                    <rect x="10" y="10" width="30" height="30" fill="#1e3a8a"/>
                    <rect x="16" y="16" width="18" height="18" fill="white"/>
                    <rect x="20" y="20" width="10" height="10" fill="#1e3a8a"/>
                    
                    <rect x="80" y="10" width="30" height="30" fill="#1e3a8a"/>
                    <rect x="86" y="16" width="18" height="18" fill="white"/>
                    <rect x="90" y="20" width="10" height="10" fill="#1e3a8a"/>
                    
                    <rect x="10" y="80" width="30" height="30" fill="#1e3a8a"/>
                    <rect x="16" y="86" width="18" height="18" fill="white"/>
                    <rect x="20" y="90" width="10" height="10" fill="#1e3a8a"/>

                    <!-- Data dots -->
                    <rect x="50" y="20" width="8" height="8" fill="#1e3a8a"/>
                    <rect x="62" y="32" width="8" height="8" fill="#1e3a8a"/>
                    <rect x="50" y="50" width="12" height="12" fill="#1e3a8a"/>
                    <rect x="70" y="60" width="10" height="10" fill="#1e3a8a"/>
                    <rect x="85" y="85" width="15" height="15" fill="#1e3a8a"/>
                </svg>
            </div>

            <div class="cust-id">KC-P-0001</div>
            <div class="tip">Tingkatkan kecerahan layar saat kasir memindai kode</div>
        </div>

        <button type="button" class="btn btn-outline" onclick="closeModal('modalCustomerQrCard')">
            Tutup
        </button>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
