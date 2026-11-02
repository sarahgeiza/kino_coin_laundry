<?php
$page_title = 'Transaksi Baru - Kino Coin Laundry';
$header_role = 'Kasir';
$header_user = '';
$header_avatar = 'K';
$header_title = 'Transaksi Baru';
$header_subtitle = 'Input cucian dan pembayaran Cash atau QRIS';
$current_role = 'kasir';
$active_tab = 'transaksi';

require_once __DIR__ . '/../includes/header.php';

$success_msg = '';
$error_msg = '';
$csrfToken = web_csrf_token();
?>

<?php if ($success_msg): ?>
    <div class="info-banner" style="background:#dcfce7; border-color:#86efac; color:#15803d;">
        <span>✅</span>
        <span><?= htmlspecialchars($success_msg) ?></span>
    </div>
<?php endif; ?>

<?php if (!$activeOperationalShift): ?>
    <div class="info-banner warning"><span>⚠️</span><span>Buka shift dari dashboard kasir sebelum membuat transaksi.</span></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="info-banner warning">
        <span>⚠️</span>
        <span><?= htmlspecialchars($error_msg) ?></span>
    </div>
<?php endif; ?>

<form action="transaksi-baru.php" method="POST" id="trx-form">
    <input type="hidden" id="trxCsrf" value="<?= web_h($csrfToken) ?>">
    <div class="responsive-grid-2">
        <!-- Kolom Kiri: Data Cucian -->
        <div class="card">
            <div class="card-title">Informasi Cucian &amp; Pelanggan</div>
            <div class="form-group">
                <label class="form-label" for="nama_pelanggan">Nama Pelanggan *</label>
                <input type="text" id="nama_pelanggan" name="nama_pelanggan" class="form-control" placeholder="Contoh: Budi Santoso" value="" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="telepon">Nomor WhatsApp *</label>
                <input type="tel" id="telepon" name="telepon" class="form-control" placeholder="Contoh: 08123456789" value="" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="service-select">Pilih Layanan *</label>
                <select name="layanan_id" id="service-select" class="form-control" required>
                    <?php foreach ($services as $s): if ($s['status'] !== 'Aktif') continue; ?>
                        <option value="<?= $s['id'] ?>" data-price="<?= $s['tarif'] ?>" data-coins="<?= $s['jumlah_koin'] ?>">
                            <?= htmlspecialchars($s['nama']) ?> — <?= format_rupiah($s['tarif']) ?>/kg
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="weight-input">Timbangan Berat (kg) *</label>
                <input type="number" step="0.1" min="0.1" id="weight-input" name="berat" class="form-control" placeholder="3.5" value="" required>
                
                <div class="capacity-progress">
                    <div class="capacity-bar" id="capacity-bar"></div>
                </div>
                <div id="capacity-text" style="font-size: 11px; margin-top: 4px; color: var(--text-muted); font-weight: 600;">
                    Maksimal 7,5 kg per transaksi.
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Pembayaran dan Kebutuhan Koin Mesin -->
        <div class="card">
            <div class="card-title">Pembayaran &amp; Ringkasan</div>
            <div class="bill-summary">
                <div class="bill-row">
                    <span>Kebutuhan koin mesin:</span>
                    <strong id="machine-coin-count">1 koin</strong>
                </div>
                <p style="font-size:12px;color:var(--text-muted);margin:6px 0 14px;">
                    Koin ini digunakan untuk menjalankan mesin laundry, bukan alat pembayaran.
                    Jumlahnya mengikuti layanan yang dipilih.
                </p>
                <div class="bill-row">
                    <span>Total Tagihan:</span>
                    <strong id="total-bill" style="font-size:16px;">Rp 0</strong>
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label class="form-label" for="payment-method">Metode Pembayaran *</label>
                <select id="payment-method" name="jenis_pembayaran" class="form-control" required>
                    <option value="cash">Cash (Tunai)</option>
                    <option value="qris">QRIS</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top:18px;" <?= !$activeOperationalShift ? 'disabled' : '' ?>>
                Simpan Transaksi
            </button>
            <a href="index.php" class="btn btn-outline" style="margin-top:8px;">Kembali</a>
        </div>
    </div>
</form>

<script>
const serviceSelect = document.getElementById('service-select');
const weightInput = document.getElementById('weight-input');
const totalBill = document.getElementById('total-bill');
const machineCoinCount = document.getElementById('machine-coin-count');

function updateTransactionSummary() {
    const selected = serviceSelect?.selectedOptions[0];
    const price = Number(selected?.dataset.price || 0);
    const machineCoins = Number(selected?.dataset.coins || 0);
    const weight = Number(weightInput?.value || 0);
    const total = Math.round(price * weight);
    totalBill.textContent = 'Rp ' + total.toLocaleString('id-ID');
    machineCoinCount.textContent = machineCoins + ' koin';
}
serviceSelect?.addEventListener('change', updateTransactionSummary);
weightInput?.addEventListener('input', updateTransactionSummary);
updateTransactionSummary();

document.getElementById('trx-form')?.addEventListener('submit', async function(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector('button[type="submit"]');
    const name = form.querySelector('[name="nama_pelanggan"]').value.trim();
    const phone = form.querySelector('[name="telepon"]').value.trim();
    const serviceId = Number(form.querySelector('[name="layanan_id"]').value);
    const weight = Number(form.querySelector('[name="berat"]').value);
    const paymentType = form.querySelector('[name="jenis_pembayaran"]').value;
    const message = document.getElementById('trxFeedback');

    if (!name || !phone || !Number.isFinite(weight) || weight <= 0 || weight > 7.5 ||
        !['cash', 'qris'].includes(paymentType)) {
        message.textContent = 'Periksa nama, WhatsApp, berat (maksimal 7,5 kg), dan metode pembayaran.';
        message.style.color = '#dc2626';
        return;
    }

    button.disabled = true;
    try {
        const response = await fetch('../api/index.php?module=transaksi&action=create', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.getElementById('trxCsrf').value
            },
            body: JSON.stringify({
                nama_pelanggan: name,
                no_hp: phone,
                jenis_pembayaran: paymentType,
                detail_transaksi: [{ id_layanan: serviceId, berat: weight }]
            })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Transaksi gagal disimpan.');
        message.style.color = '#15803d';
        message.textContent = 'Transaksi ' + result.data.no_invoice + ' berhasil disimpan.';
        setTimeout(() => location.href = 'transaksi.php', 900);
    } catch (error) {
        message.style.color = '#dc2626';
        message.textContent = error.message || 'Terjadi kesalahan.';
        button.disabled = false;
    }
});
</script>
<p id="trxFeedback" role="alert" aria-live="polite" style="font-size:13px;margin-top:10px;"></p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
