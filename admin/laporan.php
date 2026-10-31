<?php
$page_title = 'Laporan Transaksi - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'laporan';

require_once __DIR__ . '/../includes/header.php';

$periode = $_GET['periode'] ?? 'Hari Ini';
$layanan_filter = $_GET['layanan'] ?? 'Semua';
$kasir_filter = $_GET['kasir'] ?? 'Semua';

$txs = $_SESSION['transactions'];

if ($periode === 'Kosong') {
    $txs = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <h2 style="font-size: 16px; font-weight: 800;">Laporan Transaksi</h2>
        <span style="font-size: 11px; color: var(--text-muted);">Audit keuangan &amp; volume shift</span>
    </div>
    <button type="button" class="btn btn-outline" style="width: auto; padding: 6px 12px; font-size: 12px;" onclick="showToast('Laporan berhasil diekspor ke CSV', 'success')">
        📥 Export
    </button>
</div>

<!-- Filter Periode -->
<div class="filter-scroll" style="margin-bottom: 8px;">
    <a href="laporan.php?periode=Hari+Ini" class="chip <?= $periode === 'Hari Ini' ? 'active' : '' ?>">Hari Ini</a>
    <a href="laporan.php?periode=Kemarin" class="chip <?= $periode === 'Kemarin' ? 'active' : '' ?>">Kemarin</a>
    <a href="laporan.php?periode=7+Hari" class="chip <?= $periode === '7 Hari' ? 'active' : '' ?>">7 Hari</a>
    <a href="laporan.php?periode=Custom" class="chip <?= $periode === 'Custom' ? 'active' : '' ?>">Custom</a>
    <a href="laporan.php?periode=Kosong" class="chip <?= $periode === 'Kosong' ? 'active' : '' ?>">Kosong (Test)</a>
</div>

<?php if ($periode === 'Custom'): ?>
    <div class="card" style="padding: 12px; margin-bottom: 12px; background: #eff6ff; border-color: #bfdbfe;">
        <div style="display: flex; gap: 8px; align-items: center;">
            <input type="date" class="form-control" value="2026-10-25" style="font-size: 12px; padding: 8px;">
            <span>s/d</span>
            <input type="date" class="form-control" value="2026-10-31" style="font-size: 12px; padding: 8px;">
            <button type="button" class="btn btn-primary" style="width: auto; padding: 8px 12px; font-size: 12px;">Terapkan</button>
        </div>
    </div>
<?php endif; ?>

<!-- Omset Summary & Chart -->
<?php if (empty($txs)): ?>
    <div class="card" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
        <div style="font-size: 32px; margin-bottom: 8px;">📊</div>
        <strong style="font-size: 14px; color: var(--text-main);">Tidak ada transaksi di periode ini</strong>
        <p style="font-size: 12px; margin-top: 4px;">Pilih periode lain atau atur filter kasir.</p>
    </div>
<?php else: ?>
    <div class="card">
        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">TOTAL OMSET (<?= strtoupper($periode) ?>)</div>
        <div style="font-size: 24px; font-weight: 900; color: var(--primary-dark); margin: 4px 0 14px 0;">
            Rp 56.000 <span style="font-size: 12px; font-weight: normal; color: var(--text-muted);">(<?= count($txs) ?> transaksi)</span>
        </div>

        <!-- 7-Day Bar Chart Mockup (Figma P2) -->
        <div style="display: flex; align-items: flex-end; justify-content: space-between; height: 110px; border-bottom: 2px solid var(--border); padding-bottom: 4px; margin-bottom: 14px;">
            <?php
            $chart_days = [
                ['d' => '25', 'pct' => '40%'],
                ['d' => '26', 'pct' => '55%'],
                ['d' => '27', 'pct' => '65%'],
                ['d' => '28', 'pct' => '50%'],
                ['d' => '29', 'pct' => '75%'],
                ['d' => '30', 'pct' => '90%'],
                ['d' => '31', 'pct' => '85%'],
            ];
            foreach ($chart_days as $cd):
            ?>
            <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
                <div style="width: 20px; height: <?= $cd['pct'] ?>; background: #2563eb; border-radius: 4px 4px 0 0;" title="Tgl <?= $cd['d'] ?>"></div>
                <span style="font-size: 10px; color: var(--text-muted); font-weight: 700; margin-top: 4px;"><?= $cd['d'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Detail Invoice List -->
        <div class="card-title">Detail Transaksi</div>
        <?php foreach ($txs as $t): ?>
        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 10px 12px; margin-bottom: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                <strong style="font-size: 13px; color: var(--text-main);"><?= htmlspecialchars($t['id']) ?> • <?= htmlspecialchars($t['pelanggan']) ?></strong>
                <span class="badge badge-lunas"><?= $t['status'] ?></span>
            </div>
            <div style="font-size: 11px; color: var(--text-muted); display: flex; justify-content: space-between;">
                <span><?= $t['layanan'] ?> (Kasir <?= $t['kasir'] ?>)</span>
                <strong style="color: var(--primary);"><?= format_rupiah($t['total_bayar']) ?></strong>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
