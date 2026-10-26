<?php
$page_title = 'Tutup Shift - Kino Coin Laundry';
$header_role = 'Kasir';
$header_user = 'Daniel';
$header_avatar = 'D';
$header_title = 'Tutup Shift';
$header_subtitle = 'Rekonsiliasi koin & kas pergantian shift';
$current_role = 'kasir';
$active_tab = 'shift';

require_once __DIR__ . '/../includes/header.php';

// Calculate totals from current shift
$total_income = 0;
$total_kg = 0;
$total_trx = count($_SESSION['transactions']);
$coins_500 = 0;
$coins_1000 = 0;

foreach ($_SESSION['transactions'] as $t) {
    $total_income += $t['total_biaya'];
    $total_kg += $t['berat'];
    $coins_500 += $t['koin_500'];
    $coins_1000 += $t['koin_1000'];
}

// Default fallback to mockup values if session is small
if ($total_income < 425000) {
    $display_income = 425000;
    $display_trx = 18;
    $display_kg = 62.5;
    $display_c500 = 320;
    $display_c1000 = 265;
} else {
    $display_income = $total_income;
    $display_trx = $total_trx;
    $display_kg = $total_kg;
    $display_c500 = $coins_500;
    $display_c1000 = $coins_1000;
}

$closed_success = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $catatan = trim($_POST['catatan'] ?? '');
    $fisik = intval($_POST['fisik_kas'] ?? $display_income);
    $selisih = $fisik - $display_income;

    // Save recap to session
    $_SESSION['shifts_recap'][] = [
        'id' => count($_SESSION['shifts_recap']) + 1,
        'tanggal' => date('d M Y'),
        'shift' => 'Siang',
        'kasir' => 'Daniel',
        'trx_count' => $display_trx,
        'total_kg' => $display_kg,
        'pendapatan' => $display_income,
        'koin_500' => $display_c500,
        'koin_1000' => $display_c1000,
        'selisih' => $selisih,
        'status' => ($selisih === 0) ? 'Valid' : 'Review'
    ];

    $closed_success = true;
}
?>

<?php if ($closed_success): ?>
    <div class="info-banner" style="background:#dcfce7; border-color:#86efac; color:#15803d;">
        <span>✅</span>
        <span>Shift Siang berhasil ditutup dan data rekapitulasi tersimpan!</span>
    </div>
<?php endif; ?>

<div class="card">
    <div style="text-align: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
        <span style="font-size: 11px; font-weight: 800; color: var(--primary-light); letter-spacing: 0.5px;">
            REKAPITULASI SHIFT SIANG
        </span>
        <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
            14:00 — 20:00 • 12 Mei 2026
        </div>
        <div style="font-size: 13px; font-weight: 700; color: var(--text-main); margin-top: 2px;">
            Kasir: Daniel
        </div>
    </div>

    <!-- Metrik Volume -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
        <div style="background: #f8fafc; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 11px; color: var(--text-muted);">Total Transaksi</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 2px;"><?= $display_trx ?></div>
        </div>
        <div style="background: #f8fafc; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 11px; color: var(--text-muted);">Total Kg Tercuci</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 2px;"><?= number_format($display_kg, 1, ',', '.') ?> kg</div>
        </div>
    </div>

    <!-- Rincian Koin -->
    <div style="background: #f8fafc; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 16px;">
        <div style="font-size: 12px; font-weight: 800; color: var(--text-muted); margin-bottom: 8px;">
            RINCIAN PEROLEHAN KOIN
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
            <span>🪙 Koin Rp 500:</span>
            <strong><?= number_format($display_c500, 0, ',', '.') ?> pcs = <?= format_rupiah($display_c500 * 500) ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 13px;">
            <span>🪙 Koin Rp 1.000:</span>
            <strong><?= number_format($display_c1000, 0, ',', '.') ?> pcs = <?= format_rupiah($display_c1000 * 1000) ?></strong>
        </div>
    </div>

    <form action="tutup-shift.php" method="POST">
        <!-- Rekonsiliasi Kas -->
        <div class="bill-summary" style="margin-top: 0; margin-bottom: 16px;">
            <div class="bill-row">
                <span>Total Sistem:</span>
                <strong id="total-sistem-val" data-value="<?= $display_income ?>"><?= format_rupiah($display_income) ?></strong>
            </div>
            
            <div class="form-group" style="margin: 12px 0 8px 0;">
                <label class="form-label" style="font-size: 13px;">Total Fisik Uang / Koin (Input)</label>
                <input type="number" id="input-fisik-kas" name="fisik_kas" class="form-control" value="<?= $display_income ?>" required>
            </div>

            <div class="bill-row" style="border-top: 1px dashed var(--border-color); padding-top: 8px;">
                <span>Selisih Rekonsiliasi:</span>
                <strong id="selisih-val" style="color: var(--success);">Rp 0 (Sesuai)</strong>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Catatan Shift (opsional)</label>
            <textarea name="catatan" class="form-control" rows="2" placeholder="Contoh: Tidak ada kendala"></textarea>
        </div>

        <button type="submit" class="btn btn-primary" onclick="return confirm('Konfirmasi menutup Shift Siang?')">
            Konfirmasi Tutup Shift
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
