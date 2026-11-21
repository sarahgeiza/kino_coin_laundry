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

        /* ===== DESKTOP (>=1024px) ===== */
        @media (min-width: 1024px) {
            .lp-mobile { display: none; }

            .landing-screen {
                max-width: 100%;
                padding: 0;
                justify-content: flex-start;
                background:
                    radial-gradient(1100px 620px at 82% -12%, rgba(37, 99, 235, 0.55), transparent 62%),
                    radial-gradient(900px 520px at -10% 108%, rgba(245, 158, 11, 0.16), transparent 60%),
                    linear-gradient(160deg, #0a1120 0%, #101c33 46%, #0c1730 100%);
                position: relative;
                overflow: hidden;
                min-height: 100vh;
            }
            .landing-screen::before {
                content: "";
                position: absolute;
                inset: 0;
                background: radial-gradient(560px circle at var(--mx, 70%) var(--my, 20%), rgba(59, 130, 246, 0.16), transparent 62%);
                transition: background 0.15s linear;
                pointer-events: none;
                z-index: 0;
            }
            .landing-screen::after {
                content: "";
                position: absolute;
                inset: 0;
                background-image: linear-gradient(rgba(255,255,255,0.028) 1px, transparent 1px),
                                  linear-gradient(90deg, rgba(255,255,255,0.028) 1px, transparent 1px);
                background-size: 72px 72px;
                mask-image: radial-gradient(circle at 50% 0%, #000 0%, transparent 78%);
                -webkit-mask-image: radial-gradient(circle at 50% 0%, #000 0%, transparent 78%);
                pointer-events: none;
                z-index: 0;
            }

            .lp-desktop {
                display: block;
                position: relative;
                z-index: 1;
                width: 100%;
            }
            .lp-shell {
                width: 100%;
                max-width: 1560px;
                margin: 0 auto;
                padding: 0 64px;
            }

            /* nav */
            .lp-nav {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 30px 0 8px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }
            .lp-brand {
                display: flex;
                align-items: center;
                gap: 14px;
                text-decoration: none;
                color: #ffffff;
            }
            .lp-brand-mark {
                width: 46px;
                height: 46px;
                border-radius: 14px;
                background: #f59e0b;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                padding: 6px;
                box-shadow: 0 8px 24px rgba(245, 158, 11, 0.28);
            }
            .lp-brand-mark img { width: 100%; height: 100%; object-fit: contain; display: block; }
            .lp-brand-name {
                font-size: 16px;
                font-weight: 800;
                letter-spacing: -0.01em;
                line-height: 1.1;
            }
            .lp-brand-sub {
                display: block;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: rgba(255, 255, 255, 0.45);
                margin-top: 3px;
            }
            .lp-nav-actions {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .lp-nav-link {
                font-size: 13px;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.72);
                text-decoration: none;
                padding: 9px 16px;
                border-radius: 999px;
                border: 1px solid transparent;
                transition: color 0.18s ease, border-color 0.18s ease, background 0.18s ease;
            }
            .lp-nav-link:hover {
                color: #ffffff;
                border-color: rgba(255, 255, 255, 0.24);
                background: rgba(255, 255, 255, 0.06);
            }
            .lp-nav-cta {
                font-size: 13px;
                font-weight: 800;
                color: #14213d;
                background: #f59e0b;
                text-decoration: none;
                padding: 11px 20px;
                border-radius: 999px;
                transition: transform 0.18s ease, box-shadow 0.18s ease;
                box-shadow: 0 10px 26px rgba(245, 158, 11, 0.22);
            }
            .lp-nav-cta:hover {
                transform: translateY(-1px);
                box-shadow: 0 14px 32px rgba(245, 158, 11, 0.32);
            }

            /* hero */
            .lp-hero {
                display: grid;
                grid-template-columns: 1.02fr 0.98fr;
                gap: 64px;
                align-items: center;
                padding: 56px 0 72px;
            }
            .lp-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 9px;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.16em;
                text-transform: uppercase;
                color: rgba(255, 255, 255, 0.62);
                border: 1px solid rgba(255, 255, 255, 0.16);
                background: rgba(255, 255, 255, 0.04);
                padding: 8px 14px;
                border-radius: 999px;
                margin-bottom: 26px;
            }
            .lp-eyebrow-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #34d399;
                box-shadow: 0 0 0 4px rgba(52, 211, 153, 0.16);
            }
            .lp-title {
                font-size: clamp(40px, 3.7vw, 58px);
                line-height: 1.02;
                letter-spacing: -0.035em;
                font-weight: 900;
                margin: 0 0 22px 0;
            }
            .lp-title span {
                background: linear-gradient(94deg, #fbbf24 0%, #f59e0b 42%, #fcd34d 100%);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .lp-lead {
                font-size: 16.5px;
                line-height: 1.7;
                color: rgba(255, 255, 255, 0.68);
                max-width: 520px;
                margin: 0 0 34px 0;
            }
            .lp-cta-row {
                display: flex;
                align-items: center;
                gap: 14px;
                flex-wrap: wrap;
            }
            .lp-btn-primary {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                background: #f59e0b;
                color: #14213d;
                font-size: 15px;
                font-weight: 800;
                text-decoration: none;
                padding: 16px 28px;
                border-radius: 14px;
                box-shadow: 0 16px 40px rgba(245, 158, 11, 0.24);
                transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
            }
            .lp-btn-primary:hover {
                transform: translateY(-2px);
                background: #fbbf24;
                box-shadow: 0 20px 48px rgba(245, 158, 11, 0.32);
            }
            .lp-btn-ghost {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                color: #ffffff;
                font-size: 15px;
                font-weight: 700;
                text-decoration: none;
                padding: 16px 26px;
                border-radius: 14px;
                border: 1px solid rgba(255, 255, 255, 0.22);
                background: rgba(255, 255, 255, 0.04);
                transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
            }
            .lp-btn-ghost:hover {
                background: rgba(255, 255, 255, 0.1);
                border-color: rgba(255, 255, 255, 0.4);
                transform: translateY(-2px);
            }
            .lp-meta {
                display: flex;
                gap: 34px;
                margin-top: 46px;
                padding-top: 26px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            .lp-meta-item strong {
                display: block;
                font-size: 19px;
                font-weight: 800;
                letter-spacing: -0.02em;
            }
            .lp-meta-item span {
                display: block;
                font-size: 11.5px;
                font-weight: 600;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: rgba(255, 255, 255, 0.45);
                margin-top: 5px;
            }

            /* visual card */
            .lp-visual {
                position: relative;
                perspective: 1200px;
            }
            .lp-card {
                position: relative;
                z-index: 1;
                border-radius: 26px;
                padding: 26px;
                background: linear-gradient(158deg, rgba(255, 255, 255, 0.13), rgba(255, 255, 255, 0.045));
                border: 1px solid rgba(255, 255, 255, 0.16);
                backdrop-filter: blur(18px);
                box-shadow: 0 34px 90px rgba(2, 8, 23, 0.55);
                transform-style: preserve-3d;
                transition: transform 0.16s ease-out;
                will-change: transform;
            }
            .lp-card-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 22px;
            }
            .lp-card-label {
                font-size: 10.5px;
                font-weight: 800;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                color: rgba(255, 255, 255, 0.42);
            }
            .lp-live {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                font-size: 11px;
                font-weight: 700;
                color: #6ee7b7;
            }
            .lp-live-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #34d399;
                animation: lpPulse 2s ease-in-out infinite;
            }
            @keyframes lpPulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.35; transform: scale(0.8); }
            }
            .lp-clock {
                font-size: 54px;
                font-weight: 900;
                letter-spacing: -0.04em;
                line-height: 1;
                font-variant-numeric: tabular-nums;
            }
            .lp-clock-sub {
                font-size: 13px;
                font-weight: 600;
                color: rgba(255, 255, 255, 0.55);
                margin-top: 8px;
            }
            .lp-shift-pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin-top: 16px;
                font-size: 12px;
                font-weight: 700;
                color: #fcd34d;
                background: rgba(245, 158, 11, 0.12);
                border: 1px solid rgba(245, 158, 11, 0.28);
                padding: 7px 13px;
                border-radius: 999px;
            }
            .lp-rows {
                margin-top: 24px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                padding-top: 6px;
            }
            .lp-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 13px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.07);
                font-size: 13.5px;
            }
            .lp-row:last-child { border-bottom: 0; }
            .lp-row span { color: rgba(255, 255, 255, 0.58); }
            .lp-row strong { font-weight: 800; letter-spacing: -0.01em; }
            .lp-chip {
                position: absolute;
                z-index: 3;
                display: inline-flex;
                align-items: center;
                gap: 9px;
                font-size: 12px;
                font-weight: 700;
                padding: 11px 15px;
                border-radius: 14px;
                background: rgba(12, 23, 48, 0.86);
                border: 1px solid rgba(255, 255, 255, 0.16);
                box-shadow: 0 18px 44px rgba(2, 8, 23, 0.5);
                backdrop-filter: blur(10px);
                animation: lpFloat 6s ease-in-out infinite;
            }
            .lp-chip-a { top: -24px; left: -30px; }
            .lp-chip-b { bottom: -24px; right: -22px; animation-delay: 1.6s; }
            @keyframes lpFloat {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-9px); }
            }

            /* features */
            .lp-features {
                padding: 8px 0 84px;
            }
            .lp-section-head {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 40px;
                margin-bottom: 30px;
            }
            .lp-section-title {
                font-size: 26px;
                font-weight: 800;
                letter-spacing: -0.02em;
                margin: 0;
            }
            .lp-section-note {
                font-size: 13px;
                color: rgba(255, 255, 255, 0.45);
                max-width: 380px;
                line-height: 1.6;
                margin: 0;
            }
            .lp-feature-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 18px;
            }
            .lp-feature {
                position: relative;
                border-radius: 20px;
                padding: 26px 24px 28px;
                background: rgba(255, 255, 255, 0.045);
                border: 1px solid rgba(255, 255, 255, 0.1);
                transition: transform 0.22s ease, border-color 0.22s ease, background 0.22s ease, box-shadow 0.22s ease;
                overflow: hidden;
            }
            .lp-feature::after {
                content: "";
                position: absolute;
                inset: 0;
                background: radial-gradient(320px circle at var(--fx, 50%) var(--fy, 0%), rgba(59, 130, 246, 0.16), transparent 65%);
                opacity: 0;
                transition: opacity 0.22s ease;
                pointer-events: none;
            }
            .lp-feature:hover {
                transform: translateY(-4px);
                border-color: rgba(147, 197, 253, 0.34);
                background: rgba(255, 255, 255, 0.07);
                box-shadow: 0 24px 60px rgba(2, 8, 23, 0.45);
            }
            .lp-feature:hover::after { opacity: 1; }
            .lp-feature-num {
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.16em;
                color: rgba(255, 255, 255, 0.3);
                margin-bottom: 18px;
            }
            .lp-feature-icon {
                width: 46px;
                height: 46px;
                border-radius: 14px;
                background: rgba(245, 158, 11, 0.14);
                border: 1px solid rgba(245, 158, 11, 0.28);
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 18px;
            }
            .lp-feature h3 {
                font-size: 16px;
                font-weight: 800;
                margin: 0 0 8px 0;
                letter-spacing: -0.01em;
            }
            .lp-feature p {
                font-size: 13.5px;
                line-height: 1.65;
                color: rgba(255, 255, 255, 0.55);
                margin: 0;
            }

            /* footer */
            .lp-footer {
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding: 24px 0 30px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                font-size: 12px;
                color: rgba(255, 255, 255, 0.42);
            }
            .lp-footer strong { color: rgba(255, 255, 255, 0.72); font-weight: 700; }

            /* reveal: animasi CSS murni, tidak bergantung JS */
            @keyframes lpIn {
                from { opacity: 0; transform: translateY(16px); }
                to { opacity: 1; transform: none; }
            }
            .lp-reveal {
                animation: lpIn 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
            }
            .lp-hero > div:first-child .lp-reveal:nth-child(1) { animation-delay: 0.02s; }
            .lp-hero > div:first-child .lp-reveal:nth-child(2) { animation-delay: 0.08s; }
            .lp-hero > div:first-child .lp-reveal:nth-child(3) { animation-delay: 0.14s; }
            .lp-hero > div:first-child .lp-reveal:nth-child(4) { animation-delay: 0.2s; }
            .lp-hero > div:first-child .lp-reveal:nth-child(5) { animation-delay: 0.26s; }
            .lp-feature:nth-child(2) { animation-delay: 0.08s; }
            .lp-feature:nth-child(3) { animation-delay: 0.16s; }
        }
        @media (prefers-reduced-motion: reduce) {
            .lp-chip, .lp-live-dot { animation: none !important; }
            .lp-reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
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
            <div class="logo-box-lg" style="background:#f59e0b; padding:12px; overflow:hidden;">
                <img src="assets/img/logo-kino-badge.png" alt="Kino Coin Laundry" style="width:100%; height:100%; object-fit:contain; display:block;">
            </div>
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

    <!-- ================= TAMPILAN DESKTOP ================= -->
    <div class="lp-desktop">
        <div class="lp-shell">
            <header class="lp-nav">
                <a href="index.php" class="lp-brand">
                    <span class="lp-brand-mark">
                        <img src="assets/img/logo-kino-badge.png" alt="Kino Coin Laundry">
                    </span>
                    <span>
                        <span class="lp-brand-name">Kino Coin Laundry</span>
                        <span class="lp-brand-sub">Coin Laundry Operations</span>
                    </span>
                </a>
                <nav class="lp-nav-actions">
                    <a href="login-kasir.php" class="lp-nav-link">Kasir</a>
                    <a href="login-pemilik.php" class="lp-nav-link">Pemilik</a>
                    <a href="pelanggan/login.php" class="lp-nav-cta">Masuk Pelanggan</a>
                </nav>
            </header>

            <main class="lp-hero">
                <div>
                    <span class="lp-eyebrow lp-reveal"><span class="lp-eyebrow-dot"></span>Buka 24 jam &middot; 4 shift &middot; 7,5 kg per putaran</span>
                    <h1 class="lp-title lp-reveal">Laundry koin yang rapi,<br><span>dari mesin sampai laporan.</span></h1>
                    <p class="lp-lead lp-reveal">
                        Satu sistem untuk transaksi kasir, token cuci &amp; kering, kartu member QR,
                        sampai rekap shift yang langsung tercatat. Tanpa buku manual, tanpa hitung ulang.
                    </p>
                    <div class="lp-cta-row lp-reveal">
                        <a href="pelanggan/login.php" class="lp-btn-primary">
                            Masuk Pelanggan
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#14213d" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h13"/><path d="M12 5l7 7-7 7"/></svg>
                        </a>
                        <a href="pelanggan/daftar.php" class="lp-btn-ghost">Daftar Pelanggan Baru</a>
                    </div>
                    <div class="lp-meta lp-reveal">
                        <div class="lp-meta-item">
                            <strong>7,5 kg</strong>
                            <span>Kapasitas per putaran</span>
                        </div>
                        <div class="lp-meta-item">
                            <strong>4 shift</strong>
                            <span>Operasional harian</span>
                        </div>
                        <div class="lp-meta-item">
                            <strong>1 kartu</strong>
                            <span>QR member permanen</span>
                        </div>
                    </div>
                </div>

                <div class="lp-visual lp-reveal">
                    <div class="lp-chip lp-chip-a">
                        <?= get_icon('qr', 16, '#fcd34d') ?>
                        Scan kartu member
                    </div>
                    <div class="lp-card" id="lpCard">
                        <div class="lp-card-head">
                            <span class="lp-card-label">Status operasional</span>
                            <span class="lp-live"><span class="lp-live-dot"></span>LIVE</span>
                        </div>
                        <div class="lp-clock" id="lpClock">--:--</div>
                        <div class="lp-clock-sub" id="lpDate">Memuat waktu lokal</div>
                        <div class="lp-shift-pill" id="lpShift">
                            <?= get_icon('clock', 14, '#fcd34d') ?>
                            <span>Menentukan shift</span>
                        </div>
                        <div class="lp-rows">
                            <div class="lp-row"><span>Layanan self service</span><strong>Cuci &middot; Kering &middot; Extra</strong></div>
                            <div class="lp-row"><span>Drop off &amp; lipat</span><strong>Dihitung per kg</strong></div>
                            <div class="lp-row"><span>Rekap shift</span><strong>Otomatis per kasir</strong></div>
                        </div>
                    </div>
                    <div class="lp-chip lp-chip-b">
                        <?= get_icon('coin', 16, '#fcd34d') ?>
                        1 putaran &middot; 2 koin
                    </div>
                </div>
            </main>

            <section class="lp-features">
                <div class="lp-section-head">
                    <h2 class="lp-section-title">Yang kamu dapat</h2>
                    <p class="lp-section-note">Tiga hal yang paling sering dipakai tim di lapangan, semuanya jalan tanpa instalasi tambahan.</p>
                </div>
                <div class="lp-feature-grid">
                    <article class="lp-feature lp-reveal">
                        <div class="lp-feature-num">01</div>
                        <div class="lp-feature-icon"><?= get_icon('coin', 22, '#fcd34d') ?></div>
                        <h3>Token Cuci &amp; Kering</h3>
                        <p>Koin mesin dihitung otomatis dari berat cucian. Satu putaran, satu koin, tanpa salah hitung di depan pelanggan.</p>
                    </article>
                    <article class="lp-feature lp-reveal">
                        <div class="lp-feature-num">02</div>
                        <div class="lp-feature-icon"><?= get_icon('rekap', 22, '#fcd34d') ?></div>
                        <h3>Rekap Otomatis</h3>
                        <p>Setiap shift mencatat total sistem, uang fisik, dan selisihnya. Laporan pemilik tinggal buka, bukan rekap ulang.</p>
                    </article>
                    <article class="lp-feature lp-reveal">
                        <div class="lp-feature-num">03</div>
                        <div class="lp-feature-icon"><?= get_icon('qr', 22, '#fcd34d') ?></div>
                        <h3>Scan Kartu Member</h3>
                        <p>Pelanggan cukup tunjukkan QR. Nama dan nomor WhatsApp langsung terisi di form transaksi kasir.</p>
                    </article>
                </div>
            </section>

            <footer class="lp-footer">
                <span>&copy; 2026 <strong>Kino Coin Laundry</strong> &middot; Sistem operasional laundry koin</span>
                <span>Staff masuk lewat menu <strong>Kasir</strong> atau <strong>Pemilik</strong> di atas.</span>
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

    // Jam + shift berjalan (mengikuti pembagian shift di sistem)
    function shiftFor(hour) {
        if (hour >= 6 && hour < 12) return 'Shift Pagi';
        if (hour >= 12 && hour < 18) return 'Shift Siang';
        if (hour >= 18) return 'Shift Malam';
        return 'Shift Midnight';
    }
    function tick() {
        if (!clock) return;
        var now = new Date();
        var hh = String(now.getHours()).padStart(2, '0');
        var mm = String(now.getMinutes()).padStart(2, '0');
        clock.textContent = hh + ':' + mm;
        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
        }
        if (shiftEl) {
            var label = shiftFor(now.getHours());
            var span = shiftEl.querySelector('span');
            if (span) span.textContent = label + ' berjalan sekarang';
        }
    }
    tick();
    setInterval(tick, 15000);

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fine = window.matchMedia('(pointer: fine)').matches;

    // Sorotan mengikuti kursor
    if (fine && !reduce) {
        root.addEventListener('mousemove', function (e) {
            var r = root.getBoundingClientRect();
            root.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100).toFixed(2) + '%');
            root.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100).toFixed(2) + '%');
        });
    }

    // Kartu miring halus mengikuti kursor
    var card = document.getElementById('lpCard');
    if (card && fine && !reduce) {
        var wrap = card.parentElement;
        wrap.addEventListener('mousemove', function (e) {
            var r = wrap.getBoundingClientRect();
            var px = (e.clientX - r.left) / r.width - 0.5;
            var py = (e.clientY - r.top) / r.height - 0.5;
            card.style.transform = 'rotateX(' + (-py * 7).toFixed(2) + 'deg) rotateY(' + (px * 9).toFixed(2) + 'deg)';
        });
        wrap.addEventListener('mouseleave', function () {
            card.style.transform = 'rotateX(0deg) rotateY(0deg)';
        });
    }

    // Sorotan di kartu fitur
    if (fine && !reduce) {
        document.querySelectorAll('.lp-feature').forEach(function (el) {
            el.addEventListener('mousemove', function (e) {
                var r = el.getBoundingClientRect();
                el.style.setProperty('--fx', (e.clientX - r.left) + 'px');
                el.style.setProperty('--fy', (e.clientY - r.top) + 'px');
            });
        });
    }

})();
</script>
</body>
</html>
