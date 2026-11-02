
<?php
$page_title = 'Owner Dashboard - Kino Coin Laundry';
$header_role = 'Mode Pemilik';
$header_user = 'Pemilik';
$header_avatar = '👑';
$header_title = 'Dashboard 👑';
$header_subtitle = 'Monitoring Pendapatan, Layanan & Shift';
$current_role = 'admin';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';

// ==========================================
// STATISTIK TRANSAKSI HARI INI
// ==========================================

$todayStats = web_one(
    'SELECT
        COUNT(*)::int AS total_transaksi,
        COALESCE(SUM(total_bayar), 0)::bigint AS pendapatan
     FROM transaksi
     WHERE tanggal_transaksi::date = CURRENT_DATE'
) ?? [];

// ==========================================
// REKAP PEMBAYARAN CASH DAN QRIS
// ==========================================

$todayPaymentStats = web_one(
    "SELECT
        COALESCE(SUM(total_bayar) FILTER (
            WHERE LOWER(jenis_pembayaran) = 'cash'
        ), 0)::bigint AS pembayaran_cash,
        COALESCE(SUM(total_bayar) FILTER (
            WHERE LOWER(jenis_pembayaran) = 'qris'
        ), 0)::bigint AS pembayaran_qris
     FROM transaksi
     WHERE tanggal_transaksi::date = CURRENT_DATE"
) ?? [];

// ==========================================
// PENDAPATAN MINGGUAN DAN BULANAN
// ==========================================

$weekStats = web_one(
    'SELECT
        COALESCE(SUM(total_bayar), 0)::bigint AS pendapatan
     FROM transaksi
     WHERE tanggal_transaksi >= CURRENT_DATE - INTERVAL \'6 days\''
) ?? [];

