<?php
$page_title = 'Presensi Kasir - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'presensi';

require_once __DIR__ . '/../includes/header.php';

$filter = $_GET['filter'] ?? 'Hari Ini';
$dateCondition = match ($filter) {
    '7 Hari' => "so.mulai_pada >= CURRENT_DATE - INTERVAL '6 days'",
    'Bulan Ini' => "so.mulai_pada >= DATE_TRUNC('month', CURRENT_DATE)",
    'Bulan Lalu' => "so.mulai_pada >= DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month')
                     AND so.mulai_pada < DATE_TRUNC('month', CURRENT_DATE)",
    default => 'so.mulai_pada >= CURRENT_DATE',
};
$logs = rows(
    "SELECT so.id_shift_operasional AS id,
            p.nama AS kasir,
            s.nama_shift AS shift,
            TO_CHAR(so.mulai_pada, 'YYYY-MM-DD') AS filter_date,
            TO_CHAR(so.mulai_pada, 'DD Mon YYYY') AS tanggal,
            TO_CHAR(so.mulai_pada, 'HH24:MI') AS jam_masuk,
            CASE WHEN so.ditutup_pada IS NULL THEN NULL
                 ELSE TO_CHAR(so.ditutup_pada, 'HH24:MI') END AS jam_keluar,
            CASE WHEN so.status = 'aktif' THEN 'Berjalan' ELSE 'Selesai' END AS status
     FROM shift_operasional so
     JOIN pengguna p ON p.id_pengguna = so.id_kasir
     JOIN shift s USING (id_shift)
     WHERE $dateCondition
     ORDER BY so.mulai_pada DESC
     LIMIT 500"
);
?>
<script>window.__PRESENSI_LOGS = <?= json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <h2 style="font-size: 16px; font-weight: 800;">Presensi Kasir</h2>
        <span style="font-size: 11px; color: var(--text-muted);">Riwayat log jam kerja staf</span>
    </div>
    <button type="button" class="btn btn-outline" style="width: auto; padding: 6px 12px; font-size: 12px;" onclick="exportPresensiCSV()">
        <?= get_icon('export', 14) ?> Export
    </button>
</div>

<!-- Filter Periode (P6, P6b) -->
<div class="filter-scroll">
    <a href="presensi.php?filter=Hari+Ini" class="chip <?= $filter === 'Hari Ini' ? 'active' : '' ?>">Hari Ini</a>
    <a href="presensi.php?filter=7+Hari" class="chip <?= $filter === '7 Hari' ? 'active' : '' ?>">7 Hari</a>
    <a href="presensi.php?filter=Bulan+Ini" class="chip <?= $filter === 'Bulan Ini' ? 'active' : '' ?>">Bulan Ini</a>
    <a href="presensi.php?filter=Bulan+Lalu" class="chip <?= $filter === 'Bulan Lalu' ? 'active' : '' ?>">Bulan Lalu</a>
</div>

<?php if (empty($logs)): ?>
    <!-- Empty State (P6b) -->
    <div class="card" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
        <div style="margin-bottom: 8px;"><?= get_icon("clock", 36, "#94a3b8") ?></div>
        <strong style="font-size: 14px; color: var(--text-main);">Tidak ada data presensi di periode ini</strong>
        <p style="font-size: 12px; margin-top: 4px;">Pilih filter lain untuk melihat log absensi kasir.</p>
    </div>
<?php else: ?>
    <?php foreach ($logs as $p): ?>
    <div class="card" style="margin-bottom: 10px; padding: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <div>
                <strong style="font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($p['kasir']) ?></strong>
                <span style="font-size: 11px; color: var(--text-muted); margin-left: 6px;">— <?= htmlspecialchars($p['shift']) ?></span>
            </div>
            <span class="badge <?= $p['status'] === 'Berjalan' ? 'badge-proses' : 'badge-lunas' ?>">
                <?= $p['status'] ?>
            </span>
        </div>
        <div style="font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between; border-top: 1px dashed var(--border); padding-top: 8px; margin-top: 4px;">
            <span>Tanggal: <strong><?= $p['tanggal'] ?></strong></span>
            <span>Masuk: <strong><?= $p['jam_masuk'] ?></strong> | Keluar: <strong><?= $p['jam_keluar'] ?? '—' ?></strong></span>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
