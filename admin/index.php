<?php
$page_title = 'Dashboard Pemilik - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';

// Calculate extra metrics from newly added transactions
$initial_count = 4;
$current_tx_list = $_SESSION['transactions'] ?? [];
$current_count = count($current_tx_list);

$extra_income = 0;
$extra_wash = 0;
$extra_dry = 0;

if ($current_count > $initial_count) {
    for ($i = $initial_count; $i < $current_count; $i++) {
        $t = $current_tx_list[$i];
        $extra_income += ($t['total_bayar'] ?? $t['total_biaya'] ?? 0);
        $extra_wash += ($t['koin_cuci'] ?? 0);
        $extra_dry += ($t['koin_kering'] ?? 0);
    }
}

$owner_omset = 1250000 + $extra_income;
$owner_wash = 124 + $extra_wash;
$owner_dry = 130 + $extra_dry;
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
            <div class="sub" style="color: #86efac; font-weight: 700;">↑ 8% vs kemarin</div>
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

    <?php foreach ($_SESSION['shifts_recap'] as $sr): ?>
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
