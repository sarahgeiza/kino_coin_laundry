<?php
require_once __DIR__ . '/../includes/data.php';

// Handle Check-in POST BEFORE header output
$current_kasir_name = $_SESSION['username'] ?? 'Daniel';
$kasir_shift = get_active_kasir_shift($current_kasir_name);

if (isset($_POST['action']) && $_POST['action'] === 'mulai_shift') {
    $_SESSION['kasir_checked_in'] = true;
    $_SESSION['presensi'][] = [
        'id' => count($_SESSION['presensi']) + 1,
        'kasir' => $current_kasir_name,
        'shift' => $kasir_shift['nama'],
        'tanggal' => date('d M Y'),
        'filter_date' => date('Y-m-d'),
        'jam_masuk' => date('H:i'),
        'jam_keluar' => null,
        'status' => 'Berjalan'
    ];
    header('Location: index.php?shift_started=1');
    exit;
}

$page_title = 'Dashboard Kasir - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';

// Check if check-in needed (Modal M1)
$need_checkin = empty($_SESSION['kasir_checked_in']);

// Calculate metrics dynamically based on shift baseline + real added transactions
$base_income = 425000;
$base_tx = 18;
$base_wash = 24;
$base_dry = 26;

$initial_count = 4;
$current_tx_list = $_SESSION['transactions'] ?? [];
$current_count = count($current_tx_list);

$extra_income = 0;
$extra_tx = 0;
$extra_wash = 0;
$extra_dry = 0;

if ($current_count > $initial_count) {
    for ($i = $initial_count; $i < $current_count; $i++) {
        $t = $current_tx_list[$i];
        $extra_income += ($t['total_bayar'] ?? $t['total_biaya'] ?? 0);
        $extra_tx += 1;
        $extra_wash += ($t['koin_cuci'] ?? 0);
        $extra_dry += ($t['koin_kering'] ?? 0);
    }
}

$total_live_income = $base_income + $extra_income;
$total_live_tx = $base_tx + $extra_tx;
$total_live_wash = $base_wash + $extra_wash;
$total_live_dry = $base_dry + $extra_dry;

$disp_income = format_rupiah($total_live_income);
$disp_tx = $total_live_tx . ' transaksi';
$disp_wash = $total_live_wash . ' pcs';
$disp_wash_val = format_rupiah($total_live_wash * 7000);
$disp_dry = $total_live_dry . ' pcs';
$disp_dry_val = format_rupiah($total_live_dry * 10000);
?>

<!-- Tombol Aksi Cepat (Persis Figma K1) -->
<div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 10px; margin-bottom: 16px;">
    <a href="transaksi-baru.php" class="btn btn-primary" style="padding: 12px; font-size: 13px;">
        <span>+ Transaksi Baru</span>
    </a>
    <button type="button" class="btn btn-outline" style="padding: 12px; font-size: 13px;" onclick="openModal('modalScanQr')">
        <span><?= get_icon('camera', 16) ?> Scan QR</span>
    </button>
</div>

<!-- Ringkasan Shift (Metrik 3 Box) -->
<div class="card" style="padding: 16px; margin-bottom: 14px;">
    <div class="card-title" style="margin-bottom: 10px;">
        <span>Ringkasan Shift</span>
        <span style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($kasir_shift['nama']) ?></span>
    </div>
    <div class="metrics-row" style="margin-bottom: 0;">
        <div class="metric-box blue">
            <div class="label">Uang Masuk</div>
            <div class="value"><?= $disp_income ?></div>
            <div class="sub"><?= $disp_tx ?></div>
        </div>
        <div class="metric-box orange">
            <div class="label">Coin Cuci</div>
            <div class="value"><?= $disp_wash ?></div>
            <div class="sub">= <?= $disp_wash_val ?></div>
        </div>
        <div class="metric-box amber">
            <div class="label">Coin Kering</div>
            <div class="value"><?= $disp_dry ?></div>
            <div class="sub">= <?= $disp_dry_val ?></div>
        </div>
    </div>
</div>

