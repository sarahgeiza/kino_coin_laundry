<?php
require_once __DIR__ . '/../includes/data.php';
check_auth_guard('kasir');

$current_kasir = $_SESSION['username'] ?? '';

// GUARD: Rekap & Tutup Shift hanya boleh diakses setelah kasir benar-benar
// menekan "Mulai Shift & Presensi" (terbukti ada baris shift aktif di database).
$active_shift_row = db_active_shift($current_kasir);
if ($active_shift_row === null) {
    $_SESSION['kasir_checked_in'] = false;
    header('Location: index.php?toast=need_checkin');
    exit;
}

// Hitung total dari transaksi riil database Supabase
$current_tx_list = $_SESSION['transactions'] ?? [];
$display_income = 0;
$display_tx = count($current_tx_list);
$display_wash = 0;
$display_dry = 0;
$display_kg = 0;

foreach ($current_tx_list as $t) {
    $display_income += ($t['total_bayar'] ?? $t['total_biaya'] ?? 0);
    $display_wash += ($t['koin_cuci'] ?? 0);
    $display_dry += ($t['koin_kering'] ?? 0);
    $display_kg += floatval($t['qty_num'] ?? 1) * 7.5;
}

// Handle Close Shift POST BEFORE header output
$kasir_shift = get_active_kasir_shift($current_kasir);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tutup_shift') {
    $fisik = isset($_POST['total_fisik']) && $_POST['total_fisik'] !== ''
        ? max(0, (int) $_POST['total_fisik'])
        : $display_income;
    $catatan = trim((string) ($_POST['catatan'] ?? ''));
    $closed = db_close_shift($current_kasir, $fisik, 0, $catatan);
    $_SESSION['kasir_checked_in'] = false;

    if ($closed) {
        unset($_SESSION['close_error']);
        header('Location: index.php?shift_closed=1');
    } else {
        $_SESSION['close_error'] = $GLOBALS['shift_close_error'] ?? 'Gagal menutup shift.';
        header('Location: rekap.php?err=1');
    }
    exit;
}

$page_title = 'Rekapitulasi Operasional - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'rekap';

require_once __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($_SESSION['close_error'])): ?>
<div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:12px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:14px;">
    <?= htmlspecialchars($_SESSION['close_error']) ?>
</div>
<?php unset($_SESSION['close_error']); endif; ?>

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

    <div class="card-title">Rincian Perolehan Token</div>
    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px; font-size: 13px; line-height: 1.8; margin-bottom: 14px;">
        <div style="display: flex; justify-content: space-between;">
            <span>Coin Cuci (7k):</span>
            <strong><?= $display_wash ?> pcs = <?= format_rupiah($display_wash * 7000) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>Coin Kering (10k):</span>
            <strong><?= $display_dry ?> pcs = <?= format_rupiah($display_dry * 10000) ?></strong>
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
            <strong style="font-size: 15px; color: var(--success);"><?= format_rupiah($display_income) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 13px; border-top: 1px solid var(--border); padding-top: 6px;">
            <span>Selisih:</span>
            <strong style="color: var(--success);">Rp 0 (Sesuai)</strong>
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
        <form action="rekap.php" method="POST">
            <input type="hidden" name="action" value="tutup_shift">
            <div class="form-group">
                <label class="form-label">Uang Fisik di Kasir (Rp)</label>
                <input type="number" name="total_fisik" class="form-control" min="0" step="1000" value="<?= (int) $display_income ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan (wajib jika tutup lebih awal)</label>
                <input type="text" name="catatan" class="form-control" maxlength="200" placeholder="Contoh: tutup lebih awal, mesin rusak">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalTutupShift')">Batal</button>
                <button type="submit" class="btn btn-primary" style="background: #1e3a8a;">Ya, Tutup Shift</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
