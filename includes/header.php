<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/data.php';

$page_title = $page_title ?? 'Kino Coin Laundry';
$app_role = $app_role ?? 'kasir'; // 'kasir' | 'pemilik' | 'pelanggan'
$active_tab = $active_tab ?? 'home';
$show_app_header = $show_app_header ?? true;
$show_bottom_nav = $show_bottom_nav ?? true;

$user_name = $_SESSION['username'] ?? ($app_role === 'pemilik' ? 'Pemilik' : ($app_role === 'pelanggan' ? 'Budi' : 'Daniel'));
$user_avatar = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <script>
        // Inject mock state for client API service
        window.__MOCK_SERVICES = <?= json_encode($_SESSION['services'] ?? []) ?>;
        window.__MOCK_MEMBERS = <?= json_encode($_SESSION['members'] ?? []) ?>;
        window.__MOCK_TRANSACTIONS = <?= json_encode($_SESSION['transactions'] ?? []) ?>;
    </script>
</head>
<body class="role-<?= $app_role ?>">

<div class="app-screen">

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <?php if ($show_app_header): ?>

        <!-- ===================================================================
             1. DESKTOP TOP NAVBAR (Tampil di Laptop / PC, Tersembunyi di Mobile)
             =================================================================== -->
        <header class="desktop-navbar">
            <div class="desktop-nav-inner">
                <!-- Left: Brand Logo & Title -->
                <div class="desktop-nav-brand">
                    <div class="desktop-logo-badge">KC</div>
                    <div>
                        <div class="desktop-brand-title">Kino Coin Laundry</div>
                        <div class="desktop-brand-role">
                            <?php if ($app_role === 'kasir'): ?>
                                <span class="badge badge-token">Kasir Operasional</span>
                            <?php elseif ($app_role === 'pemilik'): ?>
                                <span class="badge" style="background:#fef3c7; color:#b45309;">👑 Mode Pemilik</span>
                            <?php else: ?>
                                <span class="badge badge-tunai">Portal Pelanggan</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Center: Navigation Links -->
                <nav class="desktop-nav-menu">
                    <?php if ($app_role === 'kasir'): ?>
                        <a href="index.php" class="desktop-nav-link <?= $active_tab === 'home' ? 'active' : '' ?>">🏠 Home</a>
                        <a href="transaksi.php" class="desktop-nav-link <?= $active_tab === 'transaksi' ? 'active' : '' ?>">📋 Transaksi</a>
                        <a href="pelanggan.php" class="desktop-nav-link <?= $active_tab === 'pelanggan' ? 'active' : '' ?>">👥 Pelanggan</a>
                        <a href="rekap.php" class="desktop-nav-link <?= $active_tab === 'rekap' ? 'active' : '' ?>">⏰ Rekap Shift</a>
                    <?php elseif ($app_role === 'pemilik'): ?>
                        <a href="index.php" class="desktop-nav-link <?= $active_tab === 'home' ? 'active' : '' ?>">👑 Monitoring</a>
                        <a href="laporan.php" class="desktop-nav-link <?= $active_tab === 'laporan' ? 'active' : '' ?>">📊 Laporan</a>
                        <a href="master.php" class="desktop-nav-link <?= $active_tab === 'master' ? 'active' : '' ?>">🧺 Master Data</a>
                        <a href="users.php" class="desktop-nav-link <?= $active_tab === 'user' ? 'active' : '' ?>">👤 Pengguna</a>
                        <a href="presensi.php" class="desktop-nav-link <?= $active_tab === 'presensi' ? 'active' : '' ?>">🕒 Presensi Kasir</a>
                    <?php elseif ($app_role === 'pelanggan'): ?>
                        <a href="index.php" class="desktop-nav-link <?= $active_tab === 'riwayat' ? 'active' : '' ?>">🧾 Riwayat Cucian</a>
                        <a href="javascript:void(0)" onclick="handleOpenCustomerQR()" class="desktop-nav-link">💳 Kartu QR Member</a>
                    <?php endif; ?>
                </nav>

                <!-- Right: Shift Info, User Profile, Quick CTA & Logout -->
                <div class="desktop-nav-user">
                    <?php if ($app_role === 'kasir'): ?>
                        <div class="desktop-shift-pill">
                            <span class="dot-online"></span>
                            <span>Shift Siang (14:00 — 20:00)</span>
                        </div>
                        <a href="transaksi-baru.php" class="btn btn-primary btn-sm-desktop" style="width: auto; padding: 7px 14px; font-size: 12px;">
                            + Transaksi Baru
                        </a>
                    <?php elseif ($app_role === 'pemilik'): ?>
                        <div class="desktop-shift-pill" style="background:#f1f5f9; color:#0f172a;">
                            <span>🏢 Owner View</span>
                        </div>
                    <?php elseif ($app_role === 'pelanggan'): ?>
                        <div class="desktop-shift-pill" style="background:#fff7ed; color:#ea580c;">
                            <span>📍 Buka 24 Jam</span>
                        </div>
                    <?php endif; ?>

                    <!-- User Avatar & Name -->
                    <div class="desktop-user-badge">
                        <div class="desktop-avatar <?= $app_role === 'pemilik' ? 'avatar-owner' : ($app_role === 'pelanggan' ? 'avatar-cust' : '') ?>">
                            <?= $user_avatar ?>
                        </div>
                        <span class="desktop-username"><?= htmlspecialchars($user_name) ?></span>
                    </div>

                    <!-- Logout Button -->
                    <button type="button" class="btn-logout-desktop" onclick="openModal('modalLogoutConfirm')" title="Keluar">
                        <span>Keluar</span>
                        <span style="font-size: 15px;">🚪</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- ===================================================================
             2. MOBILE HEADER (Tampil di HP, Tersembunyi di Laptop / PC)
             =================================================================== -->
        <div class="mobile-header-only">
            <?php if ($app_role === 'kasir'): ?>
                <header class="header-staff">
                    <div class="header-top">
                        <div class="header-title-box">
                            <h1>Selamat siang,<br><?= htmlspecialchars($user_name) ?></h1>
                            <p>
                                <span class="badge badge-token" style="background: rgba(255,255,255,0.2); color: white; border: none;">Shift Siang</span>
                                <span>• 14:00 — 20:00 • 31 Okt 2026</span>
                            </p>
                        </div>
                        <div class="header-avatar"><?= $user_avatar ?></div>
                    </div>
                </header>

            <?php elseif ($app_role === 'pemilik'): ?>
                <header class="header-owner">
                    <div class="header-top">
                        <div class="header-title-box">
                            <h1>Selamat malam, Pemilik</h1>
                            <p style="color: #94a3b8;">Dashboard Monitoring Bisnis</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="javascript:void(0)" onclick="openModal('modalLogoutConfirm')" title="Keluar" style="color: rgba(255,255,255,0.7); text-decoration: none; font-size: 18px;">🚪</a>
                            <div class="header-avatar" style="background: #3b82f6;">A</div>
                        </div>
                    </div>
                </header>

            <?php elseif ($app_role === 'pelanggan'): ?>
                <header class="header-customer">
                    <div class="header-top">
                        <div class="header-title-box">
                            <h1>Selamat datang,<br><?= htmlspecialchars($user_name) ?></h1>
                            <p>
                                <span style="background: rgba(255,255,255,0.25); padding: 2px 8px; border-radius: 12px; font-weight: 700;">📍 Kino Coin Laundry</span>
                                <span>• Buka 24 Jam</span>
                            </p>
                        </div>
                        <div class="header-avatar" style="background: #1e3a8a;"><?= $user_avatar ?></div>
                    </div>
                </header>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <main class="content-area">
