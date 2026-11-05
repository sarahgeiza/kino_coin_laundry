<?php
$page_title = 'Dashboard Pemilik - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';

$todaySummary = one(
    "SELECT COALESCE(SUM(t.total_bayar), 0)::bigint AS pendapatan,
            COALESCE(SUM(d.koin_cuci), 0)::bigint AS koin_cuci,
            COALESCE(SUM(d.koin_kering), 0)::bigint AS koin_kering
     FROM transaksi t
     LEFT JOIN (
        SELECT d.id_transaksi,
               SUM(d.jumlah_koin) FILTER (WHERE LOWER(l.nama_layanan) LIKE '%cuci%') AS koin_cuci,
               SUM(d.jumlah_koin) FILTER (WHERE LOWER(l.nama_layanan) LIKE '%kering%') AS koin_kering
        FROM detail_transaksi d
        JOIN layanan l USING (id_layanan)
        GROUP BY d.id_transaksi
     ) d USING (id_transaksi)
     WHERE t.tanggal_transaksi >= CURRENT_DATE
       AND t.tanggal_transaksi < CURRENT_DATE + INTERVAL '1 day'"
);
$owner_omset = (int)($todaySummary['pendapatan'] ?? 0);
$owner_wash = (int)($todaySummary['koin_cuci'] ?? 0);
$owner_dry = (int)($todaySummary['koin_kering'] ?? 0);
$yesterdayIncome = (int)(one(
    "SELECT COALESCE(SUM(total_bayar), 0)::bigint AS pendapatan
     FROM transaksi
     WHERE tanggal_transaksi >= CURRENT_DATE - INTERVAL '1 day'
       AND tanggal_transaksi < CURRENT_DATE"
)['pendapatan'] ?? 0);
$incomeComparison = $yesterdayIncome > 0
    ? sprintf('%s%.1f%% vs kemarin', $owner_omset >= $yesterdayIncome ? '↑ ' : '↓ ', abs(($owner_omset - $yesterdayIncome) / $yesterdayIncome * 100))
    : 'Belum ada data kemarin';
$shiftRecaps = rows(
    "SELECT TO_CHAR(so.mulai_pada, 'DD Mon YYYY') AS tanggal,
            s.nama_shift AS shift,
            p.nama AS kasir,
            COUNT(DISTINCT t.id_transaksi)::int AS trx_count,
            COALESCE(SUM(d.total_berat), 0)::numeric AS total_kg,
            COALESCE(SUM(d.koin_cuci), 0)::int AS koin_cuci,
            COALESCE(SUM(d.koin_kering), 0)::int AS koin_kering,
            COALESCE(SUM(t.total_bayar), 0)::bigint AS pendapatan,
            CASE WHEN so.selisih = 0 THEN 'Valid' ELSE 'Review' END AS status
     FROM shift_operasional so
     JOIN shift s USING (id_shift)
     JOIN pengguna p ON p.id_pengguna = so.id_kasir
     LEFT JOIN transaksi t USING (id_shift_operasional)
     LEFT JOIN (
        SELECT d.id_transaksi,
               SUM(COALESCE(d.berat, 0)) AS total_berat,
               SUM(d.jumlah_koin) FILTER (WHERE LOWER(l.nama_layanan) LIKE '%cuci%') AS koin_cuci,
               SUM(d.jumlah_koin) FILTER (WHERE LOWER(l.nama_layanan) LIKE '%kering%') AS koin_kering
        FROM detail_transaksi d
        JOIN layanan l USING (id_layanan)
        GROUP BY d.id_transaksi
     ) d USING (id_transaksi)
     WHERE so.status = 'ditutup'
     GROUP BY so.id_shift_operasional, s.id_shift, p.id_pengguna
     ORDER BY so.mulai_pada DESC
     LIMIT 5"
);
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
            <div class="sub" style="color: #86efac; font-weight: 700;"><?= htmlspecialchars($incomeComparison) ?></div>
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

    <?php foreach ($shiftRecaps as $sr): ?>
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
