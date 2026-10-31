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

<div class="grid-2-desktop">

    <!-- Kolom Kiri: Riwayat Cucian Pelanggan -->
    <div>
        <div class="card">
            <div class="card-title">
                <span>Riwayat Cucian Anda</span>
                <span style="font-size: 11px; color: var(--text-muted);">Real-time tracking</span>
            </div>

            <!-- Filter chips -->
            <div class="filter-scroll" style="margin-bottom: 14px;">
                <a href="index.php?filter=Semua" class="chip chip-orange <?= $filter === 'Semua' ? 'active' : '' ?>">Semua</a>
                <a href="index.php?filter=7+Hari" class="chip chip-orange <?= $filter === '7 Hari' ? 'active' : '' ?>">7 Hari Terakhir</a>
            </div>

            <?php if (empty($customer_txs)): ?>
                <!-- Empty State C1b -->
                <div style="text-align: center; padding: 40px 10px; color: var(--text-muted); font-size: 13px;">
                    <div style="font-size: 32px; margin-bottom: 8px;">🧺</div>
                    <strong style="color: var(--text-main); font-size: 14px;">Belum ada transaksi pada periode ini.</strong>
                    <p style="font-size: 12px; margin-top: 4px;">Pilih filter "Semua" untuk melihat riwayat lengkap Anda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($customer_txs as $ctx): ?>
                <div class="trx-item" style="cursor: default;">
                    <div class="trx-top">
                        <span class="trx-id"><?= htmlspecialchars($ctx['id']) ?></span>
                        <span class="badge badge-lunas"><?= $ctx['status'] ?></span>
                    </div>
                    <div class="trx-name" style="font-size: 14px; margin: 2px 0;"><?= htmlspecialchars($ctx['layanan']) ?></div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">
                        <?= $ctx['kuantitas'] ?> • <?= $ctx['metode'] === 'Token' ? '🪙 Pembayaran Token Koin' : '💵 Tunai' ?>
                    </div>
                    <div class="trx-bottom" style="padding-top: 8px;">
                        <span style="font-size: 11px; color: var(--text-muted);"><?= $ctx['tanggal'] ?> • <?= $ctx['waktu'] ?></span>
                        <strong style="font-size: 15px; color: var(--primary-dark);"><?= format_rupiah($ctx['total_bayar']) ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kolom Kanan: Kartu Member Digital & Statistik Token -->
    <div>
        <!-- Banner Kartu Member Saya (C1) -->
        <div style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); border-radius: var(--radius-lg); padding: 20px; color: #ffffff; margin-bottom: 16px; box-shadow: 0 6px 18px rgba(30, 58, 138, 0.25);">
            <span style="font-size: 11px; font-weight: 800; opacity: 0.85; letter-spacing: 0.5px;">KARTU MEMBER DIGITAL</span>
            <h3 style="font-size: 18px; font-weight: 900; margin: 4px 0 2px 0;">Budi Santoso</h3>
            <p style="font-size: 12px; opacity: 0.85; margin-bottom: 14px;">ID Member: <strong>KC-P-0001</strong></p>
            <button type="button" class="btn" style="background: #f59e0b; color: #1e3a8a; padding: 10px 16px; font-size: 13px; font-weight: 800; border-radius: 10px;" onclick="handleOpenCustomerQR()">
                Tampilkan Kartu QR Member →
            </button>
        </div>

        <!-- Metrik Pribadi Pelanggan -->
        <div class="card" style="padding: 16px;">
            <div class="card-title">Statistik Penggunaan Koin</div>
            <div class="metrics-row" style="margin-bottom: 0;">
                <div class="metric-box blue">
                    <div class="label">Total Transaksi</div>
                    <div class="value"><?= $filter === '7 Hari' ? '0' : '4x' ?></div>
                    <div class="sub">Cuci &amp; Kering</div>
                </div>
                <div class="metric-box orange">
                    <div class="label">Coin Cuci</div>
                    <div class="value"><?= $filter === '7 Hari' ? '0' : '3 pcs' ?></div>
                    <div class="sub">Telah dipakai</div>
                </div>
                <div class="metric-box amber">
                    <div class="label">Coin Kering</div>
                    <div class="value"><?= $filter === '7 Hari' ? '0' : '1 pc' ?></div>
                    <div class="sub">Telah dipakai</div>
                </div>
            </div>
        </div>
    </div>

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
