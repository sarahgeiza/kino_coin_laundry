<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    <link rel="stylesheet" href="assets/css/style.css">
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
            width: 112px;
            height: 112px;
            border-radius: 28px;
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

        /* ===== MOBILE WRAPPER: transparan supaya layout Figma tidak berubah ===== */
        .lp-mobile { display: contents; }
        .lp-desktop { display: none; }

        /* ===== TABLET (768-1023px): kartu terpusat, bukan melar ===== */
        @media (min-width: 768px) and (max-width: 1023px) {
            body { background: #eef2f7; }
            .landing-screen {
                max-width: 560px;
                margin: 0 auto;
                box-shadow: 0 18px 50px rgba(15, 23, 42, 0.14);
            }
        }

        /* ===== DESKTOP (>=1024px): gaya sama dengan versi HP, hanya melebar ===== */
        @media (min-width: 1024px) {
            .lp-mobile { display: none; }

            .landing-screen {
                max-width: 100%;
                padding: 0;
                justify-content: flex-start;
                background:
                    radial-gradient(900px 520px at 88% -6%, rgba(96, 165, 250, 0.35), transparent 62%),
                    radial-gradient(760px 460px at 6% 104%, rgba(76, 29, 149, 0.45), transparent 62%),
                    linear-gradient(180deg, #1e3a8a 0%, #1d4ed8 45%, #2563eb 100%);
                position: relative;
                overflow: hidden;
                min-height: 100vh;
            }
            .landing-screen::before {
                content: "";
                position: absolute;
                inset: 0;
                background: radial-gradient(620px circle at var(--mx, 78%) var(--my, 18%), rgba(255, 255, 255, 0.10), transparent 62%);
                pointer-events: none;
                z-index: 0;
            }

            .lp-desktop {
                display: flex;
                flex-direction: column;
                position: relative;
                z-index: 1;
                width: 100%;
                min-height: 100vh;
            }
            .lp-shell {
                width: 100%;
                max-width: 1280px;
                margin: 0 auto;
                padding: 0 56px;
                display: flex;
                flex-direction: column;
                flex: 1;
            }

            /* nav — sama seperti pill di versi HP */
            .lp-nav {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 26px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            }
            .lp-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                text-decoration: none;
                color: #ffffff;
            }
            .lp-brand-mark {
                width: 44px;
                height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .lp-brand-mark img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                display: block;
                filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.25));
            }
            .lp-brand-name {
                font-size: 16px;
                font-weight: 800;
                letter-spacing: -0.01em;
            }
            .lp-nav-actions {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .lp-nav-link {
                background: rgba(255, 255, 255, 0.16);
                color: #ffffff;
                border: 1px solid rgba(255, 255, 255, 0.35);
                padding: 8px 18px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 700;
                text-decoration: none;
                backdrop-filter: blur(4px);
                transition: all 0.2s;
            }
            .lp-nav-link:hover { background: #ffffff; color: #1e3a8a; }
            .lp-nav-cta {
                background: #ea580c;
                color: #ffffff;
                padding: 10px 22px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 800;
                text-decoration: none;
                box-shadow: 0 6px 18px rgba(234, 88, 12, 0.32);
                transition: transform 0.18s ease, box-shadow 0.18s ease;
            }
            .lp-nav-cta:hover {
                transform: translateY(-1px);
                box-shadow: 0 10px 24px rgba(234, 88, 12, 0.42);
            }

            /* hero */
            .lp-hero {
                display: grid;
                grid-template-columns: 1.04fr 0.96fr;
                gap: 64px;
                align-items: center;
                padding: 52px 0 44px;
                flex: 1;
            }
            .lp-logo-lg {
                width: 104px;
                height: 104px;
                border-radius: 26px;
                background: #f59e0b;
                padding: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4);
                margin-bottom: 20px;
            }
            .lp-logo-lg img { width: 100%; height: 100%; object-fit: contain; display: block; }
            .lp-logo-hero {
                width: 190px;
                max-width: 100%;
                height: auto;
                display: block;
                margin-bottom: 18px;
                filter: drop-shadow(0 10px 24px rgba(0, 0, 0, 0.28));
            }
            .lp-title {
                font-size: clamp(36px, 3.4vw, 52px);
                font-weight: 900;
                line-height: 1.12;
                letter-spacing: -0.02em;
                margin: 0 0 12px 0;
                color: #ffffff;
            }
            .lp-lead {
                font-size: 15.5px;
                line-height: 1.65;
                color: rgba(255, 255, 255, 0.94);
                max-width: 520px;
                margin: 0 0 28px 0;
            }
            .lp-cta-row {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
            }
            .lp-btn-primary {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #ea580c;
                color: #ffffff;
                font-size: 15px;
                font-weight: 800;
                text-decoration: none;
                padding: 15px 28px;
                border-radius: 999px;
                box-shadow: 0 8px 22px rgba(234, 88, 12, 0.35);
                transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            }
            .lp-btn-primary:hover {
                background: #f97316;
                transform: translateY(-2px);
                box-shadow: 0 12px 28px rgba(234, 88, 12, 0.45);
            }
            .lp-btn-secondary {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.15);
                border: 1.5px solid rgba(255, 255, 255, 0.4);
                color: #ffffff;
                font-size: 15px;
                font-weight: 700;
                text-decoration: none;
                padding: 15px 26px;
                border-radius: 999px;
                backdrop-filter: blur(4px);
                transition: background 0.18s ease, transform 0.18s ease;
            }
            .lp-btn-secondary:hover {
                background: rgba(255, 255, 255, 0.24);
                transform: translateY(-2px);
            }
            .lp-meta {
                display: flex;
                gap: 28px;
                margin-top: 34px;
                flex-wrap: wrap;
            }
            .lp-meta-item strong {
                display: block;
                font-size: 17px;
                font-weight: 800;
            }
            .lp-meta-item span {
                display: block;
                font-size: 11.5px;
                font-weight: 600;
                color: rgba(255, 255, 255, 0.75);
                margin-top: 3px;
            }

            /* kartu status — kaca biru, sama seperti kartu fitur di HP */
            .lp-visual { perspective: 1100px; }
            .lp-card {
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 22px;
                padding: 24px;
                backdrop-filter: blur(12px);
                box-shadow: 0 18px 44px rgba(15, 23, 42, 0.22);
                transform-style: preserve-3d;
                transition: transform 0.16s ease-out;
                will-change: transform;
            }
            .lp-card-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }
            .lp-card-label {
                font-size: 12px;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.8);
            }
            .lp-live {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 11px;
                font-weight: 700;
                color: #ffffff;
                background: rgba(255, 255, 255, 0.16);
                border: 1px solid rgba(255, 255, 255, 0.28);
                padding: 5px 10px;
                border-radius: 999px;
            }
            .lp-live-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #4ade80;
            }
            .lp-clock {
                font-size: 46px;
                font-weight: 900;
                letter-spacing: -0.02em;
                line-height: 1;
                font-variant-numeric: tabular-nums;
            }
            .lp-clock-sub {
                font-size: 12.5px;
                color: rgba(255, 255, 255, 0.8);
                margin-top: 6px;
            }
            .lp-shift-pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-top: 14px;
                font-size: 12px;
                font-weight: 700;
                color: #ffffff;
                background: rgba(245, 158, 11, 0.28);
                border: 1px solid rgba(245, 158, 11, 0.55);
                padding: 7px 13px;
                border-radius: 999px;
            }
            .lp-rows {
                margin-top: 18px;
                border-top: 1px solid rgba(255, 255, 255, 0.18);
                padding-top: 4px;
            }
            .lp-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 11px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.14);
                font-size: 13px;
                gap: 16px;
            }
            .lp-row:last-child { border-bottom: 0; }
            .lp-row span { color: rgba(255, 255, 255, 0.82); }
            .lp-row strong { font-weight: 800; text-align: right; }

            /* kartu fitur — identik dengan kartu fitur HP */
            .lp-features { padding: 6px 0 46px; }
            .lp-feature-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 14px;
            }
            .lp-feature {
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 16px;
                padding: 16px 18px;
                display: flex;
                align-items: center;
                gap: 14px;
                backdrop-filter: blur(4px);
                transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
            }
            .lp-feature:hover {
                transform: translateY(-3px);
                background: rgba(255, 255, 255, 0.18);
                border-color: rgba(255, 255, 255, 0.34);
            }
            .lp-feature-icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.2);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .lp-feature h3 {
                font-size: 14px;
                font-weight: 800;
                margin: 0 0 2px 0;
            }
            .lp-feature p {
                font-size: 12px;
                line-height: 1.5;
                color: rgba(255, 255, 255, 0.85);
                margin: 0;
            }

            .lp-footer {
                border-top: 1px solid rgba(255, 255, 255, 0.18);
                padding: 18px 0 26px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                font-size: 11.5px;
                color: rgba(255, 255, 255, 0.8);
            }
            .lp-footer strong { color: #ffffff; font-weight: 700; }
        }
        @media (prefers-reduced-motion: reduce) {
            .lp-live-dot { animation: none !important; }
        }
    </style>
