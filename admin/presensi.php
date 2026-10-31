<?php
$page_title = 'Presensi Kasir - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'presensi';

require_once __DIR__ . '/../includes/header.php';

$filter = $_GET['filter'] ?? 'Hari Ini';
$logs = $_SESSION['presensi'];

if ($filter === 'Bulan Lalu') {
    $logs = []; // Test empty state P6b
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <h2 style="font-size: 16px; font-weight: 800;">Presensi Kasir</h2>
        <span style="font-size: 11px; color: var(--text-muted);">Riwayat log jam kerja staf</span>
    </div>
    <button type="button" class="btn btn-outline" style="width: auto; padding: 6px 12px; font-size: 12px;" onclick="showToast('Log presensi berhasil diexport', 'success')">
        📥 Export
    </button>
</div>

<!-- Filter Periode (P6, P6b) -->
<div class="filter-scroll">
    <a href="presensi.php?filter=Hari+Ini" class="chip <?= $filter === 'Hari Ini' ? 'active' : '' ?>">Hari Ini</a>
    <a href="presensi.php?filter=7+Hari" class="chip <?= $filter === '7 Hari' ? 'active' : '' ?>">7 Hari</a>
    <a href="presensi.php?filter=Bulan+Ini" class="chip <?= $filter === 'Bulan Ini' ? 'active' : '' ?>">Bulan Ini</a>
    <a href="presensi.php?filter=Bulan+Lalu" class="chip <?= $filter === 'Bulan Lalu' ? 'active' : '' ?>">Bulan Lalu</a>
</div>

<?php if (empty($logs)): ?>
    <!-- Empty State (P6b) -->
    <div class="card" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
        <div style="font-size: 32px; margin-bottom: 8px;">🕒</div>
        <strong style="font-size: 14px; color: var(--text-main);">Tidak ada data presensi di periode ini</strong>
        <p style="font-size: 12px; margin-top: 4px;">Pilih filter lain untuk melihat log absensi kasir.</p>
    </div>
<?php else: ?>
    <?php foreach ($logs as $p): ?>
    <div class="card" style="margin-bottom: 10px; padding: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <div>
                <strong style="font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($p['kasir']) ?></strong>
                <span style="font-size: 11px; color: var(--text-muted); margin-left: 6px;">— <?= htmlspecialchars($p['shift']) ?></span>
            </div>
            <span class="badge <?= $p['status'] === 'Berjalan' ? 'badge-proses' : 'badge-lunas' ?>">
                <?= $p['status'] ?>
            </span>
        </div>
        <div style="font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between; border-top: 1px dashed var(--border); padding-top: 8px; margin-top: 4px;">
            <span>Tanggal: <strong><?= $p['tanggal'] ?></strong></span>
            <span>Masuk: <strong><?= $p['jam_masuk'] ?></strong> | Keluar: <strong><?= $p['jam_keluar'] ?? '—' ?></strong></span>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
