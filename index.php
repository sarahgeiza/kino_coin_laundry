<?php
session_start();
require_once __DIR__ . '/includes/data.php';

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
        .features-grid-landing {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }
        .feature-card-landing {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            backdrop-filter: blur(4px);
        }
        .cta-box-landing {
            margin-top: auto;
            padding-top: 20px;
        }

        /* Desktop responsiveness for landing */
        @media (min-width: 768px) {
            .landing-screen {
                padding: 40px 50px 40px 50px;
                border-radius: 24px;
                margin: 30px auto;
                min-height: calc(100vh - 60px);
            }
            .features-grid-landing {
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
            }
            .feature-card-landing {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }
            .cta-buttons-row {
                display: flex;
                gap: 14px;
                max-width: 500px;
            }
        }
    </style>
</head>
<body>

<div class="app-screen landing-screen">

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer">
        <?php if ($show_toast_logout): ?>
            <div class="toast toast-dark">
                <span>ℹ</span>
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
        <p style="font-size: 14px; opacity: 0.9; line-height: 1.6; margin-bottom: 28px; max-width: 650px;">
            Sistem manajemen operasional laundry modern berbasis koin. Kelola transaksi, pantau bisnis, dan cek riwayat cucian dalam satu aplikasi.
        </p>

        <!-- 3 Feature Cards (1 kol di mobile, 3 kol di desktop) -->
        <div class="features-grid-landing">
            <div class="feature-card-landing">
                <div style="font-size: 26px;">🪙</div>
                <div>
                    <strong style="font-size: 14px; display: block; margin-bottom: 2px;">Token Cuci &amp; Kering</strong>
                    <span style="font-size: 12px; opacity: 0.82;">Pembayaran khusus layanan Self Service</span>
                </div>
            </div>

            <div class="feature-card-landing">
                <div style="font-size: 26px;">📊</div>
                <div>
                    <strong style="font-size: 14px; display: block; margin-bottom: 2px;">Rekap Otomatis</strong>
                    <span style="font-size: 12px; opacity: 0.82;">Laporan shift, kasir, dan berat tercatat rapi</span>
                </div>
            </div>

            <div class="feature-card-landing">
                <div style="font-size: 26px;">💳</div>
                <div>
                    <strong style="font-size: 14px; display: block; margin-bottom: 2px;">Scan Kartu Member</strong>
                    <span style="font-size: 12px; opacity: 0.82;">Input kilat dengan QR code pelanggan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Buttons Bawah -->
    <div class="cta-box-landing">
        <div class="cta-buttons-row">
            <a href="pelanggan/login.php" class="btn btn-orange" style="margin-bottom: 10px; font-size: 15px; padding: 14px;">
                Masuk Pelanggan
            </a>
            <a href="pelanggan/daftar.php" class="btn" style="background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.4); color: white; margin-bottom: 18px; padding: 14px;">
                Daftar Pelanggan Baru
            </a>
        </div>

        <div style="font-size: 11px; opacity: 0.75; line-height: 1.6;">
            Staff? Masuk lewat <strong>Kasir</strong> / <strong>Pemilik</strong> di pojok kanan atas.<br>
            &copy; 2026 Kino Coin Laundry
        </div>
    </div>

</div>

<script src="js/app.js"></script>
</body>
</html>