</head>
<body>

<div class="app-screen landing-screen" id="lpRoot">

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer">
        <?php if ($show_toast_logout): ?>
            <div class="toast toast-dark">
                <?= get_icon('info', 16, '#ffffff') ?>
                <span>Anda telah logout</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- ================= TAMPILAN HP (PERSIS FIGMA) ================= -->
    <div class="lp-mobile">
        <div>
            <!-- Tombol Kasir | Pemilik di pojok kanan atas -->
            <div class="landing-top-nav">
                <a href="login-kasir.php" class="btn-role-tag">Kasir</a>
                <a href="login-pemilik.php" class="btn-role-tag">Pemilik</a>
            </div>

            <!-- Hero Brand -->
            <img src="assets/img/logo-kino-white.png" alt="Kino Coin Laundry"
             style="width: 160px; max-width: 55%; height: auto; display: block; margin-bottom: 14px; filter: drop-shadow(0 8px 18px rgba(0,0,0,0.22));">
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

    <!-- ================= TAMPILAN DESKTOP (gaya sama, melebar) ================= -->
    <div class="lp-desktop">
        <div class="lp-shell">
            <header class="lp-nav">
                <a href="index.php" class="lp-brand">
                    <span class="lp-brand-mark"><img src="assets/img/logo-kino-white.png" alt="Kino Coin Laundry"></span>
                    <span class="lp-brand-name">Kino Coin Laundry</span>
                </a>
                <nav class="lp-nav-actions">
                    <a href="login-kasir.php" class="lp-nav-link">Kasir</a>
                    <a href="login-pemilik.php" class="lp-nav-link">Pemilik</a>
                    <a href="pelanggan/login.php" class="lp-nav-cta">Masuk Pelanggan</a>
                </nav>
            </header>

            <main class="lp-hero">
                <div>
                    <img class="lp-logo-hero" src="assets/img/logo-kino-white.png" alt="Kino Coin Laundry">
                    <h1 class="lp-title">Kino Coin Laundry</h1>
                    <p class="lp-lead">
                        Sistem manajemen operasional laundry modern berbasis koin. Kelola transaksi, pantau bisnis, dan cek riwayat cucian dalam satu aplikasi.
                    </p>
                    <div class="lp-cta-row">
                        <a href="pelanggan/login.php" class="lp-btn-primary">Masuk Pelanggan</a>
                        <a href="pelanggan/daftar.php" class="lp-btn-secondary">Daftar Pelanggan Baru</a>
                    </div>
                    <div class="lp-meta">
                        <div class="lp-meta-item"><strong>7,5 kg</strong><span>Kapasitas per putaran</span></div>
                        <div class="lp-meta-item"><strong>4 shift</strong><span>Operasional harian</span></div>
                        <div class="lp-meta-item"><strong>1 kartu</strong><span>QR member permanen</span></div>
                    </div>
                </div>

                <div class="lp-visual">
                    <div class="lp-card" id="lpCard">
                        <div class="lp-card-head">
                            <span class="lp-card-label">Status operasional</span>
                            <span class="lp-live"><span class="lp-live-dot"></span>Live</span>
                        </div>
                        <div class="lp-clock" id="lpClock">--:--</div>
                        <div class="lp-clock-sub" id="lpDate">Memuat waktu lokal</div>
                        <div class="lp-shift-pill" id="lpShift">
                            <?= get_icon('clock', 14, '#ffffff') ?>
                            <span>Menentukan shift</span>
                        </div>
                        <div class="lp-rows">
                            <div class="lp-row"><span>Layanan self service</span><strong>Cuci &middot; Kering &middot; Extra</strong></div>
                            <div class="lp-row"><span>Drop off &amp; lipat</span><strong>Dihitung per kg</strong></div>
                            <div class="lp-row"><span>Rekap shift</span><strong>Otomatis per kasir</strong></div>
                        </div>
                    </div>
                </div>
            </main>

            <section class="lp-features">
                <div class="lp-feature-grid">
                    <article class="lp-feature">
                        <div class="lp-feature-icon"><?= get_icon('coin', 22, '#ffffff') ?></div>
                        <div>
                            <h3>Token Cuci &amp; Kering</h3>
                            <p>Pembayaran khusus layanan Self Service</p>
                        </div>
                    </article>
                    <article class="lp-feature">
                        <div class="lp-feature-icon"><?= get_icon('rekap', 22, '#ffffff') ?></div>
                        <div>
                            <h3>Rekap Otomatis</h3>
                            <p>Laporan shift, kasir, dan berat tercatat rapi</p>
                        </div>
                    </article>
                    <article class="lp-feature">
                        <div class="lp-feature-icon"><?= get_icon('qr', 22, '#ffffff') ?></div>
                        <div>
                            <h3>Scan Kartu Member</h3>
                            <p>Input kilat dengan QR code pelanggan</p>
                        </div>
                    </article>
                </div>
            </section>

            <footer class="lp-footer">
                <span>Staff? Masuk lewat <strong>Kasir</strong> / <strong>Pemilik</strong> di pojok kanan atas.</span>
                <span>&copy; 2026 Kino Coin Laundry</span>
            </footer>
        </div>
    </div>

