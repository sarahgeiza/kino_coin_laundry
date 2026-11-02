<?php
$page_title = 'Laporan Keuangan - Kino Coin Laundry';
$header_role = 'Mode Pemilik';
$header_user = 'Pemilik';
$header_avatar = '👑';
$header_title = 'Laporan Keuangan';
$header_subtitle = 'Rekap pendapatan Cash, QRIS, dan audit kas shift';
$current_role = 'admin';
$active_tab = 'laporan';

require_once __DIR__ . '/../includes/header.php';
$startDate = (string) ($_GET['start'] ?? date('Y-m-01'));
$endDate = (string) ($_GET['end'] ?? date('Y-m-d'));
foreach ([$startDate, $endDate] as $dateValue) {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
    if (!$parsed || $parsed->format('Y-m-d') !== $dateValue) {
        http_response_code(422);
        exit('Format tanggal laporan tidak valid.');
    }
}
if ($startDate > $endDate) {
    http_response_code(422);
    exit('Tanggal awal tidak boleh melewati tanggal akhir.');
}
$where = ' WHERE t.tanggal_transaksi >= ?::date AND t.tanggal_transaksi < (?::date + INTERVAL \'1 day\')';
$params = [$startDate, $endDate];
if (isset($_GET['shift']) && $_GET['shift'] !== '') {
    $where .= ' AND t.id_shift = ?';
    $params[] = (int) $_GET['shift'];
}
if (isset($_GET['kasir']) && $_GET['kasir'] !== '') {
    $where .= ' AND t.id_kasir = ?';
    $params[] = (int) $_GET['kasir'];
}
$reportStats = web_one(
    'SELECT COUNT(*)::int AS jumlah_transaksi,COALESCE(SUM(t.jumlah_dibayar),0)::bigint AS pendapatan,
            COALESCE(SUM(d.total_berat),0)::numeric AS total_berat,
            COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'cash\'),0)::bigint AS pendapatan_cash,
            COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'qris\'),0)::bigint AS pendapatan_qris
     FROM transaksi t
     LEFT JOIN (SELECT id_transaksi,SUM(COALESCE(berat,0)) total_berat FROM detail_transaksi GROUP BY id_transaksi) d USING(id_transaksi)' . $where,
    $params
) ?? [];
$dailyRows = web_rows(
    'SELECT t.tanggal_transaksi::date AS tanggal,COALESCE(SUM(t.jumlah_dibayar),0)::bigint AS pendapatan
     FROM transaksi t' . $where . ' GROUP BY t.tanggal_transaksi::date ORDER BY t.tanggal_transaksi::date',
    $params
);
$shiftWhere = ' WHERE so.mulai_pada::date BETWEEN ?::date AND ?::date';
$shiftParams = [$startDate, $endDate];
if (isset($_GET['shift']) && $_GET['shift'] !== '') {
    $shiftWhere .= ' AND so.id_shift = ?';
    $shiftParams[] = (int) $_GET['shift'];
}
if (isset($_GET['kasir']) && $_GET['kasir'] !== '') {
    $shiftWhere .= ' AND so.id_kasir = ?';
    $shiftParams[] = (int) $_GET['kasir'];
}
$reportShifts = web_rows(
    'SELECT so.id_shift_operasional,so.mulai_pada,so.ditutup_pada,so.status,so.total_sistem,so.total_fisik,so.selisih,
            s.nama_shift,u.nama AS kasir,COUNT(DISTINCT t.id_transaksi)::int AS transaksi,
            COALESCE(SUM(t.jumlah_dibayar),0)::bigint AS pendapatan,
            COALESCE(SUM(d.total_berat),0)::numeric AS berat,
            COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'cash\'),0)::bigint AS pendapatan_cash,
            COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'qris\'),0)::bigint AS pendapatan_qris
     FROM shift_operasional so JOIN shift s USING(id_shift) JOIN pengguna u ON u.id_pengguna=so.id_kasir
     LEFT JOIN transaksi t USING(id_shift_operasional)
     LEFT JOIN (SELECT id_transaksi,SUM(COALESCE(berat,0)) total_berat FROM detail_transaksi GROUP BY id_transaksi) d USING(id_transaksi)
     ' . $shiftWhere . '
     GROUP BY so.id_shift_operasional,s.nama_shift,u.nama ORDER BY so.mulai_pada DESC',
    $shiftParams
);
$shiftOptions = web_rows('SELECT id_shift,nama_shift FROM shift ORDER BY id_shift');
$cashierOptions = web_rows('SELECT id_pengguna,nama FROM pengguna WHERE role = \'kasir\' ORDER BY nama');
?>

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
    <div class="form-group"><label class="form-label">Dari tanggal</label><input class="form-control" type="date"
            name="start" value="<?= web_h($startDate) ?>"></div>
    <div class="form-group"><label class="form-label">Sampai tanggal</label><input class="form-control" type="date"
            name="end" value="<?= web_h($endDate) ?>"></div>
    <div class="form-group"><label class="form-label">Shift</label><select class="form-control" name="shift">
            <option value="">Semua shift</option><?php foreach ($shiftOptions as $o): ?>
                <option value="<?= (int) $o['id_shift'] ?>"
                    <?= (string) ($_GET['shift'] ?? '') === (string) $o['id_shift'] ? 'selected' : '' ?>><?= web_h($o['nama_shift']) ?>
                </option><?php endforeach; ?>
        </select></div>
    <div class="form-group"><label class="form-label">Kasir</label><select class="form-control" name="kasir">
            <option value="">Semua kasir</option><?php foreach ($cashierOptions as $o): ?>
                <option value="<?= (int) $o['id_pengguna'] ?>"
                    <?= (string) ($_GET['kasir'] ?? '') === (string) $o['id_pengguna'] ? 'selected' : '' ?>><?= web_h($o['nama']) ?>
                </option><?php endforeach; ?>
        </select></div>
    <button class="btn btn-primary" type="submit">Terapkan Filter</button>
