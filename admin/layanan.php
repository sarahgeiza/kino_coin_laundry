<?php
$page_title = 'Master Layanan - Kino Coin Laundry';
$header_role = 'Mode Pemilik';
$header_user = 'Pemilik';
$header_avatar = '👑';
$header_title = 'Master Layanan';
$header_subtitle = 'Kelola paket tarif dan kapasitas laundry';
$current_role = 'admin';
$active_tab = 'layanan';

require_once __DIR__ . '/../includes/header.php';

$msg = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $nama = trim($_POST['nama_layanan'] ?? '');
    $tarif = intval($_POST['tarif'] ?? 0);
    $durasi = trim($_POST['durasi'] ?? '1 hari');

    if (!empty($nama) && $tarif > 0) {
        $_SESSION['services'][] = [
            'id' => count($_SESSION['services']) + 1,
            'nama' => $nama,
            'tarif' => $tarif,
            'durasi' => $durasi,
            'status' => 'Aktif',
            'icon' => 'baju'
        ];
        $msg = 'Layanan baru berhasil ditambahkan!';
    }
}

$icons_map = [
    'Cuci Kering' => '🧺',
    'Cuci Setrika' => '👕',
    'Setrika Saja' => '♨️',
    'Bed Cover' => '🛏️'
];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <span style="font-size: 14px; color: var(--text-muted); font-weight: 600;">
        <?= count($_SESSION['services']) ?> Paket Layanan
    </span>
    <button type="button" class="btn btn-primary" style="width: auto; padding: 10px 20px; font-size: 13px;" onclick="openModal('addServiceModal')">
        + Tambah Layanan
    </button>
</div>

<?php if ($msg): ?>
    <div class="info-banner" style="background:#dcfce7; border-color:#86efac; color:#15803d;">
        <span>✅</span>
        <span><?= htmlspecialchars($msg) ?></span>
    </div>
<?php endif; ?>

<!-- Daftar Kartu Layanan (Grid 2 kolom di Desktop) -->
<div class="responsive-grid-cards">
    <?php foreach ($_SESSION['services'] as $svc): 
        $icon = $icons_map[$svc['nama']] ?? '✨';
    ?>
    <div class="service-card" style="margin-bottom: 0;">
        <div class="service-left">
            <div class="service-icon-box">
                <?= $icon ?>
            </div>
            <div>
                <div class="service-name"><?= htmlspecialchars($svc['nama']) ?></div>
                <div class="service-sub"><?= htmlspecialchars($svc['durasi']) ?> • <span style="color: var(--success); font-weight: bold;"><?= htmlspecialchars($svc['status']) ?></span></div>
            </div>
        </div>
        <div class="service-price">
            <?= format_rupiah($svc['tarif']) ?><span style="font-size: 12px; font-weight: normal; color: var(--text-muted);">/kg</span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Tambah Layanan -->
<div class="modal-overlay" id="addServiceModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Paket Layanan</h3>
            <button class="modal-close" data-close-modal>&times;</button>
        </div>
        <form action="layanan.php" method="POST">
            <div class="form-group">
                <label class="form-label">Nama Layanan</label>
                <input type="text" name="nama_layanan" class="form-control" placeholder="Contoh: Cuci Kilat 3 Jam" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tarif per Kg (Rp)</label>
                <input type="number" name="tarif" class="form-control" placeholder="10000" required>
            </div>
            <div class="form-group">
                <label class="form-label">Estimasi Durasi</label>
                <input type="text" name="durasi" class="form-control" placeholder="1 hari" required>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 14px;">Simpan Layanan</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
