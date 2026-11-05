<?php
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
check_auth_guard('kasir');

// Wajib check-in presensi terlebih dahulu sebelum mencatat transaksi kasir
if (!one(
    'SELECT id_shift_operasional
     FROM shift_operasional
     WHERE id_kasir = ? AND status = ?',
    [(int)$_SESSION['user_id'], 'aktif']
)) {
    $_SESSION['kasir_checked_in'] = false;
    header('Location: index.php?toast=need_checkin');
    exit;
}
$_SESSION['kasir_checked_in'] = true;



$trx_error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'save_trx') {
    header('Content-Type: application/json; charset=utf-8');
    respond(['error' => 'Kirim transaksi melalui endpoint API.'], 405);
}

$serviceOptions = rows(
    'SELECT id_layanan, nama_layanan, harga, jumlah_koin, berat_standar
     FROM layanan
     WHERE aktif = TRUE
     ORDER BY id_layanan'
);

// Cek apakah ada scan member dari dashboard kasir
$scan_code = trim($_GET['scan_member'] ?? '');
$prefilled_member = null;
if ($scan_code !== '') {
    $prefilled_member = one(
        "SELECT id_pelanggan AS id, nama_pelanggan AS nama, no_hp AS wa,
                COALESCE(kode_kartu, CONCAT('KC-P-', LPAD(id_pelanggan::text, 4, '0'))) AS kode_kartu
         FROM pelanggan
         WHERE UPPER(COALESCE(kode_kartu, CONCAT('KC-P-', LPAD(id_pelanggan::text, 4, '0')))) = UPPER(?)",
        [$scan_code]
    );
}

$page_title = 'Transaksi Baru - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'transaksi';

require_once __DIR__ . '/../includes/header.php';

?>

<!-- Header Meta Sub (Persis Figma K2) -->
<div
    style="margin-bottom: 12px; font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center;">
    <div>
        <strong style="color: var(--text-main);">Transaksi Baru</strong>
        <span>• Kasir: <?= htmlspecialchars($user_name) ?></span>
    </div>
    <span style="font-size: 11px;"><?= date('d/m/Y H.i.s') ?></span>
</div>

