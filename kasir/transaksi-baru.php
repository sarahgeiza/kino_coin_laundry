<?php
$page_title = 'Transaksi Baru - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'transaksi';

require_once __DIR__ . '/../includes/header.php';

// Handle Save Transaction POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_trx') {
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $wa = trim($_POST['wa_pelanggan'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 1);
    $qty = intval($_POST['qty'] ?? 1);
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

    $total_biaya = $selected_service['tarif'] * $qty;
    $is_token = ($selected_service['kategori'] === 'SELF_SERVICE');
    
    if ($is_token) {
        $total_bayar = ($koin_cuci * 7000) + ($koin_kering * 10000);
        $metode = 'Token';
        $status = ($total_bayar >= $total_biaya) ? 'Lunas' : 'Kurang';
    } else {
        $total_bayar = $total_biaya;
        $metode = 'Tunai';
        $status = 'Lunas';
    }

    $new_id = 'INV-2026-' . str_pad(count($_SESSION['transactions']) + 1, 3, '0', STR_PAD_LEFT);

    $_SESSION['transactions'][] = [
        'id' => $new_id,
        'pelanggan' => $nama,
        'wa' => $wa,
        'kode_kartu' => 'KC-P-' . str_pad(rand(1, 99), 4, '0', STR_PAD_LEFT),
        'layanan' => $selected_service['nama'],
        'layanan_tipe' => $selected_service['kategori_label'],
        'kategori' => $selected_service['kategori'],
        'kuantitas' => $qty . ' ' . $selected_service['satuan'],
        'qty_num' => $qty,
        'total_biaya' => $total_biaya,
        'metode' => $metode,
        'koin_cuci' => $koin_cuci,
        'koin_kering' => $koin_kering,
        'total_bayar' => $total_bayar,
        'status' => $status,
        'kasir' => $_SESSION['username'] ?? 'Daniel',
        'shift' => 'Shift Siang',
        'waktu' => date('H:i') . ' WIB',
        'tanggal' => date('d M Y'),
        'filter_date' => date('Y-m-d')
    ];

    header('Location: index.php?toast=saved');
    exit;
}
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
        <div id="memberSelectedBadge" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 8px 12px; border-radius: 8px; font-size: 12px; margin-bottom: 10px;"></div>

        <div style="position: relative;">
            <input type="text" id="inputPelangganNama" name="nama_pelanggan" class="form-control" placeholder="Ketik nama / WA / scan kartu..." value="Budi Santoso" autocomplete="off" required>
            <!-- Dropdown Autocomplete (K2b) -->
            <div id="customerSuggestBox" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1.5px solid var(--primary-light); border-radius: 8px; box-shadow: var(--shadow-lg); z-index: 30; max-height: 180px; overflow-y: auto;"></div>
        </div>

        <div class="form-group" style="margin-top: 10px; margin-bottom: 0;">
            <label class="form-label">Nomor WhatsApp *</label>
            <input type="tel" id="inputPelangganWA" name="wa_pelanggan" class="form-control" value="08123456789" placeholder="08123456789" required>
        </div>
    </div>

    <!-- Card 2: Pemilihan Layanan -->
    <div class="card">
        <label class="form-label">Pilih Cepat Layanan</label>
        <div class="filter-scroll" style="margin-bottom: 10px;">
            <button type="button" class="chip active" onclick="selectServiceByCategory('cuci')">Cuci</button>
            <button type="button" class="chip" onclick="selectServiceByCategory('kering')">Kering</button>
            <button type="button" class="chip" onclick="selectServiceByCategory('cuci kering')">Cuci + Kering</button>
            <button type="button" class="chip" onclick="selectServiceByCategory('drop off')">Drop Off</button>
            <button type="button" class="chip" onclick="selectServiceByCategory('lipat')">Lipat</button>
        </div>

        <div class="form-group">
            <label class="form-label">Paket Layanan *</label>
            <select name="service_id" id="serviceSelect" class="form-control" required>
                <?php foreach ($_SESSION['services'] as $s): ?>
                    <option value="<?= $s['id'] ?>" data-price="<?= $s['tarif'] ?>" data-category="<?= $s['kategori'] ?>" <?= ($s['id'] === 3 ? 'selected' : '') ?>>
                        <?= htmlspecialchars($s['nama']) ?> — <?= format_rupiah($s['tarif']) ?> (<?= $s['satuan'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Counter Kuantitas -->
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Kuantitas (Putaran / Kg / Pcs) *</label>
            <div style="display: flex; align-items: center; gap: 10px;">
                <button type="button" class="btn btn-outline" style="width: 44px; height: 44px; padding: 0; font-size: 20px;" onclick="changeQty(-1)">-</button>
                <input type="number" min="1" id="qtyInput" name="qty" class="form-control" value="1" style="text-align: center; font-size: 16px; font-weight: 800;" required>
                <button type="button" class="btn btn-outline" style="width: 44px; height: 44px; padding: 0; font-size: 20px;" onclick="changeQty(1)">+</button>
            </div>
            <!-- Alert Kuantitas 0 (K2f) -->
            <div id="kuantitasErrorAlert" style="display: none; color: #dc2626; font-size: 12px; font-weight: 700; margin-top: 6px;">
                ⚠ Kuantitas harus &gt; 0
            </div>
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
                    <div style="font-size: 11px; color: var(--text-muted);">@ Rp 7.000 / pcs</div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" min="0" id="coinWashInput" name="koin_cuci" class="form-control" value="1" style="width: 65px; text-align: center; font-weight: 800;">
                    <span id="subtotalWashDisplay" style="font-size: 13px; font-weight: 700; min-width: 85px; text-align: right;">Rp 7.000</span>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                <div>
                    <strong>Coin Kering</strong>
                    <div style="font-size: 11px; color: var(--text-muted);">@ Rp 10.000 / pcs</div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" min="0" id="coinDryInput" name="koin_kering" class="form-control" value="1" style="width: 65px; text-align: center; font-weight: 800;">
                    <span id="subtotalDryDisplay" style="font-size: 13px; font-weight: 700; min-width: 85px; text-align: right;">Rp 10.000</span>
                </div>
            </div>
        </div>

        <!-- Alert Tunai Non-Token (K2e) -->
        <div id="tunaiAlertBox" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-md); padding: 12px; color: #15803d; font-size: 13px;">
            <strong>✔ Pembayaran Tunai / Transfer</strong>
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
                <span id="statusBadgeDisplay" class="badge badge-lunas">✔ Lunas (lebih Rp 1.000)</span>
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

        <form id="formNewMemberModal" onsubmit="handleQuickCreateMember(event)">
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" id="modalMemberNama" class="form-control" placeholder="Contoh: Dewi Lestari" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp *</label>
                <input type="tel" id="modalMemberWA" class="form-control" placeholder="Contoh: 08155556666" required>
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

<!-- Modal M1a-c: Scan QR Kartu -->
<div class="sheet-overlay" id="modalScanQr">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Scan QR Kartu Pelanggan</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalScanQr')">&times;</button>
        </div>

        <div id="qrViewfinder" style="width: 100%; height: 180px; border: 2.5px dashed #ea580c; border-radius: 16px; background: rgba(234, 88, 12, 0.05); display: flex; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 14px; position: relative; transition: all 0.2s;">
            <div style="margin-bottom: 8px;"><?= get_icon('camera', 36, '#ea580c') ?></div>
            <div id="qrStatusText" style="font-size: 12px; font-weight: 700; color: #ea580c;">
                Arahkan QR ke kamera...
            </div>
            <div style="position: absolute; bottom: 8px; font-size: 11px; background: rgba(0,0,0,0.6); color: white; padding: 2px 10px; border-radius: 12px; cursor: pointer;" onclick="simulateQrScanSuccess()">
                ⚡ Simulasi Scan Berhasil
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Atau ketik kode kartu manual:</label>
            <input type="text" id="qrManualInput" class="form-control" placeholder="Contoh: KC-P-0001" value="">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 14px;">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalScanQr')">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="applyScannedCode()">Gunakan Kode</button>
        </div>
    </div>
</div>

<script>
function handleQuickCreateMember(e) {
    e.preventDefault();
    const nama = document.getElementById('modalMemberNama').value.trim();
    const wa = document.getElementById('modalMemberWA').value.trim();
    const isMember = document.getElementById('modalMemberCheck').checked;

    if (!nama || !wa) return;

    const newCode = 'KC-P-000' + (window.__MOCK_MEMBERS.length + 1);
    const newMember = {
        id: window.__MOCK_MEMBERS.length + 1,
        nama,
        wa,
        kode_kartu: newCode,
        is_member: isMember,
        tx_count: 0
    };

    window.__MOCK_MEMBERS.push(newMember);

    document.getElementById('inputPelangganNama').value = nama;
    document.getElementById('inputPelangganWA').value = wa;
    const badge = document.getElementById('memberSelectedBadge');
    if (badge) {
        badge.style.display = 'block';
        badge.innerHTML = `✓ Member Baru Terdaftar: <strong>${nama}</strong> (${newCode})`;
    }

    closeModal('modalNewMember');
    showToast(`Pelanggan ${nama} berhasil ditambahkan`, 'success');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
