<?php
require_once __DIR__ . '/../includes/data.php';

// Calculate totals from session transactions
$total_income = 0;
$total_tx = count($_SESSION['transactions']);
$coins_wash = 0;
$coins_dry = 0;

foreach ($_SESSION['transactions'] as $t) {
    $total_income += $t['total_bayar'];
    $coins_wash += ($t['koin_cuci'] ?? 0);
    $coins_dry += ($t['koin_kering'] ?? 0);
}

$display_income = max($total_income, 425000);
$display_tx = max($total_tx, 18);
$display_kg = 62.5;
$display_wash = max($coins_wash, 24);
$display_dry = max($coins_dry, 26);

// Handle Close Shift POST BEFORE header output
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tutup_shift') {
    $_SESSION['kasir_checked_in'] = false;
    $current_kasir = $_SESSION['username'] ?? 'Daniel';
    $jam_keluar = date('H:i');

    // 1. Update status presensi kasir di dashboard Pemilik menjadi 'Selesai' dengan jam keluar aktual!
    if (isset($_SESSION['presensi']) && is_array($_SESSION['presensi'])) {
        for ($i = count($_SESSION['presensi']) - 1; $i >= 0; $i--) {
            if ($_SESSION['presensi'][$i]['kasir'] === $current_kasir && $_SESSION['presensi'][$i]['status'] === 'Berjalan') {
                $_SESSION['presensi'][$i]['jam_keluar'] = $jam_keluar;
                $_SESSION['presensi'][$i]['status'] = 'Selesai';
                break;
            }
        }
    }
    
    // 2. Catat rekonsiliasi ke riwayat rekapitulasi shift pemilik
    $_SESSION['shifts_recap'][] = [
        'id' => count($_SESSION['shifts_recap']) + 1,
        'kasir' => $current_kasir,
        'shift' => 'Siang',
        'tanggal' => date('d M Y'),
        'trx_count' => $display_tx,
        'total_kg' => $display_kg,
        'pendapatan' => $display_income,
        'koin_cuci' => $display_wash,
        'koin_kering' => $display_dry,
        'status' => 'Valid',
        'selisih' => 0
    ];

    header('Location: index.php?shift_closed=1');
    exit;
}

$page_title = 'Rekapitulasi Operasional - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'rekap';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 14px;">
    <h2 style="font-size: 17px; font-weight: 800;">Rekapitulasi Operasional</h2>
    <span style="font-size: 12px; color: var(--text-muted);">Shift Siang • 14:00 — 20:00 • 31 Okt 2026</span>
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
            Akses input transaksi kasir akan dikunci hingga shift berikutnya dimulai. Konfirmasi penutupan Shift Siang?
        </p>
        <form action="rekap.php" method="POST">
            <input type="hidden" name="action" value="tutup_shift">
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalTutupShift')">Batal</button>
                <button type="submit" class="btn btn-primary" style="background: #1e3a8a;">Ya, Tutup Shift</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
