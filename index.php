<?php
session_start();
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/icons.php';

$show_toast_logout = (isset($_GET['logout']) && $_GET['logout'] == '1');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kino Coin Laundry - Landing Page</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .landing-screen {
            background: linear-gradient(180deg, #1e3a8a 0%, #1d4ed8 45%, #2563eb 100%);
            color: #ffffff;
            padding: 24px 22px 30px 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .landing-top-nav {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 24px;
        }
        .btn-role-tag {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.35);
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            backdrop-filter: blur(4px);
            transition: all 0.2s;
        }
        .btn-role-tag:hover {
            background: #ffffff;
            color: #1e3a8a;
        }
        .logo-box-lg {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: #f59e0b;
            color: #1e3a8a;
            font-size: 28px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4);
            margin-bottom: 14px;
        }
        .feature-card-landing {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 10px;
            backdrop-filter: blur(4px);
        }
        .icon-circle-landing {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<div class="app-screen landing-screen">

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer">
        <?php if ($show_toast_logout): ?>
            <div class="toast toast-dark">
                <?= get_icon('info', 16, '#ffffff') ?>
                <span>Anda telah logout</span>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <!-- Tombol Kasir | Pemilik di pojok kanan atas -->
        <div class="landing-top-nav">
            <a href="login-kasir.php" class="btn-role-tag">Kasir</a>
            <a href="login-pemilik.php" class="btn-role-tag">Pemilik</a>
        </div>

        <!-- Hero Brand -->
        <div class="logo-box-lg">KC</div>
        <h1 style="font-size: 28px; font-weight: 900; line-height: 1.2; margin-bottom: 8px;">Kino Coin Laundry</h1>
        <p style="font-size: 14px; opacity: 0.9; line-height: 1.6; margin-bottom: 24px;">
            Sistem manajemen operasional laundry modern berbasis koin. Kelola transaksi, pantau bisnis, dan cek riwayat cucian dalam satu aplikasi.
        </p>

        <!-- 3 Feature Cards with SVG Vector Icons (Persis Figma A1) -->
        <div class="feature-card-landing">
            <div class="icon-circle-landing">
                <?= get_icon('coin', 22, '#ffffff') ?>
            </div>
            <div>
                <strong style="font-size: 14px; display: block; margin-bottom: 2px;">Token Cuci &amp; Kering</strong>
                <span style="font-size: 12px; opacity: 0.82;">Pembayaran khusus layanan Self Service</span>
            </div>
        </div>

        <div class="feature-card-landing">
            <div class="icon-circle-landing">
                <?= get_icon('rekap', 22, '#ffffff') ?>
            </div>
            <div>
                <strong style="font-size: 14px; display: block; margin-bottom: 2px;">Rekap Otomatis</strong>
                <span style="font-size: 12px; opacity: 0.82;">Laporan shift, kasir, dan berat tercatat rapi</span>
            </div>
        </div>

        <div class="feature-card-landing">
            <div class="icon-circle-landing">
                <?= get_icon('qr', 22, '#ffffff') ?>
            </div>
            <div>
                <strong style="font-size: 14px; display: block; margin-bottom: 2px;">Scan Kartu Member</strong>
                <span style="font-size: 12px; opacity: 0.82;">Input kilat dengan QR code pelanggan</span>
            </div>
        </div>
    </div>

    <!-- CTA Buttons Bawah -->
    <div style="margin-top: 24px;">
        <a href="pelanggan/login.php" class="btn btn-orange" style="margin-bottom: 10px; font-size: 15px; padding: 14px;">
            Masuk Pelanggan
        </a>
        <a href="pelanggan/daftar.php" class="btn" style="background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.4); color: white; margin-bottom: 18px; padding: 14px;">
            Daftar Pelanggan Baru
        </a>

        <div style="font-size: 11px; opacity: 0.75; line-height: 1.6; text-align: center;">
            Staff? Masuk lewat <strong>Kasir</strong> / <strong>Pemilik</strong> di pojok kanan atas.<br>
            &copy; 2026 Kino Coin Laundry
        </div>
    </div>

</div>

<script defer src="js/alpine.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>
