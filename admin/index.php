<?php
$page_title = 'Dashboard Pemilik - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';

// Hitung metrik omset & koin HARI INI (bukan seluruh riwayat)
$current_tx_list = $_SESSION['transactions'] ?? [];
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$owner_omset = 0;
$owner_wash = 0;
$owner_dry = 0;
$omset_kemarin = 0;

foreach ($current_tx_list as $t) {
    $tgl = (string) ($t['filter_date'] ?? '');
    $nominal = (int) ($t['total_bayar'] ?? $t['total_biaya'] ?? 0);

    if ($tgl === $today) {
        $owner_omset += $nominal;
        $owner_wash += (int) ($t['koin_cuci'] ?? 0);
        $owner_dry += (int) ($t['koin_kering'] ?? 0);
    } elseif ($tgl === $yesterday) {
        $omset_kemarin += $nominal;
    }
}

if ($omset_kemarin > 0) {
    $delta_persen = (int) round((($owner_omset - $omset_kemarin) / $omset_kemarin) * 100);
    $delta_label = ($delta_persen >= 0 ? '↑ ' : '↓ ') . abs($delta_persen) . '% vs kemarin';
    $delta_warna = $delta_persen >= 0 ? '#86efac' : '#fca5a5';
} else {
    $delta_label = 'Belum ada pembanding kemarin';
    $delta_warna = 'rgba(255,255,255,0.75)';
}
?>

<!-- Section Monitoring Bisnis (Persis Figma P1) -->
<div class="card" style="padding: 16px; margin-bottom: 14px;">
    <div class="card-title" style="margin-bottom: 10px;">
        <span>Monitoring Bisnis</span>
        <span style="font-size: 11px; color: var(--text-muted);">Hari Ini</span>
    </div>
    <div class="metrics-row" style="margin-bottom: 0;">
        <div class="metric-box blue">
            <div class="label">Omset Hari Ini</div>
            <div class="value"><?= format_rupiah($owner_omset) ?></div>
            <div class="sub" style="color: <?= $delta_warna ?>; font-weight: 700;"><?= htmlspecialchars($delta_label) ?></div>
        </div>
        <div class="metric-box orange">
            <div class="label">Coin Cuci</div>
            <div class="value"><?= $owner_wash ?> pcs</div>
            <div class="sub">= <?= format_rupiah($owner_wash * 7000) ?></div>
        </div>
        <div class="metric-box amber">
            <div class="label">Coin Kering</div>
            <div class="value"><?= $owner_dry ?> pcs</div>
            <div class="sub">= <?= format_rupiah($owner_dry * 10000) ?></div>
        </div>
    </div>
</div>

<!-- Rekapitulasi Shift Terbaru (Persis Figma P1) -->
<div class="card">
    <div class="card-title">
        <span>Rekapitulasi Shift</span>
        <a href="laporan.php" style="font-size: 12px; color: var(--primary-light); text-decoration: none; font-weight: 700;">Lihat Laporan →</a>
    </div>

    <?php foreach (array_reverse($_SESSION['shifts_recap'] ?? []) as $sr): ?>
    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px; margin-bottom: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
            <strong style="font-size: 13px; color: var(--text-main);">
                <?= strtoupper($sr['tanggal']) ?> • <?= strtoupper($sr['shift']) ?>
            </strong>
            <span class="badge <?= $sr['status'] === 'Valid' ? 'badge-lunas' : 'badge-kurang' ?>">
                <?= htmlspecialchars($sr['status']) ?>
            </span>
        </div>
        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 6px;">
            Kasir: <strong><?= htmlspecialchars($sr['kasir']) ?></strong> • <?= $sr['trx_count'] ?> trx • <?= $sr['total_kg'] ?> kg
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border); padding-top: 6px; font-size: 12px;">
            <span style="color: var(--text-muted);">Cuci: <?= $sr['koin_cuci'] ?> pcs | Kering: <?= $sr['koin_kering'] ?> pcs</span>
            <strong style="font-size: 14px; color: var(--primary-dark);"><?= format_rupiah($sr['pendapatan']) ?></strong>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