<form action="transaksi-baru.php" method="POST" id="formTransaksiBaru">
    <input type="hidden" name="action" value="save_trx">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">

    <!-- Card 1: Data Pelanggan -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <label class="form-label" style="margin-bottom: 0;">Pelanggan *</label>
            <div style="display: flex; gap: 6px;">
                <button type="button" class="btn btn-outline" style="width: auto; padding: 4px 10px; font-size: 11px;"
                    onclick="openModal('modalScanQr')">
                    <?= get_icon('camera', 14) ?> Scan Kartu
                </button>
                <button type="button" class="btn btn-outline" style="width: auto; padding: 4px 10px; font-size: 11px;"
                    onclick="openModal('modalNewMember')">
                    + Pelanggan Baru
                </button>
            </div>
        </div>

        <!-- Selected Member Alert (K2c) -->
        <div id="memberSelectedBadge"
            style="display: <?= $prefilled_member ? 'block' : 'none' ?>; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 8px 12px; border-radius: 8px; font-size: 12px; margin-bottom: 10px;">
            <?= $prefilled_member ? 'Member Terpilih: <strong>' . htmlspecialchars($prefilled_member['nama']) . '</strong> (' . htmlspecialchars($prefilled_member['kode_kartu']) . ')' : '' ?>
        </div>

        <div style="position: relative;">
            <input type="text" id="inputPelangganNama" name="nama_pelanggan" class="form-control"
                placeholder="Ketik nama / WA / scan kartu..." value="<?= htmlspecialchars($prefilled_member['nama'] ?? '') ?>" autocomplete="off" required>
            <!-- Dropdown Autocomplete (K2b) -->
            <div id="customerSuggestBox"
                style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1.5px solid var(--primary-light); border-radius: 8px; box-shadow: var(--shadow-lg); z-index: 30; max-height: 180px; overflow-y: auto;">
            </div>
        </div>

        <div class="form-group" style="margin-top: 10px; margin-bottom: 0;">
            <label class="form-label">Nomor WhatsApp *</label>
            <input type="text" id="inputPelangganWA" name="wa_pelanggan" class="form-control" value="<?= htmlspecialchars($prefilled_member['wa'] ?? '') ?>"
                placeholder="08123456789" inputmode="numeric" pattern="[0-9]+"
                title="Nomor WhatsApp hanya boleh berisi angka" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                required>
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

        <div id="serviceDetails">
            <div class="service-detail-row"
                style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 12px;">
                <div class="form-group">
                    <label class="form-label">Layanan *</label>
                    <select id="serviceSelect" class="form-control service-select" required>
                        <?php $currentServiceGroup = null; ?>
                        <?php foreach ($serviceOptions as $service): ?>
                            <?php
                            $isMachine = (int)$service['jumlah_koin'] > 0;
                            $isProcessService = $isMachine || (float)($service['berat_standar'] ?? 0) > 0;
                            $unit = $isProcessService
                                ? 'process'
                                : (stripos($service['nama_layanan'], 'lipat') !== false ? 'kg' : 'pcs');
                            $serviceName = strtolower($service['nama_layanan']);
                            $serviceGroup = in_array($serviceName, ['sabun', 'softener', 'softner', 'kresek'], true)
                                ? 'ADDITIONAL'
                                : (stripos($serviceName, 'drop off') !== false || stripos($serviceName, 'lipat') !== false
                                    ? 'DROP OFF'
                                    : 'SELF SERVICE');
                            $selected = stripos($serviceName, 'cuci kering') !== false
                                && stripos($serviceName, 'drop off') === false;
                            if ($serviceGroup !== $currentServiceGroup):
                                if ($currentServiceGroup !== null): ?>
                                    </optgroup>
                                <?php endif; ?>
                                <optgroup label="<?= $serviceGroup ?>">
                                <?php $currentServiceGroup = $serviceGroup; ?>
                            <?php endif; ?>
                            <option value="<?= (int)$service['id_layanan'] ?>"
                                data-price="<?= (int)$service['harga'] ?>"
                                data-coins="<?= (int)$service['jumlah_koin'] ?>"
                                data-machine="<?= $isMachine ? 'true' : 'false' ?>"
                                data-unit="<?= $unit ?>"
                                <?= $selected ? 'selected' : '' ?>>
                                <?= htmlspecialchars($service['nama_layanan']) ?> — <?= format_rupiah($service['harga']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($currentServiceGroup !== null): ?>
                            </optgroup>
                        <?php endif; ?>
                        <?php if ($serviceOptions === []): ?>
                            <option value="">Tidak ada layanan aktif</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label detail-quantity-label">Berat (kg) *</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button type="button" class="btn btn-outline"
                            style="width: 44px; height: 44px; padding: 0; font-size: 20px;"
                            onclick="changeQty(-1, this)">-</button>
                        <input type="number" min="0.01" step="0.01" id="qtyInput"
                            class="form-control service-quantity" value="7.5"
                            style="text-align: center; font-size: 16px; font-weight: 800;" required>
                        <button type="button" class="btn btn-outline"
                            style="width: 44px; height: 44px; padding: 0; font-size: 20px;"
                            onclick="changeQty(1, this)">+</button>
                    </div>
                    <div class="kuantitas-error-alert"
                        style="display: none; color: #dc2626; font-size: 12px; font-weight: 700; margin-top: 6px;">
                        Nilai harus lebih dari 0
                    </div>
                </div>
            </div>
        </div>
        <?php
        $quickAddServices = array_values(array_filter($serviceOptions, static function (array $service): bool {
            $name = strtolower(trim($service['nama_layanan']));
            return in_array($name, ['extra spin', 'extra dry', 'sabun', 'softener', 'softner', 'kresek'], true);
        }));
        ?>
        <?php if ($quickAddServices !== []): ?>
            <div style="border-top: 1px solid var(--border); padding-top: 12px; margin-top: 8px;">
                <label class="form-label">Tambahan cepat (opsional)</label>
                <div style="display: grid; gap: 8px;">
                    <?php foreach ($quickAddServices as $service): ?>
                        <?php
                        $serviceName = strtolower(trim($service['nama_layanan']));
                        $serviceIsMachine = (int)$service['jumlah_koin'] > 0;
                        $serviceIsProcess = $serviceIsMachine || (float)($service['berat_standar'] ?? 0) > 0;
                        $serviceUnit = $serviceIsProcess
                            ? 'process'
                            : ($serviceName === 'lipat' ? 'kg' : 'pcs');
                        $unitLabel = $serviceUnit === 'process' ? 'proses' : $serviceUnit;
                        ?>
                        <label class="quick-add-service-option"
                            style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; cursor: pointer;">
                            <input type="checkbox" class="quick-add-service"
                                value="<?= (int)$service['id_layanan'] ?>"
                                data-price="<?= (int)$service['harga'] ?>"
                                data-coins="<?= (int)$service['jumlah_koin'] ?>"
                                data-unit="<?= $serviceUnit ?>"
                                style="width: 16px; height: 16px; accent-color: var(--primary);">
                            <span style="display: flex; justify-content: space-between; gap: 10px; width: 100%; font-size: 12px;">
                                <span><?= htmlspecialchars($service['nama_layanan']) ?></span>
                                <strong><?= format_rupiah($service['harga']) ?> / <?= $unitLabel ?></strong>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Card 3: Pembayaran -->
    <div class="card">
        <div class="form-group">
            <label class="form-label" for="paymentType">Metode Pembayaran *</label>
            <select id="paymentType" class="form-control" required>
                <option value="cash">Cash</option>
                <option value="qris">QRIS</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="amountPaid">Jumlah dibayar (Rp) *</label>
            <input type="number" min="0" step="1" id="amountPaid" class="form-control" value="0" required>
        </div>

        <!-- Summary Calculation Box -->
        <div
            style="background: #f8fafc; border: 1.5px dashed var(--border); border-radius: var(--radius-md); padding: 14px; margin-top: 14px;">
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                <span>Total Tagihan:</span>
                <strong id="totalBillDisplay" style="font-size: 15px;">Rp 16.000</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                <span>Total koin mesin:</span>
                <strong id="totalMachineCoinsDisplay">0</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                <span>Jumlah dibayarkan:</span>
                <strong id="amountPaidDisplay" style="font-size: 15px;">Rp 0</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                <span>Sisa pembayaran:</span>
                <strong id="remainingAmountDisplay" style="font-size: 15px;">Rp 16.000</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                <span>Kembalian:</span>
                <strong id="changeAmountDisplay" style="color: var(--primary); font-size: 15px;">Rp 0</strong>
            </div>
            <div
                style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 8px;">
                <span style="font-size: 12px; font-weight: 700;">Status Pembayaran:</span>
                <span id="statusBadgeDisplay" class="badge">Belum lunas</span>
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
                <input type="text" id="modalMemberNama" class="form-control" placeholder="Contoh: Dewi Lestari"
                    required>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp *</label>
                <input type="text" id="modalMemberWA" class="form-control" placeholder="Contoh: 08155556666"
                    inputmode="numeric" pattern="[0-9]+" title="Nomor WhatsApp hanya boleh berisi angka"
                    oninput="this.setCustomValidity(''); this.value = this.value.replace(/[^0-9]/g, '')" required>
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
            <div id="qrStatusText"
                style="font-size: 12px; font-weight: 700; color: #ea580c; text-align: center; margin-top: 8px;">
                Arahkan QR ke kamera...
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Atau ketik kode kartu manual:</label>
            <input type="text" id="qrManualInput" class="form-control" placeholder="Kode yang tertera pada kartu" value="">
        </div>

        <div style="display: flex; gap: 10px; margin-top: 14px;">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalScanQr')">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="applyScannedCode()">Gunakan Kode</button>
        </div>
    </div>
</div>

<script>
    async function handleQuickCreateMember(e) {
        e.preventDefault();
        const nama = document.getElementById('modalMemberNama').value.trim();
        const wa = document.getElementById('modalMemberWA').value.trim();

        if (!nama || !/^[0-9]+$/.test(wa)) {
            document.getElementById('modalMemberWA').setCustomValidity('Nomor WhatsApp hanya boleh berisi angka.');
            document.getElementById('modalMemberWA').reportValidity();
            return;
        }

        document.getElementById('modalMemberWA').setCustomValidity('');
        try {
            const result = await window.KinoAPI.createMember(nama, wa, true);
            const member = result.data;
            document.getElementById('inputPelangganNama').value = member.nama_pelanggan;
            document.getElementById('inputPelangganWA').value = member.no_hp;
            const badge = document.getElementById('memberSelectedBadge');
            if (badge) {
                badge.style.display = 'block';
                badge.innerHTML = `Pelanggan Baru: <strong>${member.nama_pelanggan}</strong> (${member.kode_kartu})`;
            }
            closeModal('modalNewMember');
            showToast(`Pelanggan ${member.nama_pelanggan} berhasil ditambahkan`, 'success');
        } catch (error) {
            showToast(error.message || 'Pelanggan gagal ditambahkan', 'error', 4500);
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>