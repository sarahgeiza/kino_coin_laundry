<?php
require_once __DIR__ . '/../includes/data.php';
check_auth_guard('kasir');

// Wajib check-in presensi terlebih dahulu sebelum mencatat transaksi kasir.
// Sumber kebenaran: ada/tidaknya baris shift_operasional berstatus 'aktif' di Supabase.
if (db_active_shift($_SESSION['username'] ?? '') === null) {
    $_SESSION['kasir_checked_in'] = false;
    header('Location: index.php?toast=need_checkin');
    exit;
}



$trx_error = '';

// Handle tambah pelanggan cepat dari modal form transaksi (langsung ke database)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'quick_member') {
    $qm_nama = trim($_POST['quick_nama'] ?? '');
    $qm_wa = trim($_POST['quick_wa'] ?? '');

    if ($qm_nama === '' || !preg_match('/^[0-9]{10,15}$/', $qm_wa)) {
        header('Location: transaksi-baru.php?toast=member_invalid');
        exit;
    }

    // Sudah terdaftar? Pakai data lama supaya tidak dobel
    $existing = null;
    foreach ($_SESSION['members'] ?? [] as $m) {
        if (($m['wa'] ?? '') === $qm_wa) { $existing = $m; break; }
    }

    if ($existing) {
        header('Location: transaksi-baru.php?scan_member=' . urlencode($existing['kode_kartu']) . '&toast=member_exists');
        exit;
    }

    $saved_member = db_save_member($qm_nama, $qm_wa);
    if ($saved_member) {
        header('Location: transaksi-baru.php?scan_member=' . urlencode($saved_member['kode_kartu']) . '&toast=member_saved');
    } else {
        header('Location: transaksi-baru.php?toast=member_failed');
    }
    exit;
}

