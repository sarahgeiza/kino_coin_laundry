<?php
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
check_auth_guard('kasir');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    respond(['error' => 'Kirim penutupan shift melalui endpoint API.'], 405);
}

$kasirId = (int)$_SESSION['user_id'];
$activeShift = one(
    'SELECT so.id_shift_operasional, s.nama_shift, s.jam_mulai, s.jam_selesai
     FROM shift_operasional so
     JOIN shift s USING (id_shift)
     WHERE so.id_kasir = ? AND so.status = ?
     ORDER BY so.id_shift_operasional DESC
     LIMIT 1',
    [$kasirId, 'aktif']
);

if (!$activeShift) {
    $_SESSION['kasir_checked_in'] = false;
    header('Location: index.php?toast=need_checkin');
    exit;
}

$summary = one(
    'SELECT COUNT(t.id_transaksi)::int AS total_transaksi,
            COALESCE(SUM(t.jumlah_dibayar - t.uang_kembalian) FILTER (
                WHERE t.jenis_pembayaran = \'cash\'
            ), 0)::bigint AS total_sistem,
            COALESCE(SUM(d.total_berat), 0)::numeric AS total_berat,
            COALESCE(SUM(d.total_koin), 0)::bigint AS total_koin_mesin
     FROM transaksi t
     LEFT JOIN (
        SELECT id_transaksi,
               SUM(COALESCE(berat, 0)) AS total_berat,
               SUM(COALESCE(jumlah_koin, 0)) AS total_koin
        FROM detail_transaksi
        GROUP BY id_transaksi
     ) d USING (id_transaksi)
     WHERE t.id_shift_operasional = ?',
    [(int)$activeShift['id_shift_operasional']]
);

$display_income = (int)($summary['total_sistem'] ?? 0);
$display_tx = (int)($summary['total_transaksi'] ?? 0);
$display_kg = (float)($summary['total_berat'] ?? 0);
$display_coins = (int)($summary['total_koin_mesin'] ?? 0);
$kasir_shift = [
    'nama' => $activeShift['nama_shift'],
    'jam_tampil' => substr((string)$activeShift['jam_mulai'], 0, 5)
        . ' — ' . substr((string)$activeShift['jam_selesai'], 0, 5)
];

$page_title = 'Rekapitulasi Operasional - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'rekap';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; font-weight: 800;">Rekapitulasi Operasional</h2>
    <span style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($kasir_shift['nama']) ?> • <?= htmlspecialchars($kasir_shift['jam_tampil']) ?> • <?= date('d M Y') ?></span>
</div>

<div class="card">
    <div class="card-title">Volume Cucian Shift</div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
        <div style="background: #f8fafc; border: 1px solid var(--border); padding: 12px; border-radius: var(--radius-md); text-align: center;">
            <div style="font-size: 11px; color: var(--text-muted);">Total Transaksi</div>
            <div style="font-size: 22px; font-weight: 900; color: var(--text-main); margin-top: 2px;"><?= $display_tx ?></div>
        </div>
        <div style="background: #f8fafc; border: 1px solid var(--border); padding: 12px; border-radius: var(--radius-md); text-align: center;">
            <div style="font-size: 11px; color: var(--text-muted);">Total Berat Cuci</div>
            <div style="font-size: 22px; font-weight: 900; color: var(--text-main); margin-top: 2px;"><?= $display_kg ?> kg</div>
        </div>
    </div>

    <div class="card-title">Rincian Koin Mesin</div>
    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px; font-size: 13px; line-height: 1.8; margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between;">
            <span>Total koin mesin:</span>
            <strong><?= $display_coins ?> pcs</strong>
        </div>
    </div>

    <!-- Rekonsiliasi Kas -->
    <div class="card-title">Rekonsiliasi Kas Operasional</div>
    <div style="background: #f8fafc; border: 1.5px dashed var(--border); border-radius: var(--radius-md); padding: 14px; margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
            <span>Total Sistem:</span>
            <strong style="font-size: 15px; color: var(--primary-dark);"><?= format_rupiah($display_income) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
            <span>Total Fisik Kas:</span>
            <strong id="physicalCashDisplay" style="font-size: 15px; color: var(--success);">Belum diinput</strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 13px; border-top: 1px solid var(--border); padding-top: 6px;">
            <span>Selisih:</span>
            <strong id="cashDifferenceDisplay" style="color: var(--text-muted);">Belum dihitung</strong>
        </div>
    </div>

    <button type="button" class="btn btn-primary" style="background: #1e3a8a;" onclick="openModal('modalTutupShift')">
        Tutup Shift
    </button>
</div>

<!-- Modal M4: Konfirmasi Tutup Shift -->
<div class="sheet-overlay" id="modalTutupShift">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Tutup Shift</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalTutupShift')">&times;</button>
        </div>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
            Akses input transaksi kasir akan dikunci hingga shift berikutnya dimulai. Konfirmasi penutupan <?= htmlspecialchars($kasir_shift['nama']) ?>?
        </p>
        <form action="rekap.php" method="POST" id="formTutupShift"
            data-total-sistem="<?= $display_income ?>">
            <input type="hidden" name="action" value="tutup_shift">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <div class="form-group">
                <label class="form-label" for="totalFisikInput">Total uang fisik (Rp) *</label>
                <input type="number" min="0" step="1" id="totalFisikInput" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="catatanShiftInput">Catatan (opsional)</label>
                <textarea id="catatanShiftInput" class="form-control" maxlength="1000" rows="3"></textarea>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalTutupShift')">Batal</button>
                <button type="submit" class="btn btn-primary" style="background: #1e3a8a;">Ya, Tutup Shift</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