$monthStats = web_one(
    'SELECT
        COALESCE(SUM(total_bayar), 0)::bigint AS pendapatan
     FROM transaksi
     WHERE date_trunc(\'month\', tanggal_transaksi) =
           date_trunc(\'month\', CURRENT_DATE)'
) ?? [];

// ==========================================
// TOTAL BERAT HARI INI
// ==========================================

$todayWeight = web_one(
    'SELECT
        COALESCE(SUM(d.berat), 0)::numeric AS total_berat
     FROM transaksi t
     LEFT JOIN detail_transaksi d USING (id_transaksi)
     WHERE t.tanggal_transaksi::date = CURRENT_DATE'
) ?? [];

// ==========================================
// PERFORMA KASIR 30 HARI
// ==========================================

$cashierStats = web_rows(
    'SELECT
        u.nama,
        COUNT(t.id_transaksi)::int AS transaksi,
        COALESCE(SUM(t.total_bayar), 0)::bigint AS pendapatan
     FROM pengguna u
     LEFT JOIN transaksi t
        ON t.id_kasir = u.id_pengguna
       AND t.tanggal_transaksi >= CURRENT_DATE - INTERVAL \'29 days\'
     WHERE u.role = \'kasir\'
     GROUP BY u.id_pengguna, u.nama
     ORDER BY pendapatan DESC'
);

// ==========================================
// LAYANAN TERLARIS 30 HARI
// ==========================================

$serviceStats = web_rows(
    'SELECT
        l.nama_layanan,
        COUNT(DISTINCT t.id_transaksi)::int AS transaksi,
        COALESCE(SUM(d.berat), 0)::numeric AS berat,
        COALESCE(SUM(d.subtotal), 0)::bigint AS pendapatan
     FROM layanan l
     LEFT JOIN detail_transaksi d
        ON d.id_layanan = l.id_layanan
     LEFT JOIN transaksi t
        ON t.id_transaksi = d.id_transaksi
       AND t.tanggal_transaksi >= CURRENT_DATE - INTERVAL \'29 days\'
     GROUP BY l.id_layanan, l.nama_layanan
     ORDER BY transaksi DESC, pendapatan DESC
     LIMIT 5'
);

// ==========================================
// GRAFIK PEMBAYARAN 7 HARI TERAKHIR
// ==========================================

$weekRows = web_rows(
    "SELECT
        tanggal_transaksi::date AS tanggal,
        COALESCE(SUM(total_bayar) FILTER (
            WHERE LOWER(jenis_pembayaran) = 'cash'
        ), 0)::bigint AS pembayaran_cash,
        COALESCE(SUM(total_bayar) FILTER (
            WHERE LOWER(jenis_pembayaran) = 'qris'
        ), 0)::bigint AS pembayaran_qris
     FROM transaksi
     WHERE tanggal_transaksi >= CURRENT_DATE - INTERVAL '6 days'
     GROUP BY tanggal_transaksi::date
     ORDER BY tanggal_transaksi::date"
);

$weekByDate = [];
$maxPayment = 0;

foreach ($weekRows as $row) {
    $weekByDate[(string) $row['tanggal']] = $row;

    $maxPayment = max(
        $maxPayment,
        (int) $row['pembayaran_cash'],
        (int) $row['pembayaran_qris']
    );
}

$chart_data = [];

for ($i = 6; $i >= 0; $i--) {

    $date = (new DateTimeImmutable('today'))->modify("-$i days");
    $key = $date->format('Y-m-d');

    $row = $weekByDate[$key] ?? [
        'pembayaran_cash' => 0,
        'pembayaran_qris' => 0
    ];

    $chart_data[] = [
        'day' => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][(int) $date->format('w')],
        'h_cash' => (int) round(
            ((int) $row['pembayaran_cash'] / max(1, $maxPayment)) * 100
        ) . '%',
        'h_qris' => (int) round(
            ((int) $row['pembayaran_qris'] / max(1, $maxPayment)) * 100
        ) . '%'
    ];
}
?>

<!-- ==========================================
     KARTU METRIK BISNIS
========================================== -->

<div class="metrics-scroll">

    <div class="metric-card blue">
        <div class="metric-label">Pendapatan Hari Ini</div>
        <div class="metric-value">
            <?= format_rupiah($todayStats['pendapatan'] ?? 0) ?>
        </div>
        <div class="metric-sub">
            <?= (int) ($todayStats['total_transaksi'] ?? 0) ?> transaksi hari ini
        </div>
    </div>

    <div class="metric-card orange">
        <div class="metric-label">Pembayaran Cash</div>
        <div class="metric-value">
            <?= format_rupiah($todayPaymentStats['pembayaran_cash'] ?? 0) ?>
        </div>
        <div class="metric-sub">Pembayaran tunai hari ini</div>
    </div>

    <div class="metric-card amber">
        <div class="metric-label">Pembayaran QRIS</div>
        <div class="metric-value">
            <?= format_rupiah($todayPaymentStats['pembayaran_qris'] ?? 0) ?>
        </div>
        <div class="metric-sub">Pembayaran QRIS hari ini</div>
    </div>

    <div class="metric-card blue">
        <div class="metric-label">Minggu Ini</div>
        <div class="metric-value">
            <?= format_rupiah($weekStats['pendapatan'] ?? 0) ?>
        </div>
        <div class="metric-sub">Pendapatan 7 hari terakhir</div>
    </div>

    <div class="metric-card orange">
        <div class="metric-label">Bulan Ini</div>
        <div class="metric-value">
            <?= format_rupiah($monthStats['pendapatan'] ?? 0) ?>
        </div>
        <div class="metric-sub">Pendapatan bulan berjalan</div>
    </div>

    <div class="metric-card amber">
        <div class="metric-label">Berat Hari Ini</div>
        <div class="metric-value">
            <?= number_format((float) ($todayWeight['total_berat'] ?? 0), 1, ',', '.') ?> kg
        </div>
        <div class="metric-sub">
            <?= (int) ($todayStats['total_transaksi'] ?? 0) ?> transaksi
        </div>
    </div>

</div>

<!-- ==========================================
     GRAFIK PEMBAYARAN DAN REKAP SHIFT
========================================== -->

<div class="responsive-grid-2">

    <div class="card">
        <div class="card-title">
            <span>Pembayaran Cash & QRIS</span>
            <span style="font-size: 11px; color: var(--text-muted);">
                7 hari terakhir
            </span>
        </div>

        <div class="chart-container">
            <div class="bar-chart">

                <?php foreach ($chart_data as $cd): ?>

                    <div class="chart-bar-group">

                        <div class="bar-stack">
                            <div
                                class="bar-pillar bar-orange"
                                style="height: <?= $cd['h_cash'] ?>;"
                                title="Cash">
                            </div>

                            <div
                                class="bar-pillar bar-blue"
                                style="height: <?= $cd['h_qris'] ?>;"
                                title="QRIS">
                            </div>
                        </div>

                        <span class="bar-label">
                            <?= $cd['day'] ?>
                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

            <div class="chart-legend">

                <div class="legend-item">
                    <div class="legend-dot" style="background: var(--accent);"></div>
                    <span>Cash</span>
                </div>

                <div class="legend-item">
                    <div class="legend-dot" style="background: var(--primary-dark);"></div>
                    <span>QRIS</span>
                </div>

            </div>
        </div>
    </div>

    <!-- REKAP SHIFT TERBARU -->

    <div class="card">

        <div class="card-title">
            <span>Rekap Shift Terbaru</span>

            <a href="laporan.php"
                style="color: var(--primary-light); font-size: 13px; text-decoration: none; font-weight: 600;">
                Semua Laporan
            </a>
        </div>

        <?php if (empty($shifts_recap)): ?>

            <p style="font-size:13px;color:var(--text-muted);">
                Belum ada shift yang tercatat.
            </p>

        <?php endif; ?>

        <?php foreach ($shifts_recap as $sr): ?>

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

                    Kasir:
                    <strong><?= htmlspecialchars($sr['kasir']) ?></strong>
                    |
                    <?= (int) $sr['trx_count'] ?> trx
                    •
                    <?= $sr['total_kg'] ?> kg

                </div>

                <div style="display: flex; justify-content: flex-end; align-items: center; border-top: 1px dashed var(--border-color); padding-top: 8px;">

                    <div style="font-size: 15px; font-weight: 800; color: var(--primary-dark);">
                        <?= format_rupiah($sr['pendapatan']) ?>
                    </div>

                </div>

                <?php if ((float) $sr['selisih'] != 0): ?>

                    <div style="font-size: 11px; color: var(--danger); font-weight: 700; margin-top: 4px;">
                        Selisih: <?= format_rupiah($sr['selisih']) ?>
                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<!-- ==========================================
     PERFORMA KASIR DAN LAYANAN
========================================== -->

<div class="responsive-grid-2">

    <div class="card">

        <div class="card-title">Performa Kasir (30 Hari)</div>

        <?php if (empty($cashierStats)): ?>

            <p style="font-size:13px;color:var(--text-muted);">
                Belum ada data kasir.
            </p>

        <?php endif; ?>

        <?php foreach ($cashierStats as $stat): ?>

            <div style="display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-color);">

                <div>
                    <strong><?= web_h($stat['nama']) ?></strong>

                    <div style="font-size:12px;color:var(--text-muted);">
                        <?= (int) $stat['transaksi'] ?> transaksi
                    </div>
                </div>

                <strong>
                    <?= format_rupiah($stat['pendapatan']) ?>
                </strong>

            </div>

        <?php endforeach; ?>

    </div>

    <div class="card">

        <div class="card-title">Layanan Terlaris (30 Hari)</div>

        <?php if (empty($serviceStats)): ?>

            <p style="font-size:13px;color:var(--text-muted);">
                Belum ada data layanan.
            </p>

        <?php endif; ?>

        <?php foreach ($serviceStats as $stat): ?>

            <div style="display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-color);">

                <div>
                    <strong><?= web_h($stat['nama_layanan']) ?></strong>

                    <div style="font-size:12px;color:var(--text-muted);">
                        <?= (int) $stat['transaksi'] ?> transaksi
                        •
                        <?= number_format((float) $stat['berat'], 1, ',', '.') ?> kg
                    </div>
                </div>

                <strong>
                    <?= format_rupiah($stat['pendapatan']) ?>
                </strong>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