// Handle Save Transaction POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_trx') {
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $wa = trim($_POST['wa_pelanggan'] ?? '');
    
    if (empty($nama)) {
        $trx_error = 'Nama pelanggan wajib diisi.';
    } elseif (empty($wa)) {
        $trx_error = 'Nomor WhatsApp wajib diisi.';
    } elseif (!preg_match('/^[0-9]+$/', $wa)) {
        $trx_error = 'Nomor WhatsApp hanya boleh berisi angka.';
    } elseif (strlen($wa) < 10 || strlen($wa) > 15) {
        $trx_error = 'Nomor WhatsApp tidak valid (minimal 10 digit, maksimal 15 digit).';
    }
    if (empty($trx_error)) {
    $service_id = intval($_POST['service_id'] ?? 1);
    $qty_raw = (float) ($_POST['qty'] ?? 1);
    $koin_cuci = intval($_POST['koin_cuci'] ?? 0);
    $koin_kering = intval($_POST['koin_kering'] ?? 0);

    // Find selected service
    $selected_service = null;
    foreach ($_SESSION['services'] as $s) {
        if ($s['id'] === $service_id) {
            $selected_service = $s;
            break;
        }
    }
    if (!$selected_service) $selected_service = $_SESSION['services'][0];

    // Mode harga layanan:
    //  block  = per blok 7,5 kg (1 putaran mesin), berat dibulatkan ke atas
    //  per_kg = linear per kg (Lipat)
    //  unit   = per unit (Extra Spin, Extra Dry)
    $nama_layanan = strtolower(trim($selected_service['nama']));
    $block_services = ['cuci', 'kering', 'cuci kering', 'cuci + kering', 'cuci kering drop off'];
    $linear_services = ['lipat'];

    if (in_array($nama_layanan, $linear_services, true)) {
        $pricing_mode = 'per_kg';
    } elseif (in_array($nama_layanan, $block_services, true)) {
        $pricing_mode = 'block';
    } else {
        $pricing_mode = 'unit';
    }

    // Validasi kuantitas: tidak boleh 0 / negatif / bukan angka
    if (!is_numeric($_POST['qty'] ?? '') || $qty_raw <= 0) {
        $trx_error = $pricing_mode === 'unit'
            ? 'Kuantitas harus lebih dari 0.'
            : 'Berat cucian harus lebih dari 0 kg.';
        $berat = 0;
        $qty = 0;
        $putaran = 0;
        $biaya_layanan = 0;
    } elseif ($pricing_mode === 'block') {
        $berat = $qty_raw;
        $putaran = max(1, (int) ceil($berat / 7.5));
        $qty = $putaran;
        $biaya_layanan = (int) $selected_service['tarif'] * $putaran;
    } elseif ($pricing_mode === 'per_kg') {
        $berat = $qty_raw;
        $qty = 1;
        $putaran = 0;
        $biaya_layanan = (int) round($selected_service['tarif'] * $berat);
    } else {
        $berat = 7.5;
        $qty = max(1, (int) round($qty_raw));
        $putaran = $qty;
        $biaya_layanan = (int) $selected_service['tarif'] * $qty;
    }

    // Layanan tambahan (sabun/softener/kresek) menambah tagihan, bukan mengganti layanan
    $addon_rows = [];
    $addon_total = 0;
    foreach (($_POST['addon'] ?? []) as $aid => $aqty) {
        $aid = (int) $aid;
        $aqty = (int) $aqty;
        if ($aqty <= 0) continue;
        foreach ($_SESSION['services'] as $svc) {
            if ((int) $svc['id'] === $aid && $svc['kategori'] === 'ADDITIONAL') {
                $addon_rows[] = [
                    'id' => $aid,
                    'nama' => $svc['nama'],
                    'tarif' => (int) $svc['tarif'],
                    'qty' => $aqty,
                    'subtotal' => (int) $svc['tarif'] * $aqty,
                ];
                $addon_total += (int) $svc['tarif'] * $aqty;
                break;
            }
        }
    }

    $total_biaya = $biaya_layanan + $addon_total;
    $is_token = ($selected_service['kategori'] === 'SELF_SERVICE');

    if ($is_token) {
        // Koin selalu dihitung sistem dari layanan + jumlah putaran.
        // Input koin di form hanya tampilan, bukan sumber tagihan.
        $putaranKoin = $pricing_mode === 'block' ? $putaran : $qty;
        $koin_cuci = (strpos($nama_layanan, 'cuci') !== false) ? $putaranKoin : 0;
        $koin_kering = (strpos($nama_layanan, 'kering') !== false) ? $putaranKoin : 0;
        $total_bayar = $total_biaya;
        $metode = 'Token';
        $status = 'Lunas';
    } else {
        $total_bayar = $total_biaya;
        $metode = 'Tunai';
        $status = 'Lunas';
    }

    if (!empty($trx_error)) {
        // Validasi gagal: jangan simpan apa pun
    } else {
        // 1. Simpan lewat controller backend
    $tx_data = [
        'nama' => $nama,
        'wa' => $wa,
        'service_id' => $service_id,
        'qty' => $qty,
        'putaran' => $putaran,
        'berat' => $berat,
        'subtotal_main' => $biaya_layanan,
        'pricing_mode' => $pricing_mode,
        'koin_cuci' => $koin_cuci,
        'koin_kering' => $koin_kering,
        'addons' => $addon_rows,
        'tarif' => $selected_service['tarif'] ?? 7000,
        'total_biaya' => $total_biaya,
        'total_bayar' => $total_bayar,
        'metode' => 'cash'
    ];
    $saved_inv = db_save_transaction($tx_data);

    // 2. Gagal simpan: tampilkan pesan error, jangan berpura-pura sukses
    if (!$saved_inv) {
        $db_err = $GLOBALS['kino_last_db_error'] ?? '';
        $trx_error = 'Transaksi GAGAL tersimpan ke database'
            . ($db_err !== '' ? ': ' . $db_err : '. Periksa koneksi database.');
    } else {
        header('Location: index.php?toast=saved');
        exit;
    }
    }
    }
}

// Cek apakah ada scan member dari dashboard kasir
$scan_code = trim($_GET['scan_member'] ?? '');
$prefilled_member = null;
if (!empty($scan_code)) {
    foreach ($_SESSION['members'] as $m) {
        if (strcasecmp($m['kode_kartu'], $scan_code) === 0) {
            $prefilled_member = $m;
            break;
        }
    }
}

$page_title = 'Transaksi Baru - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'transaksi';

require_once __DIR__ . '/../includes/header.php';

?>

<!-- Header Meta Sub (Persis Figma K2) -->
<div style="margin-bottom: 12px; font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center;">
    <div>
        <strong style="color: var(--text-main);">Transaksi Baru</strong>
        <span>• Kasir: <?= htmlspecialchars($user_name) ?></span>
    </div>
    <span style="font-size: 11px;"><?= date('d/m/Y H.i.s') ?></span>
