<?php
$page_title = 'Transaksi Baru - Kino Coin Laundry';
$header_role = 'Kasir';
$header_user = 'Daniel';
$header_avatar = 'D';
$header_title = 'Transaksi Baru';
$header_subtitle = 'Input cucian & pembayaran koin pelanggan';
$current_role = 'kasir';
$active_tab = 'transaksi';

require_once __DIR__ . '/../includes/header.php';

$success_msg = '';
$error_msg = '';
$memberQrUrl = null;
$memberQrName = '';
$memberWasCreated = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $pelanggan = trim($_POST['nama_pelanggan'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $layanan_id = intval($_POST['layanan_id'] ?? 1);
    $berat = floatval($_POST['berat'] ?? 0);
    $koin_500 = intval($_POST['koin_500'] ?? 0);
    $koin_1000 = intval($_POST['koin_1000'] ?? 0);

    $selected_service = null;
    foreach ($_SESSION['services'] as $s) {
        if ($s['id'] === $layanan_id) {
            $selected_service = $s;
            break;
        }
    }

    if (!$selected_service) {
        $selected_service = $_SESSION['services'][0];
    }

    if (empty($pelanggan) || $berat <= 0) {
        $error_msg = 'Mohon isi nama pelanggan dan berat cucian yang valid.';
    } else {
        try {
            require_once __DIR__ . '/../includes/customer_access.php';
            $member = \Kino\CustomerAccess\registerOrFind($pelanggan, $telepon);
            $pelanggan = $member['nama_pelanggan'];
            $telepon = $member['no_hp'];
            $memberQrUrl = \Kino\CustomerAccess\memberUrl(
                (int) $member['id_pelanggan'],
                $member['token']
            );
            $memberQrName = $pelanggan;
            $memberWasCreated = $member['is_new'];

            $total_biaya = round($berat * $selected_service['tarif']);
            $total_koin_nilai = ($koin_500 * 500) + ($koin_1000 * 1000);
            $sisa = $total_biaya - $total_koin_nilai;
            $status = ($sisa <= 0) ? 'Lunas' : 'Kurang';

            $new_id = 'INV-2026-' . str_pad(count($_SESSION['transactions']) + 1, 3, '0', STR_PAD_LEFT);

            $new_trx = [
                'id' => $new_id,
                'pelanggan' => $pelanggan,
                'telepon' => $telepon,
                'layanan_id' => $selected_service['id'],
                'layanan' => $selected_service['nama'],
                'berat' => $berat,
                'total_biaya' => $total_biaya,
                'koin_500' => $koin_500,
                'koin_1000' => $koin_1000,
                'total_koin_nilai' => $total_koin_nilai,
                'sisa_bayar' => max(0, $sisa),
                'status' => $status,
                'waktu' => date('H:i') . ' WIB',
                'tanggal' => date('d M Y'),
                'kasir' => $_SESSION['username'] ?? 'Daniel',
                'shift' => 'Siang'
            ];

            $_SESSION['transactions'][] = $new_trx;

            $_SESSION['antrian'][] = [
                'nomor' => str_pad(count($_SESSION['antrian']) + 1, 2, '0', STR_PAD_LEFT),
                'pelanggan' => $pelanggan,
                'estimasi' => '~' . ceil($berat * 15) . ' menit',
                'status' => 'Proses',
                'status_color' => 'orange',
                'layanan' => $selected_service['nama'] . ' • ' . $berat . ' kg'
            ];

            $success_msg = 'Transaksi ' . $new_id . ' berhasil dicatat!';
        } catch (Throwable $exception) {
            error_log($exception->__toString());
            $error_msg = $exception instanceof \Kino\CustomerAccess\CustomerInputException
                ? $exception->getMessage()
                : 'Pelanggan tidak dapat disimpan. Periksa konfigurasi database dan coba lagi.';
        }
    }
}
?>

<?php if ($success_msg): ?>
    <div class="info-banner" style="background:#dcfce7; border-color:#86efac; color:#15803d;">
        <span>✅</span>
        <span><?= htmlspecialchars($success_msg) ?></span>
    </div>
<?php endif; ?>

    <?php if ($memberQrUrl !== null): ?>
        <div class="card" style="text-align:center; border:1px solid #86efac;">
            <div class="card-title">
                <?= $memberWasCreated ? 'Pelanggan berhasil didaftarkan' : 'Pelanggan sudah terdaftar' ?>
            </div>
            <p>QR member untuk <strong><?= htmlspecialchars($memberQrName, ENT_QUOTES, 'UTF-8') ?></strong>.
                Pelanggan dapat memindainya dengan kamera HP untuk masuk ke halaman pelanggan.</p>
            <div id="member-qr" style="display:inline-block; padding:12px; background:#fff;"></div>
            <p><a href="<?= htmlspecialchars($memberQrUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Buka halaman login pelanggan</a></p>
            <p style="font-size:12px;color:#64748b">QR ini menggunakan alamat aplikasi. Atur APP_BASE_URL agar dapat dibuka dari perangkat pelanggan.</p>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
        if (window.QRCode) {
            new QRCode(document.getElementById('member-qr'), {
                text: <?= json_encode($memberQrUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                width: 220,
                height: 220,
                correctLevel: QRCode.CorrectLevel.H
            });
        } else {
            document.getElementById('member-qr').textContent = 'QR tidak dapat dimuat. Buka tautan login pelanggan.';
        }
        </script>
    <?php endif; ?>

<?php if ($error_msg): ?>
    <div class="info-banner warning">
        <span>⚠️</span>
        <span><?= htmlspecialchars($error_msg) ?></span>
    </div>
<?php endif; ?>

<form action="transaksi-baru.php" method="POST" id="trx-form">
    <div class="responsive-grid-2">
        <!-- Kolom Kiri: Data Cucian -->
        <div class="card">
            <div class="card-title">Informasi Cucian &amp; Pelanggan</div>
            <div class="form-group">
                <label class="form-label" for="nama_pelanggan">Nama Pelanggan *</label>
                <input type="text" id="nama_pelanggan" name="nama_pelanggan" class="form-control" placeholder="Nama pelanggan" value="" minlength="2" maxlength="100" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="telepon">Nomor WhatsApp *</label>
                <input type="tel" id="telepon" name="telepon" class="form-control" placeholder="Contoh: 081234567890" value="" inputmode="tel" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="service-select">Pilih Layanan *</label>
                <select name="layanan_id" id="service-select" class="form-control" required>
                    <?php foreach ($_SESSION['services'] as $s): ?>
                        <option value="<?= $s['id'] ?>" data-price="<?= $s['tarif'] ?>">
                            <?= htmlspecialchars($s['nama']) ?> — <?= format_rupiah($s['tarif']) ?>/kg
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="weight-input">Timbangan Berat (kg) *</label>
                <input type="number" step="0.1" min="0.1" id="weight-input" name="berat" class="form-control" placeholder="3.5" value="3.5" required>
                
                <div class="capacity-progress">
                    <div class="capacity-bar" id="capacity-bar"></div>
                </div>
                <div id="capacity-text" style="font-size: 11px; margin-top: 4px; color: var(--text-muted); font-weight: 600;">
                    ⚠️ Maks. 7,5 kg — pastikan sesuai standar koin
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Pembayaran Koin & Total Tagihan -->
        <div class="card">
            <div class="card-title">Pembayaran &amp; Ringkasan</div>
            <div class="coin-box">
                <div style="font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                    INPUT KOIN YANG DIBAYAR
                </div>

                <div class="coin-row">
                    <div class="coin-badge">
                        <span style="color: var(--accent); font-size: 18px;">🪙</span>
                        <span>Koin Rp 500</span>
                    </div>
                    <div class="coin-input-wrapper">
                        <input type="number" min="0" id="coin-500-input" name="koin_500" class="coin-qty-input" value="7">
                        <div class="coin-subtotal" id="subtotal-500">Rp 3.500</div>
                    </div>
                </div>

                <div class="coin-row">
                    <div class="coin-badge">
                        <span style="color: var(--accent); font-size: 18px;">🪙</span>
                        <span>Koin Rp 1.000</span>
                    </div>
                    <div class="coin-input-wrapper">
                        <input type="number" min="0" id="coin-1000-input" name="koin_1000" class="coin-qty-input" value="0">
                        <div class="coin-subtotal" id="subtotal-1000">Rp 0</div>
                    </div>
                </div>
            </div>

            <div class="bill-summary">
                <div class="bill-row">
                    <span>Total Tagihan:</span>
                    <strong id="total-bill" style="font-size: 16px;">Rp 21.000</strong>
                </div>
                <div class="bill-row">
                    <span>Total Koin Dibayar:</span>
                    <strong id="total-coin-paid" style="color: var(--success); font-size: 16px;">Rp 3.500</strong>
                </div>
                <div class="bill-status status-kurang" id="bill-status">
                    ⚠️ Kurang Rp 17.500
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 18px;">
                Simpan Transaksi
            </button>
            <a href="index.php" class="btn btn-outline" style="margin-top: 8px;">Kembali</a>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
