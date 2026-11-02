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
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        $error = 'Sesi formulir tidak valid. Muat ulang halaman.';
    } else {
        $action = (string) ($_POST['service_action'] ?? 'create');
        try {
            if ($action === 'toggle') {
                $id = (int) ($_POST['id_layanan'] ?? 0);
                $active = (($_POST['aktif'] ?? '') === '1');
                web_query('UPDATE layanan SET aktif = ? WHERE id_layanan = ?', [$active, $id]);
                web_flash('Status layanan diperbarui.');
                header('Location: layanan.php');
                exit;
            } elseif ($action === 'delete') {
                $id = (int) ($_POST['id_layanan'] ?? 0);
                $used = web_one('SELECT 1 FROM detail_transaksi WHERE id_layanan = ? LIMIT 1', [$id]);
                if ($used) {
                    $error = 'Layanan sudah digunakan dalam transaksi dan tidak dapat dihapus.';
                } else {
                    web_query('DELETE FROM layanan WHERE id_layanan = ?', [$id]);
                    $msg = 'Layanan berhasil dihapus.';
                    require __DIR__ . '/../includes/data.php';
                }
            } else {
                $name = trim((string) ($_POST['nama_layanan'] ?? ''));
                $price = filter_var($_POST['tarif'] ?? null, FILTER_VALIDATE_INT);
                $duration = trim((string) ($_POST['durasi'] ?? ''));
                if ($name === '' || strlen($name) > 50 || $price === false || $price < 1 || $duration === '' || strlen($duration) > 60) {
                    $error = 'Nama, tarif positif, dan estimasi durasi wajib diisi dengan benar.';
                } elseif ($action === 'update') {
                    web_query('UPDATE layanan SET nama_layanan = ?, harga = ?, durasi = ? WHERE id_layanan = ?', [$name, $price, $duration, (int) $_POST['id_layanan']]);
                    $msg = 'Layanan berhasil diperbarui.';
                    require __DIR__ . '/../includes/data.php';
                } else {
                    web_query('INSERT INTO layanan(nama_layanan,harga,durasi,aktif) VALUES(?,?,?,TRUE)', [$name, $price, $duration]);
                    $msg = 'Layanan baru berhasil ditambahkan.';
                    require __DIR__ . '/../includes/data.php';
                }
            }
        } catch (Throwable $e) {
            error_log('Service management failed: ' . $e->getMessage());
            $error = 'Perubahan layanan gagal disimpan.';
        }
    }
}
$flash = web_take_flash();
if ($flash)
    $msg = $flash['message'];

$icons_map = [
    'Cuci Kering' => '🧺',
    'Cuci Setrika' => '👕',
    'Setrika Saja' => '♨️',
    'Bed Cover' => '🛏️'
];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <span style="font-size: 14px; color: var(--text-muted); font-weight: 600;">
        <?= count($services) ?> Paket Layanan
    </span>
    <button type="button" class="btn btn-primary" style="width: auto; padding: 10px 20px; font-size: 13px;"
        onclick="openModal('addServiceModal')">
        + Tambah Layanan
    </button>
</div>

<?php if ($msg): ?>
    <div class="info-banner" style="background:#dcfce7; border-color:#86efac; color:#15803d;">
        <span>✅</span>
        <span><?= htmlspecialchars($msg) ?></span>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="info-banner warning"><span>⚠️</span><span><?= web_h($error) ?></span></div>
<?php endif; ?>

<!-- Daftar Kartu Layanan (Grid 2 kolom di Desktop) -->
<div class="responsive-grid-cards">
    <?php foreach ($services as $svc):
        $icon = $icons_map[$svc['nama']] ?? '✨';
        ?>
        <div class="service-card" style="margin-bottom: 0;">
            <div class="service-left">
                <div class="service-icon-box">
                    <?= $icon ?>
                </div>
                <div>
                    <div class="service-name"><?= htmlspecialchars($svc['nama']) ?></div>
                    <div class="service-sub"><?= htmlspecialchars($svc['durasi']) ?> • <span
                            style="color: var(--success); font-weight: bold;"><?= htmlspecialchars($svc['status']) ?></span>
                    </div>
                </div>
            </div>
            <div class="service-price">
                <?= format_rupiah($svc['tarif']) ?><span
                    style="font-size: 12px; font-weight: normal; color: var(--text-muted);">/kg</span>
            </div>
        </div>
        <div class="card" style="margin-top:8px;">
            <form action="layanan.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= web_h(web_csrf_token()) ?>">
                <input type="hidden" name="service_action" value="update">
                <input type="hidden" name="id_layanan" value="<?= (int) $svc['id'] ?>">
                <div class="form-group"><label class="form-label">Ubah nama</label><input class="form-control"
                        name="nama_layanan" maxlength="50" value="<?= web_h($svc['nama']) ?>" required></div>
                <div class="form-group"><label class="form-label">Tarif/kg (Rp)</label><input class="form-control"
                        type="number" min="1" name="tarif" value="<?= (int) $svc['tarif'] ?>" required></div>
                <div class="form-group"><label class="form-label">Estimasi durasi</label><input class="form-control"
                        name="durasi" maxlength="60" value="<?= web_h($svc['durasi']) ?>" required></div>
                <button type="submit" class="btn btn-outline">Simpan Perubahan</button>
            </form>
            <form action="layanan.php" method="POST" style="margin-top:8px;">
                <input type="hidden" name="csrf_token" value="<?= web_h(web_csrf_token()) ?>">
                <input type="hidden" name="service_action" value="toggle">
                <input type="hidden" name="id_layanan" value="<?= (int) $svc['id'] ?>">
                <input type="hidden" name="aktif" value="<?= $svc['status'] === 'Aktif' ? '0' : '1' ?>">
                <button type="submit" class="btn btn-outline"><?= $svc['status'] === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                    Layanan</button>
            </form>
            <form action="layanan.php" method="POST" onsubmit="return confirm('Hapus layanan ini?')">
                <input type="hidden" name="csrf_token" value="<?= web_h(web_csrf_token()) ?>">
                <input type="hidden" name="service_action" value="delete">
                <input type="hidden" name="id_layanan" value="<?= (int) $svc['id'] ?>">
                <button type="submit" class="btn btn-outline" style="margin-top:6px;">Hapus Layanan</button>
            </form>
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
            <input type="hidden" name="csrf_token" value="<?= web_h(web_csrf_token()) ?>">
            <input type="hidden" name="service_action" value="create">
            <div class="form-group">
                <label class="form-label">Nama Layanan</label>
                <input type="text" name="nama_layanan" class="form-control" placeholder="Contoh: Cuci Kilat 3 Jam"
                    required>
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