</div>

<form action="transaksi-baru.php" method="POST" id="formTransaksiBaru">
    <input type="hidden" name="action" value="save_trx">

    <?php if (!empty($trx_error)): ?>
    <div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:12px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:14px;">
        <?= htmlspecialchars($trx_error) ?>
    </div>
    <?php endif; ?>

    <!-- Card 1: Data Pelanggan -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <label class="form-label" style="margin-bottom: 0;">Pelanggan *</label>
            <div style="display: flex; gap: 6px;">
                <button type="button" class="btn btn-outline" style="width: auto; padding: 4px 10px; font-size: 11px;" onclick="openModal('modalScanQr')">
                    <?= get_icon('camera', 14) ?> Scan Kartu
                </button>
                <button type="button" class="btn btn-outline" style="width: auto; padding: 4px 10px; font-size: 11px;" onclick="openModal('modalNewMember')">
                    + Pelanggan Baru
                </button>
            </div>
        </div>

        <!-- Selected Member Alert (K2c) -->
        <div id="memberSelectedBadge" style="display: <?= $prefilled_member ? 'block' : 'none' ?>; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 8px 12px; border-radius: 8px; font-size: 12px; margin-bottom: 10px;">
            <?= $prefilled_member ? 'Member Terpilih: <strong>' . htmlspecialchars($prefilled_member['nama']) . '</strong> (' . htmlspecialchars($prefilled_member['kode_kartu']) . ')' : '' ?>
        </div>

        <div style="position: relative;">
            <input type="text" id="inputPelangganNama" name="nama_pelanggan" class="form-control" placeholder="Ketik nama / WA / scan kartu..." value="<?= htmlspecialchars($prefilled_member['nama'] ?? ($_POST['nama_pelanggan'] ?? '')) ?>" autocomplete="off" required>
            <!-- Dropdown Autocomplete (K2b) -->
            <div id="customerSuggestBox" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1.5px solid var(--primary-light); border-radius: 8px; box-shadow: var(--shadow-lg); z-index: 30; max-height: 180px; overflow-y: auto;"></div>
        </div>

        <div class="form-group" style="margin-top: 10px; margin-bottom: 0;">
            <label class="form-label">Nomor WhatsApp *</label>
            <input type="text" id="inputPelangganWA" name="wa_pelanggan" class="form-control" value="<?= htmlspecialchars($prefilled_member['wa'] ?? ($_POST['wa_pelanggan'] ?? '')) ?>" placeholder="08123456789" inputmode="numeric" pattern="[0-9]+" title="Nomor WhatsApp hanya boleh berisi angka" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
        </div>
    </div>

    <!-- Card 2: Pemilihan Layanan -->
    <div class="card">
        <label class="form-label">Pilih Cepat Layanan</label>
        <div class="filter-scroll" id="quickServiceChips" style="margin-bottom: 10px;">
            <button type="button" class="chip" data-category="cuci" onclick="selectServiceByCategory('cuci', this)">Cuci</button>
            <button type="button" class="chip" data-category="kering" onclick="selectServiceByCategory('kering', this)">Kering</button>
            <button type="button" class="chip" data-category="cuci kering" onclick="selectServiceByCategory('cuci kering', this)">Cuci + Kering</button>
            <button type="button" class="chip" data-category="drop off" onclick="selectServiceByCategory('drop off', this)">Drop Off</button>
            <button type="button" class="chip" data-category="lipat" onclick="selectServiceByCategory('lipat', this)">Lipat</button>
        </div>

        <div class="form-group">
            <label class="form-label">Paket Layanan *</label>
            <select name="service_id" id="serviceSelect" class="form-control" required>
                <?php foreach ($_SESSION['services'] as $s): ?>
                    <?php if ($s['kategori'] === 'ADDITIONAL') continue; ?>
                    <option value="<?= $s['id'] ?>" data-price="<?= $s['tarif'] ?>" data-category="<?= $s['kategori'] ?>" data-unit="<?= htmlspecialchars($s['satuan']) ?>" <?= ($s['id'] === 3 ? 'selected' : '') ?>>
                        <?= htmlspecialchars($s['nama']) ?> — <?= format_rupiah($s['tarif']) ?> (<?= $s['satuan'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Counter Kuantitas -->
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Kuantitas (<span id="qtyUnitLabel">putaran</span>) *</label>
            <div style="display: flex; align-items: center; gap: 10px;">
                <button type="button" class="btn btn-outline" style="width: 44px; height: 44px; padding: 0; font-size: 20px;" onclick="changeQty(-1)">-</button>
                <input type="number" min="0.5" step="0.5" id="qtyInput" name="qty" class="form-control" value="1" style="text-align: center; font-size: 16px; font-weight: 800;" required>
                <button type="button" class="btn btn-outline" style="width: 44px; height: 44px; padding: 0; font-size: 20px;" onclick="changeQty(1)">+</button>
            </div>
            <input type="hidden" id="cyclesInput" name="putaran" value="1">
            <div id="putaranHint" style="display: none; margin-top: 8px; font-size: 11px; color: #1d4ed8; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 7px 10px;"></div>
        </div>

        <!-- Layanan Tambahan (ADDITIONAL) -->
        <?php $addon_services = array_values(array_filter($_SESSION['services'], fn($s) => $s['kategori'] === 'ADDITIONAL')); ?>
        <?php if (!empty($addon_services)): ?>
        <div style="margin-top: 18px; padding-top: 16px; border-top: 1px dashed var(--border-color, #e2e8f0);">
            <label class="form-label" style="margin-bottom: 10px;">Layanan Tambahan (opsional)</label>
            <?php foreach ($addon_services as $a): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f1f5f9;">
                <div>
                    <div style="font-size: 13px; font-weight: 700;"><?= htmlspecialchars($a['nama']) ?></div>
                    <div style="font-size: 11px; color: var(--text-muted);"><?= format_rupiah($a['tarif']) ?> / <?= htmlspecialchars($a['satuan']) ?></div>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <button type="button" class="btn btn-outline" style="width: 34px; height: 34px; padding: 0; font-size: 16px;" onclick="changeAddon(<?= $a['id'] ?>, -1)">-</button>
                    <input type="number" min="0" step="1" id="addonInput<?= $a['id'] ?>" name="addon[<?= $a['id'] ?>]" class="form-control" value="0" style="width: 52px; text-align: center; font-weight: 800; padding: 6px;" oninput="clampAddon(this)">
                    <button type="button" class="btn btn-outline" style="width: 34px; height: 34px; padding: 0; font-size: 16px;" onclick="changeAddon(<?= $a['id'] ?>, 1)">+</button>
                    <span id="addonSubtotal<?= $a['id'] ?>" style="font-size: 12px; font-weight: 700; min-width: 74px; text-align: right; color: var(--text-muted);">Rp 0</span>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 12px; font-weight: 700;">
                <span style="color: var(--text-muted);">Subtotal tambahan</span>
                <span id="addonTotalDisplay">Rp 0</span>
            </div>
        </div>
        <?php endif; ?>
            <!-- Alert Kuantitas 0 (K2f) -->
            <div id="kuantitasErrorAlert" style="display: none; color: #dc2626; font-size: 12px; font-weight: 700; margin-top: 6px;">
                Kuantitas harus &gt; 0
            </div>
    </div>

    <!-- Card 3: Pembayaran Token vs Tunai -->
    <div class="card">
        <!-- Self Service Token Input Box -->
        <div id="tokenInputSection">
            <div class="card-title">
                <span>Input Koin Yang Dibayar</span>
                <span style="font-size: 11px; color: var(--text-muted);">Sistem Token</span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border);">
                <div>
                    <strong>Coin Cuci</strong>
                    <div id="coinWashRate" style="font-size: 11px; color: var(--text-muted);">@ Rp 7.000 / pcs</div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" min="0" id="coinWashInput" name="koin_cuci" class="form-control" value="1" style="width: 65px; text-align: center; font-weight: 800;">
                    <span id="subtotalWashDisplay" style="font-size: 13px; font-weight: 700; min-width: 85px; text-align: right;">Rp 7.000</span>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                <div>
                    <strong>Coin Kering</strong>
                    <div id="coinDryRate" style="font-size: 11px; color: var(--text-muted);">@ Rp 10.000 / pcs</div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" min="0" id="coinDryInput" name="koin_kering" class="form-control" value="1" style="width: 65px; text-align: center; font-weight: 800;">
                    <span id="subtotalDryDisplay" style="font-size: 13px; font-weight: 700; min-width: 85px; text-align: right;">Rp 10.000</span>
                </div>
            </div>
        </div>

        <!-- Alert Tunai Non-Token (K2e) -->
        <div id="tunaiAlertBox" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md); padding: 12px; color: #15803d; font-size: 13px;">
            <strong>Pembayaran Tunai / Transfer</strong>
            <p style="font-size: 11px; margin-top: 4px;">Layanan ini dibayar Tunai / Transfer ke Kasir (tanpa token koin).</p>
        </div>

        <!-- Summary Calculation Box -->
        <div style="background: #f8fafc; border: 1.5px dashed var(--border); border-radius: var(--radius-md); padding: 14px; margin-top: 14px;">
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                <span>Total Tagihan:</span>
                <strong id="totalBillDisplay" style="font-size: 15px;">Rp 16.000</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                <span>Total Dibayar:</span>
                <strong id="totalPaidDisplay" style="color: var(--primary); font-size: 15px;">Rp 17.000</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 8px;">
                <span style="font-size: 12px; font-weight: 700;">Status Pembayaran:</span>
                <span id="statusBadgeDisplay" class="badge badge-lunas">Lunas (lebih Rp 1.000)</span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 10px; margin-top: 6px;">
        <a href="index.php" class="btn btn-outline" style="width: 35%;">Batal</a>
        <button type="submit" class="btn btn-primary" style="width: 65%;">Simpan Transaksi</button>
    </div>
