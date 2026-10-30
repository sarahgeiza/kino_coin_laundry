<?php

declare(strict_types=1);

session_start();
$loginMessage = $_SESSION['customer_login_message'] ?? '';
unset($_SESSION['customer_login_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Pelanggan - Kino Coin Laundry</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-container">
    <main class="app-content">
        <div class="card">
            <div class="card-title">Login Pelanggan dengan QR Member</div>
            <p>Scan QR yang diberikan kasir. Jika QR dibuka dari kamera HP, Anda akan langsung masuk ke akun pelanggan.</p>
            <?php if ($loginMessage !== ''): ?>
                <div class="info-banner"><?= htmlspecialchars($loginMessage, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <button type="button" class="btn btn-primary" id="start-scan">Nyalakan kamera</button>
            <button type="button" class="btn btn-outline" id="stop-scan" hidden>Matikan kamera</button>
            <div id="qr-reader" style="max-width:420px;margin:16px auto"></div>
            <p id="scan-message" role="status"></p>

            <form id="qr-link-form">
                <label for="qr-link">Atau masukkan tautan QR member</label>
                <input type="url" id="qr-link" class="form-control"
                       placeholder="http://alamat-aplikasi/pelanggan/index.php?id=...&token=...">
                <button class="btn btn-primary" type="submit">Masuk dengan tautan</button>
            </form>

            <p style="margin-top:20px">
                <a href="../index.php">Kembali ke halaman utama</a>
            </p>
        </div>
    </main>
</div>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const startButton = document.getElementById('start-scan');
const stopButton = document.getElementById('stop-scan');
const message = document.getElementById('scan-message');
let scanner;

function openMemberLink(value) {
    let target;
    try {
        target = new URL(value, window.location.href);
    } catch {
        message.textContent = 'Tautan QR tidak valid.';
        return false;
    }

    const memberPage = new URL('index.php', window.location.href);
    if (
        target.origin !== window.location.origin
        || target.pathname !== memberPage.pathname
        || !target.searchParams.has('id')
        || !target.searchParams.has('token')
    ) {
        message.textContent = 'QR bukan tautan login member Kino Laundry.';
        return false;
    }

    window.location.assign(target.href);
    return true;
}

document.getElementById('qr-link-form').addEventListener('submit', (event) => {
    event.preventDefault();
    openMemberLink(document.getElementById('qr-link').value.trim());
});

startButton.addEventListener('click', async () => {
    if (!window.Html5Qrcode) {
        message.textContent = 'Pemindai tidak dapat dimuat. Buka QR dengan kamera HP atau masukkan tautannya.';
        return;
    }

    startButton.disabled = true;
    scanner = new Html5Qrcode('qr-reader');
    try {
        await scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            async (text) => {
                if (openMemberLink(text.trim())) {
                    await scanner.stop();
                }
            },
            () => {}
        );
        startButton.hidden = true;
        stopButton.hidden = false;
        message.textContent = 'Arahkan kamera ke QR member.';
    } catch (error) {
        startButton.disabled = false;
        message.textContent = 'Kamera gagal dibuka: ' + error.message;
    }
});

stopButton.addEventListener('click', async () => {
    if (scanner && scanner.isScanning) {
        await scanner.stop();
    }
    stopButton.hidden = true;
    startButton.hidden = false;
    startButton.disabled = false;
    message.textContent = '';
});
</script>
</body>
</html>