<!-- Banner Info Operasional Token vs Tunai -->
<div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 10px 14px; font-size: 12px; color: #1e40af; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
    <?= get_icon('info', 18, '#1e40af') ?>
    <span>Pembayaran koin khusus Self Service. Drop off / retail dibayar tunai.</span>
</div>

<!-- Daftar Transaksi Terakhir -->
<div class="card">
    <div class="card-title">
        <span>Transaksi Terakhir</span>
        <a href="transaksi.php" style="font-size: 12px; color: var(--primary-light); text-decoration: none; font-weight: 700;">Lihat Semua →</a>
    </div>

    <?php 
    $recent_txs = array_slice(array_reverse($_SESSION['transactions']), 0, 4);
    foreach ($recent_txs as $trx):
        $is_token = ($trx['metode'] === 'Token');
        $meta_coin = [];
        if (!empty($trx['koin_cuci'])) $meta_coin[] = $trx['koin_cuci'] . ' Coin Cuci';
        if (!empty($trx['koin_kering'])) $meta_coin[] = $trx['koin_kering'] . ' Coin Kering';
        $coin_desc = !empty($meta_coin) ? implode(' + ', $meta_coin) : ($trx['metode'] ?? 'Tunai');
    ?>
    <div class="trx-item" onclick="showInvoiceDetail(<?= htmlspecialchars(json_encode($trx)) ?>)">
        <div class="trx-top">
            <div>
                <span class="trx-id"><?= htmlspecialchars($trx['id']) ?></span>
                <div class="trx-name"><?= htmlspecialchars($trx['pelanggan']) ?></div>
            </div>
            <span class="badge badge-lunas"><?= htmlspecialchars($trx['status']) ?></span>
        </div>
        <div class="trx-meta-line">
            <?= htmlspecialchars($trx['layanan']) ?> • <?= htmlspecialchars($trx['kuantitas']) ?>
        </div>
        <div class="trx-bottom">
            <div>
                <div class="trx-price"><?= format_rupiah($trx['total_bayar']) ?></div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                    <?= htmlspecialchars($trx['waktu']) ?> | Kasir <?= htmlspecialchars($trx['kasir']) ?> | <?= $coin_desc ?>
                </div>
            </div>
            <button type="button" class="trx-details-btn">Detail</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Floating Action Button + (Persis Figma K1) -->
<a href="transaksi-baru.php" class="fab-btn" title="Transaksi Baru">+</a>

