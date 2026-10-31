<?php
$page_title = 'Beranda Pelanggan - Kino Coin Laundry';
$app_role = 'pelanggan';
$active_tab = 'riwayat';

require_once __DIR__ . '/../includes/header.php';

$filter = $_GET['filter'] ?? 'Semua';

// 1. Resolve logged-in customer data dynamically from session
$login_user_name = $_SESSION['username'] ?? 'Budi';

$current_member = null;
foreach ($_SESSION['members'] as $m) {
    if (strcasecmp($m['nama'], $login_user_name) === 0) {
        $current_member = $m;
        break;
    }
}

// Fallback auto-registration if newly logged in
if (!$current_member) {
    $new_id = count($_SESSION['members']) + 1;
    $new_code = 'KC-P-' . str_pad($new_id, 4, '0', STR_PAD_LEFT);
    $current_member = [
        'id' => $new_id,
        'nama' => $login_user_name,
        'wa' => '0812' . rand(10000000, 99999999),
        'kode_kartu' => $new_code,
        'is_member' => true,
        'tx_count' => 0,
        'coin_cuci' => 0,
        'coin_kering' => 0,
        'password' => 'password123'
    ];
    $_SESSION['members'][] = $current_member;
}

// 2. Filter customer transactions specifically for this user
$customer_txs = array_filter($_SESSION['transactions'], function($t) use ($current_member) {
    return (stripos($t['pelanggan'], $current_member['nama']) !== false) ||
           (isset($t['kode_kartu']) && $t['kode_kartu'] === $current_member['kode_kartu']);
});

$total_tx_count = count($customer_txs);
$total_wash_count = 0;
$total_dry_count = 0;

foreach ($customer_txs as $t) {
    $total_wash_count += ($t['koin_cuci'] ?? 0);
    $total_dry_count += ($t['koin_kering'] ?? 0);
}

// If it is Budi and transaction array is empty in filter, use default member stats
if ($total_tx_count === 0 && !empty($current_member['tx_count'])) {
    $total_tx_count = $current_member['tx_count'];
    $total_wash_count = $current_member['coin_cuci'];
    $total_dry_count = $current_member['coin_kering'];
}

if ($filter === '7 Hari') {
    $customer_txs = []; // Empty state C1b
    $disp_tx = 0;
    $disp_wash = '0 pcs';
    $disp_dry = '0 pc';
} else {
    $disp_tx = $total_tx_count . 'x';
    $disp_wash = $total_wash_count . ' pcs';
    $disp_dry = $total_dry_count . ' pc';
}
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
                <span class="badge badge-lunas"><?= $ctx['status'] ?></span>
            </div>
            <div class="trx-name" style="font-size: 13px; margin: 2px 0;"><?= htmlspecialchars($ctx['layanan']) ?></div>
            <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 6px;">
                <?= $ctx['kuantitas'] ?> • <?= $ctx['metode'] === 'Token' ? 'Pembayaran Token' : 'Tunai' ?>
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
