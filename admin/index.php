<?php
$page_title = 'Dashboard Pemilik - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Section Monitoring Bisnis -->
<div class="card" style="padding: 14px;">
    <div class="card-title" style="margin-bottom: 10px;">
        <span>Monitoring Bisnis</span>
        <span style="font-size: 11px; color: var(--text-muted);">Hari Ini</span>
    </div>
    <div class="metrics-row" style="margin-bottom: 0;">
        <div class="metric-box blue">
            <div class="label">Omset Hari Ini</div>
            <div class="value">Rp 1.25jt</div>
            <div class="sub" style="color: #86efac; font-weight: 700;">↑ 8% vs kemarin</div>
        </div>
        <div class="metric-box orange">
            <div class="label">🪙 Coin Cuci</div>
            <div class="value">124 pcs</div>
            <div class="sub">= Rp 868.000</div>
        </div>
        <div class="metric-box amber">
            <div class="label">🪙 Coin Kering</div>
            <div class="value">130 pcs</div>
            <div class="sub">= Rp 1.300.000</div>
        </div>
    </div>
</div>

<!-- Rekapitulasi Shift Terbaru -->
<div class="card">
    <div class="card-title">
        <span>Rekapitulasi Shift</span>
        <a href="laporan.php" style="font-size: 12px; color: var(--primary-light); text-decoration: none;">Lihat Laporan →</a>
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