</form>

<!-- Modal M2 / M6b: Pelanggan Baru -->
<div class="sheet-overlay" id="modalNewMember">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Pelanggan Baru</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalNewMember')">&times;</button>
        </div>

        <form id="formNewMemberModal" method="POST" action="transaksi-baru.php">
            <input type="hidden" name="action" value="quick_member">
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" id="modalMemberNama" name="quick_nama" class="form-control" placeholder="Contoh: Dewi Lestari" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp *</label>
                <input type="text" id="modalMemberWA" name="quick_wa" class="form-control" placeholder="Contoh: 08155556666" inputmode="numeric" pattern="[0-9]{10,15}" title="Nomor WhatsApp hanya boleh berisi angka (10-15 digit)" oninput="this.setCustomValidity(''); this.value = this.value.replace(/[^0-9]/g, '')" required>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" id="modalMemberCheck" checked>
                    <span>Jadikan Member &amp; buat kartu barcode</span>
                </label>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 18px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalNewMember')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
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

        <!-- Real Webcam / Viewfinder Container -->
        <div id="qrViewfinder" style="width: 100%; border: 2.5px dashed #ea580c; border-radius: 16px;
            background: rgba(234, 88, 12, 0.05); display: flex; flex-direction: column;
            align-items: center; justify-content: center; margin-bottom: 12px;
            padding: 12px; position: relative; overflow: hidden; min-height: 180px;">
            <div id="cameraReader" style="width: 100%; max-width: 240px; margin: 0 auto;"></div>
            <div id="qrStatusText" style="font-size: 12px; font-weight: 700; color: #ea580c; text-align: center; margin-top: 8px;">
                Arahkan QR ke kamera...
            </div>
        </div>

        <!-- Tombol Cepat Simulasi Barcode Pelanggan (Memudahkan Pengujian Tanpa Fisik HP) -->
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
</script>

<?php if (!empty($_GET['toast'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var messages = {
        member_saved: ['Pelanggan baru tersimpan di database', 'success'],
        member_exists: ['Nomor WA sudah terdaftar, memakai data lama', 'info'],
        member_invalid: ['Nama dan nomor WhatsApp tidak valid', 'error'],
        member_failed: ['Gagal menyimpan pelanggan ke database', 'error']
    };
    var key = <?= json_encode($_GET['toast']) ?>;
    var item = messages[key];
    if (item && typeof showToast === 'function') showToast(item[0], item[1]);
});
</script>
<?php endif; ?>

<script>
window.__ADDON_SERVICES = <?= json_encode(array_map(
    fn($a) => ['id' => (int) $a['id'], 'nama' => $a['nama'], 'tarif' => (int) $a['tarif'], 'satuan' => $a['satuan']],
    array_values(array_filter($_SESSION['services'], fn($s) => $s['kategori'] === 'ADDITIONAL'))
), JSON_UNESCAPED_UNICODE) ?>;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>