<!-- Modal M1: Mulai Shift -->
<div class="sheet-overlay <?= $need_checkin ? 'active' : '' ?>" id="modalMulaiShift">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Mulai Shift</h3>
            <?php if (!$need_checkin): ?>
                <button type="button" class="sheet-close" onclick="closeModal('modalMulaiShift')">&times;</button>
            <?php endif; ?>
        </div>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
            Selamat bekerja, <strong><?= htmlspecialchars($user_name) ?></strong>. Konfirmasi kehadiran untuk membuka akses transaksi kasir.
        </p>

        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px; margin-bottom: 20px; font-size: 13px; line-height: 1.8;">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Shift:</span>
                <strong><?= htmlspecialchars($kasir_shift['nama']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Jadwal Shift:</span>
                <strong><?= htmlspecialchars($kasir_shift['jam_tampil']) ?> WIB</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Jam Sekarang:</span>
                <strong style="color: var(--primary);"><?= date('H:i') ?> WIB</strong>
            </div>
        </div>

        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="mulai_shift">
            <button type="submit" class="btn btn-primary" style="padding: 14px;">
                Mulai Shift &amp; Presensi
            </button>
        </form>
    </div>
</div>

<!-- Modal M2 / M3: Detail Transaksi -->
<div class="sheet-overlay" id="modalTrxDetail">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <div>
                <span style="font-size: 11px; font-weight: 800; color: var(--primary);" id="detailInvoiceId">INV-2026-001</span>
                <h3 class="sheet-title" id="detailCustomerName">Budi Santoso</h3>
            </div>
            <button type="button" class="sheet-close" onclick="closeModal('modalTrxDetail')">&times;</button>
        </div>
        
        <div id="detailContentBody" style="font-size: 13px; line-height: 1.6;"></div>

        <button type="button" class="btn btn-outline" style="margin-top: 18px;" onclick="closeModal('modalTrxDetail')">
            Tutup
        </button>
    </div>
</div>

<!-- Modal M1a-c: Scan QR Kartu Pelanggan (Live Webcam + Fallback Input Manual M1c) -->
<div class="sheet-overlay" id="modalScanQr">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Scan QR Kartu Pelanggan</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalScanQr')">&times;</button>
        </div>

        <div id="qrViewfinder" style="width: 100%; border: 2.5px dashed #ea580c; border-radius: 16px; background: rgba(234, 88, 12, 0.05); display: flex; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 12px; padding: 12px; position: relative; overflow: hidden; min-height: 180px;">
            <div id="cameraReader" style="width: 100%; max-width: 240px; margin: 0 auto;"></div>
            <div id="qrStatusText" style="font-size: 12px; font-weight: 700; color: #ea580c; text-align: center; margin-top: 8px;">
                Arahkan QR ke kamera...
            </div>
        </div>

        <div style="margin-bottom: 12px;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pindai Cepat (Simulasi Kode Member):</div>
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <button type="button" class="chip" style="font-size: 11px; padding: 4px 10px;" onclick="simulateQrScanSuccess('KC-P-0001')">KC-P-0001 (Budi)</button>
                <button type="button" class="chip" style="font-size: 11px; padding: 4px 10px;" onclick="simulateQrScanSuccess('KC-P-0002')">KC-P-0002 (Sari)</button>
                <button type="button" class="chip" style="font-size: 11px; padding: 4px 10px;" onclick="simulateQrScanSuccess('KC-P-0003')">KC-P-0003 (Andi)</button>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Atau ketik kode kartu manual (mis. KC-P-0001):</label>
            <input type="text" id="qrManualInput" class="form-control" placeholder="Contoh: KC-P-0001" value="">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 14px;">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalScanQr')">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="applyScannedCode()">Gunakan Kode</button>
        </div>
    </div>
</div>

<script>
function showInvoiceDetail(trx) {
    document.getElementById('detailInvoiceId').textContent = trx.id;
    document.getElementById('detailCustomerName').textContent = trx.pelanggan;

    const isToken = (trx.metode === 'Token');
    let paymentDetail = '';

    if (isToken) {
        paymentDetail = `
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                <div style="font-size: 11px; font-weight: 800; color: #1e40af; text-transform: uppercase; margin-bottom: 6px;">
                    Rincian Token Dibayar
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Coin Cuci (${trx.koin_cuci || 0} pcs):</span>
                    <strong>Rp ${((trx.koin_cuci || 0) * 7000).toLocaleString('id-ID')}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Coin Kering (${trx.koin_kering || 0} pcs):</span>
                    <strong>Rp ${((trx.koin_kering || 0) * 10000).toLocaleString('id-ID')}</strong>
                </div>
            </div>
        `;
    } else {
        paymentDetail = `
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; margin-bottom: 12px; color: #15803d;">
                <strong>Metode Bayar: Tunai / Transfer (Non-Token)</strong>
                <p style="font-size: 11px; margin-top: 2px;">Diterima lunas di meja kasir tanpa penggunaan koin mesin.</p>
            </div>
        `;
    }

    const html = `
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
            <p><strong>Layanan:</strong> ${trx.layanan}</p>
            <p><strong>Kuantitas:</strong> ${trx.kuantitas}</p>
            <p><strong>WhatsApp:</strong> ${trx.wa || '-'}</p>
            <p><strong>Waktu:</strong> ${trx.tanggal} • ${trx.waktu}</p>
            <p><strong>Kasir:</strong> ${trx.kasir} (${trx.shift})</p>
        </div>
        ${paymentDetail}
        <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 900; padding: 10px 0; border-top: 1px dashed #e2e8f0;">
            <span>Total Tagihan:</span>
            <span>Rp ${(trx.total_biaya || 0).toLocaleString('id-ID')}</span>
        </div>
    `;

    document.getElementById('detailContentBody').innerHTML = html;
    openModal('modalTrxDetail');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
