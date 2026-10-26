<?php
$page_title = 'Laporan Keuangan - Kino Coin Laundry';
$header_role = 'Mode Pemilik';
$header_user = 'Pemilik';
$header_avatar = '👑';
$header_title = 'Laporan Keuangan';
$header_subtitle = 'Rekap omzet, perolehan koin & audit kas shift';
$current_role = 'admin';
$active_tab = 'laporan';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Kartu Ringkasan & Grafik Mingguan -->
<div class="card">
    <div style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Total Uang Masuk (Minggu Ini)</div>
    <div style="font-size: 26px; font-weight: 900; color: var(--primary-dark); margin: 6px 0 16px 0;">
        Rp 8.750.000
    </div>

    <!-- 7 Bar Chart -->
    <div style="display: flex; align-items: flex-end; justify-content: space-between; height: 120px; border-bottom: 2px solid var(--border-color); padding-bottom: 4px;">
        <?php
        $days = [
            ['date' => '6', 'pct' => '40%'],
            ['date' => '7', 'pct' => '55%'],
            ['date' => '8', 'pct' => '70%'],
            ['date' => '9', 'pct' => '60%'],
            ['date' => '10', 'pct' => '85%'],
            ['date' => '11', 'pct' => '90%'],
            ['date' => '12', 'pct' => '75%'],
        ];
        foreach ($days as $d):
        ?>
        <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
            <div style="width: 22px; height: <?= $d['pct'] ?>; background: #3b82f6; border-radius: 4px 4px 0 0;" title="Tgl <?= $d['date'] ?>"></div>
            <span style="font-size: 11px; color: var(--text-muted); margin-top: 6px; font-weight: 700;"><?= $d['date'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Detail Rekap Per Shift -->
<div class="card">
    <div class="card-title">
        <span>Detail Rekap Per Shift</span>
        <span style="font-size: 12px; color: var(--primary-light); font-weight: 600;">Terbaru</span>
    </div>

    <?php foreach ($_SESSION['shifts_recap'] as $sr): ?>
    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px; margin-bottom: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <strong style="font-size: 13px; letter-spacing: 0.5px;">
                <?= strtoupper($sr['tanggal']) ?> • <?= strtoupper($sr['shift']) ?>
            </strong>
            <span class="badge <?= $sr['status'] === 'Valid' ? 'badge-success' : 'badge-warning' ?>">
                <?= htmlspecialchars($sr['status']) ?>
            </span>
        </div>
        
        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 6px;">
            Kasir: <strong><?= htmlspecialchars($sr['kasir']) ?></strong> | <?= $sr['trx_count'] ?> trx • <?= $sr['total_kg'] ?> kg
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border-color); padding-top: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">
                500: <?= $sr['koin_500'] ?> pcs | 1K: <?= $sr['koin_1000'] ?> pcs
            </div>
            <div style="font-size: 15px; font-weight: 800; color: var(--primary-dark);">
                <?= format_rupiah($sr['pendapatan']) ?>
            </div>
        </div>

        <?php if ($sr['selisih'] != 0): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; margin-top: 8px; display: flex; justify-content: space-between;">
                <span>⚠️ Selisih Rekonsiliasi:</span>
                <span><?= format_rupiah($sr['selisih']) ?></span>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