</form>

<!-- Kartu Ringkasan & Grafik Mingguan -->
<div class="card">
    <div style="font-size: 13px; color: var(--text-muted); font-weight: 600;">Total Pendapatan (<?= web_h($startDate) ?>
        — <?= web_h($endDate) ?>)</div>
    <div style="font-size: 26px; font-weight: 900; color: var(--primary-dark); margin: 6px 0 16px 0;">
        <?= format_rupiah($reportStats['pendapatan'] ?? 0) ?>
    </div>

    <!-- 7 Bar Chart -->
    <div
        style="display: flex; align-items: flex-end; justify-content: space-between; height: 120px; border-bottom: 2px solid var(--border-color); padding-bottom: 4px;">
        <?php
        $maxDaily = max(1, ...array_map(static fn($r) => (int) $r['pendapatan'], $dailyRows ?: [['pendapatan' => 0]]));
        $days = $dailyRows;
        foreach ($days as &$d)
            $d['pct'] = (int) round(((int) $d['pendapatan'] / $maxDaily) * 100) . '%';
        unset($d);
        foreach ($days as $d):
            ?>
            <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
                <div style="width: 22px; height: <?= $d['pct'] ?>; background: #3b82f6; border-radius: 4px 4px 0 0;"
                    title="Tgl <?= date('d', strtotime($d['tanggal'])) ?>"></div>
                <span
                    style="font-size: 11px; color: var(--text-muted); margin-top: 6px; font-weight: 700;"><?= date('d', strtotime($d['tanggal'])) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="font-size:13px;color:var(--text-muted);margin-top:12px;">
        <?= (int) ($reportStats['jumlah_transaksi'] ?? 0) ?> transaksi •
        <?= number_format((float) ($reportStats['total_berat'] ?? 0), 1, ',', '.') ?> kg •
        Cash: <?= format_rupiah($reportStats['pendapatan_cash'] ?? 0) ?> •
        QRIS: <?= format_rupiah($reportStats['pendapatan_qris'] ?? 0) ?>
    </div>
</div>

<!-- Detail Rekap Per Shift -->
<div class="card">
    <div class="card-title">
        <span>Detail Rekap Per Shift</span>
        <span style="font-size: 12px; color: var(--primary-light); font-weight: 600;">Terbaru</span>
    </div>

    <?php if (!$reportShifts): ?>
        <p style="font-size:13px;color:var(--text-muted);">Belum ada rekap shift pada periode ini.</p><?php endif; ?>
    <?php foreach ($reportShifts as $sr): ?>
        <div
            style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px; margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <strong style="font-size: 13px; letter-spacing: 0.5px;">
                    <?= strtoupper(date('d M Y', strtotime($sr['mulai_pada']))) ?> • <?= strtoupper($sr['nama_shift']) ?>
                </strong>
                <span
                    class="badge <?= $sr['status'] === 'ditutup' && (int) $sr['selisih'] === 0 ? 'badge-success' : 'badge-warning' ?>">
                    <?= htmlspecialchars($sr['status'] === 'ditutup' && (int) $sr['selisih'] === 0 ? 'Valid' : ($sr['status'] === 'aktif' ? 'Aktif' : 'Review')) ?>
                </span>
            </div>

            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 6px;">
                Kasir: <strong><?= htmlspecialchars($sr['kasir']) ?></strong> | <?= $sr['transaksi'] ?> trx •
                <?= $sr['berat'] ?> kg
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border-color); padding-top: 8px;">
                <div style="font-size: 12px; color: var(--text-muted);">
                    Cash: <?= format_rupiah($sr['pendapatan_cash'] ?? 0) ?> | QRIS: <?= format_rupiah($sr['pendapatan_qris'] ?? 0) ?>
                </div>
                <div style="font-size: 15px; font-weight: 800; color: var(--primary-dark);">
                    <?= format_rupiah($sr['pendapatan']) ?>
                </div>
            </div>

            <?php if ($sr['selisih'] != 0): ?>
                <div
                    style="background: #fee2e2; color: #dc2626; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; margin-top: 8px; display: flex; justify-content: space-between;">
                    <span>⚠️ Selisih Rekonsiliasi:</span>
                    <span><?= format_rupiah($sr['selisih']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>