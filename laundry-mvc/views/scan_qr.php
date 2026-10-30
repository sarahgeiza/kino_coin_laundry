<?php
use function LaundryMvc\Config\e;

$title = 'Scan QR';
$active = 'scan';
require __DIR__ . '/partials/header.php';
?>
<h2>Scan QR Pelanggan</h2>
<div class="grid">
    <div class="card">
        <h3>Cara 1: Scanner USB</h3>
        <p class="muted">Arahkan scanner ke QR. Kode akan terbaca sebagai teks.</p>
        <form action="" method="get">
            <input type="hidden" name="page" value="detail">
            <label class="sr-only" for="kode">Kode pelanggan</label>
            <input type="text" id="kode" name="kode" placeholder="Arahkan scanner ke QR..."
                   autofocus autocomplete="off" required>
            <button class="btn" type="submit">Buka</button>
        </form>
    </div>

    <div class="card">
        <h3>Cara 2: Kamera HP / Laptop</h3>
        <p class="muted">Kamera hanya dapat digunakan melalui localhost atau HTTPS.</p>
        <button class="btn hijau" id="btnMulai" type="button">Nyalakan kamera</button>
        <button class="btn abu" id="btnStop" type="button" hidden>Matikan kamera</button>
        <div id="reader"></div>
        <p id="hasil" class="muted" aria-live="polite"></p>
    </div>
</div>
<div class="card">
    <h3>Pelanggan tidak bawa QR?</h3>
    <p class="muted">Daftarkan pelanggan baru untuk membuat QR member.</p>
    <a class="btn hijau" href="?page=baru">Daftarkan pelanggan baru</a>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const startButton = document.getElementById('btnMulai');
const stopButton = document.getElementById('btnStop');
const result = document.getElementById('hasil');
let scanner;
let cameraStream;
let nativeDetector;
let scanTimer;
let scanStarted = false;

function stopNativeScanner() {
    window.clearTimeout(scanTimer);
    if (cameraStream) {
        cameraStream.getTracks().forEach((track) => track.stop());
        cameraStream = undefined;
    }

    const video = document.querySelector('#reader video');
    if (video) {
        video.remove();
    }
}

async function openScannedCode(code) {
    if (!scanStarted) {
        return;
    }
    scanStarted = false;

    try {
        if (scanner && scanner.isScanning) {
            await scanner.stop();
        }
    } finally {
        stopNativeScanner();
        window.location.href = '?page=detail&kode=' + encodeURIComponent(code.trim());
    }
}

async function startNativeScanner() {
    if (!('BarcodeDetector' in window)) {
        throw new Error('Browser belum mendukung pemindai QR bawaan. Gunakan Chrome/Edge terbaru atau scanner USB.');
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        throw new Error('Akses kamera memerlukan localhost atau HTTPS.');
    }

    nativeDetector = new BarcodeDetector({ formats: ['qr_code'] });
    cameraStream = await navigator.mediaDevices.getUserMedia({
        audio: false,
        video: { facingMode: { ideal: 'environment' } }
    });

    const video = document.createElement('video');
    video.autoplay = true;
    video.muted = true;
    video.playsInline = true;
    video.srcObject = cameraStream;
    document.getElementById('reader').replaceChildren(video);
    await video.play();

    const detect = async () => {
        if (!scanStarted) {
            return;
        }
        try {
            const codes = await nativeDetector.detect(video);
            if (codes.length > 0 && codes[0].rawValue) {
                await openScannedCode(codes[0].rawValue);
                return;
            }
        } catch (error) {
            result.textContent = 'Pemindaian gagal: ' + error.message;
            scanStarted = false;
            stopNativeScanner();
            startButton.hidden = false;
            startButton.disabled = false;
            stopButton.hidden = true;
            return;
        }
        scanTimer = window.setTimeout(detect, 250);
    };

    scanStarted = true;
    result.textContent = 'Arahkan kamera ke QR pelanggan...';
    detect();
}

startButton.addEventListener('click', async () => {
    startButton.disabled = true;
    result.textContent = 'Meminta akses kamera...';
    try {
        if (window.Html5Qrcode) {
            scanner = new Html5Qrcode('reader');
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                openScannedCode,
                () => {}
            );
            scanStarted = true;
        } else {
            await startNativeScanner();
        }
        startButton.hidden = true;
        stopButton.hidden = false;
    } catch (error) {
        scanStarted = false;
        stopNativeScanner();
        result.textContent = 'Kamera gagal dibuka: ' + error.message;
        startButton.disabled = false;
    }
});

stopButton.addEventListener('click', async () => {
    scanStarted = false;
    try {
        if (scanner && scanner.isScanning) {
            await scanner.stop();
        }
    } finally {
        stopNativeScanner();
    }
    stopButton.hidden = true;
    startButton.hidden = false;
    startButton.disabled = false;
    result.textContent = '';
});
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
