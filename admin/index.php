<?php
$page_title = 'Dashboard Pemilik - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Section Monitoring Bisnis Top Row -->
<div class="card" style="padding: 18px; margin-bottom: 20px;">
    <div class="card-title" style="margin-bottom: 12px;">
        <span>Monitoring Bisnis Hari Ini</span>
        <span style="font-size: 12px; color: var(--primary); font-weight: 700;">31 Okt 2026</span>
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

<div class="grid-2-desktop">

    <!-- Kolom Kiri: Rekapitulasi Shift -->
    <div>
        <div class="card">
            <div class="card-title">
                <span>Rekapitulasi Shift Kasir</span>
                <a href="laporan.php" style="font-size: 12px; color: var(--primary-light); text-decoration: none; font-weight: 700;">Lihat Laporan Detail →</a>
            </div>

            <?php foreach ($_SESSION['shifts_recap'] as $sr): ?>
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px; margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <strong style="font-size: 13px; color: var(--text-main);">
                        <?= strtoupper($sr['tanggal']) ?> • <?= strtoupper($sr['shift']) ?>
                    </strong>
                    <span class="badge <?= $sr['status'] === 'Valid' ? 'badge-lunas' : 'badge-kurang' ?>">
                        <?= htmlspecialchars($sr['status']) ?>
                    </span>
                </div>
                <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 8px;">
                    Kasir Bertugas: <strong><?= htmlspecialchars($sr['kasir']) ?></strong> • <?= $sr['trx_count'] ?> transaksi • <?= $sr['total_kg'] ?> kg
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border); padding-top: 8px; font-size: 12px;">
                    <span style="color: var(--text-muted);">Token: <?= $sr['koin_cuci'] ?> Coin Cuci | <?= $sr['koin_kering'] ?> Coin Kering</span>
                    <strong style="font-size: 15px; color: var(--primary-dark);"><?= format_rupiah($sr['pendapatan']) ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Kolom Kanan: Pintasan Manajemen & Audit -->
    <div>
        <div class="card">
            <div class="card-title">Navigasi Cepat Manajemen</div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="laporan.php" class="btn btn-outline" style="justify-content: flex-start; padding: 14px 18px;">
                    <span style="font-size: 18px;">📊</span>
                    <span>Audit Laporan Keuangan &amp; Grafik Omset</span>
                </a>
                <a href="master.php?tab=layanan" class="btn btn-outline" style="justify-content: flex-start; padding: 14px 18px;">
                    <span style="font-size: 18px;">🧺</span>
                    <span>Kelola Master Layanan &amp; Tarif Token</span>
                </a>
                <a href="master.php?tab=shift" class="btn btn-outline" style="justify-content: flex-start; padding: 14px 18px;">
                    <span style="font-size: 18px;">⏰</span>
                    <span>Pengaturan Jadwal Shift &amp; Staf</span>
                </a>
                <a href="presensi.php" class="btn btn-outline" style="justify-content: flex-start; padding: 14px 18px;">
                    <span style="font-size: 18px;">🕒</span>
                    <span>Log Presensi Kehadiran Kasir</span>
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
