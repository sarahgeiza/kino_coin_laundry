<?php
$page_title = 'Owner Dashboard - Kino Coin Laundry';
$header_role = 'Mode Pemilik';
$header_user = 'Pemilik';
$header_avatar = '👑';
$header_title = 'Dashboard 👑';
$header_subtitle = 'Monitoring Real-time Mesin, Koin & Shift';
$current_role = 'admin';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Kartu Metrik Bisnis -->
<div class="metrics-scroll">
    <div class="metric-card blue">
        <div class="metric-label">Hari Ini</div>
        <div class="metric-value">Rp 1.25jt</div>
        <div class="metric-sub" style="color: #86efac; font-weight: bold;">↑ 8% dibanding kemarin</div>
    </div>
    <div class="metric-card orange">
        <div class="metric-label">🪙 Koin 500</div>
        <div class="metric-value">1.240</div>
        <div class="metric-sub">= Rp 620.000</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">🪙 Koin 1000</div>
        <div class="metric-value">630</div>
        <div class="metric-sub">= Rp 630.000</div>
    </div>
</div>

<div class="responsive-grid-2">
    <!-- Grafik Koin Terkumpul 7 Hari -->
    <div class="card">
        <div class="card-title">
            <span>Koin Terkumpul (7 Hari)</span>
            <span style="font-size: 11px; color: var(--text-muted);">6 Mei — 12 Mei</span>
        </div>

        <div class="chart-container">
            <div class="bar-chart">
                <?php
                $chart_data = [
                    ['day' => 'Sen', 'h_org' => '50%', 'h_blu' => '40%'],
                    ['day' => 'Sel', 'h_org' => '65%', 'h_blu' => '55%'],
                    ['day' => 'Rab', 'h_org' => '45%', 'h_blu' => '60%'],
                    ['day' => 'Kam', 'h_org' => '70%', 'h_blu' => '50%'],
                    ['day' => 'Jum', 'h_org' => '85%', 'h_blu' => '75%'],
                    ['day' => 'Sab', 'h_org' => '95%', 'h_blu' => '90%'],
                    ['day' => 'Min', 'h_org' => '80%', 'h_blu' => '70%'],
                ];
                foreach ($chart_data as $cd):
                ?>
                <div class="chart-bar-group">
                    <div class="bar-stack">
                        <div class="bar-pillar bar-orange" style="height: <?= $cd['h_org'] ?>;" title="Koin 500"></div>
                        <div class="bar-pillar bar-blue" style="height: <?= $cd['h_blu'] ?>;" title="Koin 1000"></div>
                    </div>
                    <span class="bar-label"><?= $cd['day'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-dot" style="background: var(--accent);"></div>
                    <span>Koin 500</span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background: var(--primary-dark);"></div>
                    <span>Koin 1.000</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Rekap Shift Terbaru -->
    <div class="card">
        <div class="card-title">
            <span>Rekap Shift Terbaru</span>
            <a href="laporan.php" style="color: var(--primary-light); font-size: 13px; text-decoration: none; font-weight: 600;">Semua Laporan</a>
        </div>

        <?php foreach ($_SESSION['shifts_recap'] as $sr): ?>
        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px; margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <strong style="font-size: 13px; letter-spacing: 0.5px;">
                    <?= strtoupper($sr['tanggal']) ?> • <?= strtoupper($sr['shift']) ?>
                </strong>
                <span class="badge <?= $sr['status'] === 'Valid' ? 'badge-success' : 'badge-warning' ?>">
                    <?= htmlspecialchars($sr['status']) ?>
                </span>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">
                Kasir: <strong><?= htmlspecialchars($sr['kasir']) ?></strong> | <?= $sr['trx_count'] ?> trx • <?= $sr['total_kg'] ?> kg
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border-color); padding-top: 6px;">
                <div style="font-size: 12px; color: var(--text-muted);">
                    500: <?= $sr['koin_500'] ?> pcs | 1K: <?= $sr['koin_1000'] ?> pcs
                </div>
                <div style="font-size: 15px; font-weight: 800; color: var(--primary-dark);">
                    <?= format_rupiah($sr['pendapatan']) ?>
                </div>
            </div>
            <?php if ($sr['selisih'] != 0): ?>
                <div style="font-size: 11px; color: var(--danger); font-weight: 700; margin-top: 4px;">
                    ⚠️ Selisih: <?= format_rupiah($sr['selisih']) ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
