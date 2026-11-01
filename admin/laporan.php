<?php
$page_title = 'Laporan Transaksi - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'laporan';

require_once __DIR__ . '/../includes/header.php';

$periode = $_GET['periode'] ?? 'Hari Ini';
$layanan_filter = $_GET['layanan'] ?? 'Semua';
$kasir_filter = $_GET['kasir'] ?? 'Semua';

// 1. Hitung transaksi dan omset hari ini secara dinamis dan real-time dari sesi
$all_session_txs = $_SESSION['transactions'] ?? [];
$today_real_omset = 0;
foreach ($all_session_txs as $t) {
    $today_real_omset += ($t['total_bayar'] ?? $t['total_biaya'] ?? 0);
}

// 2. Data historis 7 hari terakhir (baseline tren operasional toko)
$chart_days = [
    ['d' => '25', 'name' => 'Min', 'omset' => 380000, 'is_active' => false],
    ['d' => '26', 'name' => 'Sen', 'omset' => 420000, 'is_active' => false],
    ['d' => '27', 'name' => 'Sel', 'omset' => 510000, 'is_active' => false],
    ['d' => '28', 'name' => 'Rab', 'omset' => 390000, 'is_active' => false],
    ['d' => '29', 'name' => 'Kam', 'omset' => 480000, 'is_active' => false],
    ['d' => '30', 'name' => 'Jum', 'omset' => 520000, 'is_active' => false],
    ['d' => '31', 'name' => 'Sab', 'omset' => $today_real_omset, 'is_active' => false],
];

// 3. Tangani filter periode
if ($periode === 'Kosong') {
    $txs = [];
    $total_laporan_omset = 0;
    foreach ($chart_days as &$cd) {
        $cd['omset'] = 0;
        $cd['is_active'] = false;
    }
} elseif ($periode === 'Kemarin') {
    $chart_days[5]['is_active'] = true; // Jumat 30 Okt
    $total_laporan_omset = 520000;
    $txs = [
        [
            'id' => 'INV-2026-K01',
            'pelanggan' => 'Hendro Wijaya',
            'layanan' => 'Cuci Kering (Self)',
            'kasir' => 'Sarah',
            'status' => 'Lunas',
            'total_bayar' => 17000
        ],
        [
            'id' => 'INV-2026-K02',
            'pelanggan' => 'Dewi Anggraini',
            'layanan' => 'Cuci Kering Lengkap',
            'kasir' => 'Sarah',
            'status' => 'Lunas',
            'total_bayar' => 32000
        ],
        [
            'id' => 'INV-2026-K03',
            'pelanggan' => 'Fajar Nugraha',
            'layanan' => 'Drop Off Regular',
            'kasir' => 'Nizar',
            'status' => 'Lunas',
            'total_bayar' => 25000
        ]
    ];
} elseif ($periode === '7 Hari') {
    foreach ($chart_days as &$cd) {
        $cd['is_active'] = true;
    }
    $total_laporan_omset = array_sum(array_column($chart_days, 'omset'));
    $txs = $all_session_txs;
} else {
    // Default: Hari Ini
    $chart_days[6]['is_active'] = true; // Hari ini aktif
    $txs = $all_session_txs;
    $total_laporan_omset = $today_real_omset;
}

// 4. Hitung skala tinggi pixel bar chart secara proporsional
$max_chart_omset = max(array_column($chart_days, 'omset'));
if ($max_chart_omset <= 0) $max_chart_omset = 100000;

foreach ($chart_days as &$cd) {
    if ($cd['omset'] <= 0) {
        $cd['height_px'] = 6;
        $cd['short'] = '0';
    } else {
        $cd['height_px'] = max(12, round(($cd['omset'] / $max_chart_omset) * 75));
        $cd['short'] = ($cd['omset'] >= 1000000) 
            ? round($cd['omset'] / 1000000, 1) . 'jt'
            : round($cd['omset'] / 1000) . 'k';
    }
}
unset($cd);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <h2 style="font-size: 16px; font-weight: 800;">Laporan Transaksi</h2>
        <span style="font-size: 11px; color: var(--text-muted);">Audit keuangan &amp; volume shift</span>
    </div>
    <button type="button" class="btn btn-outline" style="width: auto; padding: 6px 12px; font-size: 12px;" onclick="exportLaporanTransactionsCSV()">
        <?= get_icon('export', 14) ?> Export
    </button>
</div>

<!-- Filter Periode (Figma P2) -->
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
<?php if (empty($txs) && $periode === 'Kosong'): ?>
    <div class="card" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
        <div style="margin-bottom: 8px;"><?= get_icon('rekap', 36, '#94a3b8') ?></div>
        <strong style="font-size: 14px; color: var(--text-main);">Tidak ada transaksi di periode ini</strong>
        <p style="font-size: 12px; margin-top: 4px;">Pilih periode lain atau atur filter kasir.</p>
    </div>
<?php else: ?>
    <div class="card">
        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">TOTAL OMSET (<?= strtoupper($periode) ?>)</div>
        <div style="font-size: 24px; font-weight: 900; color: var(--primary-dark); margin: 4px 0 14px 0;">
            <?= format_rupiah($total_laporan_omset) ?> <span style="font-size: 12px; font-weight: normal; color: var(--text-muted);">(<?= count($txs) ?> transaksi)</span>
        </div>

        <!-- Dynamic 7-Day Bar Chart (Figma P2) -->
        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px 12px 10px 12px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Grafik Tren Omset 7 Hari</span>
                <span style="font-size: 10px; font-weight: 700; color: #2563eb; background: #dbeafe; padding: 2px 8px; border-radius: 12px;">Dinamis Real-Time</span>
            </div>

            <!-- Chart Bars Container -->
            <div style="display: flex; align-items: flex-end; justify-content: space-between; height: 115px; border-bottom: 2px solid #cbd5e1; padding-bottom: 4px;">
                <?php foreach ($chart_days as $cd): ?>
                <div style="height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; flex: 1; padding: 0 2px;">
                    <!-- Micro amount label on top -->
                    <span style="font-size: 9px; font-weight: 800; color: <?= $cd['is_active'] ? '#1d4ed8' : '#64748b' ?>; margin-bottom: 4px; white-space: nowrap;">
                        <?= $cd['short'] ?>
                    </span>
                    
                    <!-- Bar with explicit pixel height and rounded top -->
                    <div style="width: 100%; max-width: 24px; height: <?= $cd['height_px'] ?>px; min-height: 8px; background: <?= $cd['is_active'] ? 'linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%)' : '#94a3b8' ?>; border-radius: 5px 5px 0 0; transition: all 0.3s ease; box-shadow: <?= $cd['is_active'] ? '0 3px 8px rgba(37,99,235,0.35)' : 'none' ?>;" title="Tgl <?= $cd['d'] ?>: <?= format_rupiah($cd['omset']) ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- X-Axis Date Labels -->
            <div style="display: flex; justify-content: space-between; padding-top: 6px;">
                <?php foreach ($chart_days as $cd): ?>
                <div style="flex: 1; text-align: center;">
                    <span style="font-size: 10px; font-weight: <?= $cd['is_active'] ? '900' : '700' ?>; color: <?= $cd['is_active'] ? '#1e3a8a' : '#64748b' ?>; display: block;">
                        <?= $cd['d'] ?>
                    </span>
                    <span style="font-size: 9px; color: #94a3b8; display: block; margin-top: -2px;">
                        <?= $cd['name'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
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