</div>

<script defer src="assets/js/vendor/alpine.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
(function () {
    var root = document.getElementById('lpRoot');
    if (!root) return;

    var clock = document.getElementById('lpClock');
    var dateEl = document.getElementById('lpDate');
    var shiftEl = document.getElementById('lpShift');

    function shiftFor(hour) {
        if (hour >= 6 && hour < 12) return 'Shift Pagi';
        if (hour >= 12 && hour < 18) return 'Shift Siang';
        if (hour >= 18) return 'Shift Malam';
        return 'Shift Midnight';
    }
    function tick() {
        if (!clock) return;
        var now = new Date();
        clock.textContent = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }
        if (shiftEl) {
            var span = shiftEl.querySelector('span');
            if (span) span.textContent = shiftFor(now.getHours()) + ' berjalan sekarang';
        }
    }
    tick();
    setInterval(tick, 15000);

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fine = window.matchMedia('(pointer: fine)').matches;

    if (fine && !reduce) {
        root.addEventListener('mousemove', function (e) {
            var r = root.getBoundingClientRect();
            root.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100).toFixed(2) + '%');
            root.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100).toFixed(2) + '%');
        });
    }

    var card = document.getElementById('lpCard');
    if (card && fine && !reduce) {
        var wrap = card.parentElement;
        wrap.addEventListener('mousemove', function (e) {
            var r = wrap.getBoundingClientRect();
            var px = (e.clientX - r.left) / r.width - 0.5;
            var py = (e.clientY - r.top) / r.height - 0.5;
            card.style.transform = 'rotateX(' + (-py * 5).toFixed(2) + 'deg) rotateY(' + (px * 6).toFixed(2) + 'deg)';
        });
        wrap.addEventListener('mouseleave', function () {
            card.style.transform = 'rotateX(0deg) rotateY(0deg)';
        });
    }
})();
</script>
</body>
</html>
