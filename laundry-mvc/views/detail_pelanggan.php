<?php
use function LaundryMvc\Config\csrfField;
use function LaundryMvc\Config\e;
use function LaundryMvc\Config\linkWhatsApp;
use function LaundryMvc\Config\rupiah;
use function LaundryMvc\Config\tanggal;
use const LaundryMvc\Config\LAYANAN;
use const LaundryMvc\Config\STATUS_LIST;

$customer = $data['pelanggan'];
$title = 'Detail Pelanggan';
$active = 'home';
$whatsAppText = "Halo {$customer['nama_pelanggan']}, terima kasih sudah mendaftar di Kino Koin Laundry.\n"
    . "Kode member Anda: {$customer['kode_pelanggan']}\n"
    . 'Tunjukkan QR member ke kasir setiap datang.';
require __DIR__ . '/partials/header.php';
?>
<h2><?= e($customer['nama_pelanggan']) ?></h2>
<?php foreach (($errors ?? []) as $error): ?>
    <div class="alert error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="grid">
    <div class="card qr-box">
        <h3>QR Member</h3>
        <div id="qr"></div>
        <div class="kode"><?= e($customer['kode_pelanggan']) ?></div>
        <div><b><?= e($customer['nama_pelanggan']) ?></b></div>
        <div class="muted"><?= e($customer['no_hp']) ?></div>
        <div class="aksi no-print">
            <button class="btn kecil" type="button" onclick="window.print()">Cetak</button>
            <button class="btn kecil hijau" type="button" onclick="unduhQR()">Unduh PNG</button>
            <a class="btn kecil whatsapp" target="_blank" rel="noopener"
               href="<?= e(linkWhatsApp($customer['no_hp'], $whatsAppText)) ?>">Kirim WhatsApp</a>
        </div>
    </div>

    <div>
        <div class="card no-print">
            <h3>Pesanan Baru</h3>
            <form method="post" autocomplete="off">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="order">
                <input type="hidden" name="id_pelanggan" value="<?= (int) $customer['id_pelanggan'] ?>">

                <label for="layanan">Layanan</label>
                <select name="layanan" id="layanan" required>
                    <?php foreach (LAYANAN as $key => $service): ?>
                        <option value="<?= e($key) ?>"><?= e($service['nama']) ?> - <?= rupiah($service['harga']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="jumlah">Jumlah (siklus / mesin)</label>
                <input type="number" name="jumlah" id="jumlah" value="1" min="1" max="50" required>

                <label for="catatan">Catatan (opsional)</label>
                <textarea name="catatan" id="catatan" rows="2" maxlength="255"
                          placeholder="Contoh: mesin nomor 3"></textarea>

                <div class="total-box">Total: <span id="total">Rp0</span></div>
                <button class="btn" type="submit">Simpan Pesanan</button>
            </form>
        </div>

        <div class="card">
            <h3>Ringkasan</h3>
            <p>Terdaftar: <b><?= e(tanggal($customer['created_at'])) ?></b></p>
            <p>Jumlah pesanan: <b><?= (int) $data['ringkasan']['jumlah'] ?></b></p>
            <p>Total belanja: <b><?= rupiah($data['ringkasan']['total_belanja']) ?></b></p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Riwayat Pesanan</h3>
    <?php if ($data['riwayat'] === []): ?>
        <p class="muted">Belum ada pesanan untuk pelanggan ini.</p>
    <?php else: ?>
        <div class="tabel-scroll"><table>
            <thead><tr><th>Waktu</th><th>Layanan</th><th>Jumlah</th><th>Total</th><th>Catatan</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($data['riwayat'] as $order): ?>
                <tr>
                    <td><?= e(tanggal($order['created_at'])) ?></td>
                    <td><?= e(LAYANAN[$order['layanan']]['nama'] ?? $order['layanan']) ?></td>
                    <td><?= (int) $order['jumlah'] ?></td>
                    <td><?= rupiah($order['total']) ?></td>
                    <td><?= e($order['catatan'] ?? '') ?></td>
                    <td>
                        <form method="post" class="status-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="id_pesanan" value="<?= (int) $order['id_pesanan'] ?>">
                            <input type="hidden" name="kode_pelanggan" value="<?= e($customer['kode_pelanggan']) ?>">
                            <select name="status" aria-label="Status pesanan" onchange="this.form.submit()">
                                <?php foreach (STATUS_LIST as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                                        <?= e($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const memberCode = <?= json_encode(
    $customer['kode_pelanggan'],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;
const prices = <?= json_encode(
    array_map(static fn(array $service): int => $service['harga'], LAYANAN),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

new QRCode(document.getElementById('qr'), {
    text: memberCode,
    width: 220,
    height: 220,
    correctLevel: QRCode.CorrectLevel.H
});

function unduhQR() {
    const canvas = document.querySelector('#qr canvas');
    if (!canvas) {
        alert('QR belum siap, coba lagi.');
        return;
    }
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = 'QR-' + memberCode + '.png';
    link.click();
}

const serviceSelect = document.getElementById('layanan');
const quantityInput = document.getElementById('jumlah');
const totalLabel = document.getElementById('total');

function updateTotal() {
    const total = (prices[serviceSelect.value] || 0) * (parseInt(quantityInput.value, 10) || 0);
    totalLabel.textContent = 'Rp' + total.toLocaleString('id-ID');
}

serviceSelect.addEventListener('change', updateTotal);
quantityInput.addEventListener('input', updateTotal);
updateTotal();
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
