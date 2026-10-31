<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/icons.php';

$page_title = $page_title ?? 'Kino Coin Laundry';
$app_role = $app_role ?? 'kasir'; // 'kasir' | 'pemilik' | 'pelanggan'
$active_tab = $active_tab ?? 'home';
$show_app_header = $show_app_header ?? true;
$show_bottom_nav = $show_bottom_nav ?? true;

$user_name = $_SESSION['username'] ?? ($app_role === 'pemilik' ? 'Pemilik' : ($app_role === 'pelanggan' ? 'Budi' : 'Daniel'));
$user_avatar = strtoupper(substr($user_name, 0, 1));

// Resolusi shift kasir secara dinamis
$active_shift = ($app_role === 'kasir') ? get_active_kasir_shift($user_name) : null;
$shift_name_badge = $active_shift['nama'] ?? 'Shift Siang';
$shift_time_label = $active_shift['jam_tampil'] ?? '14:00 — 20:00';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        window.__MOCK_SERVICES = <?= json_encode($_SESSION['services'] ?? []) ?>;
        window.__MOCK_MEMBERS = <?= json_encode($_SESSION['members'] ?? []) ?>;
        window.__MOCK_TRANSACTIONS = <?= json_encode($_SESSION['transactions'] ?? []) ?>;
    </script>
</head>
<body>

<div class="app-screen">

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <?php if ($show_app_header): ?>
        <?php if ($app_role === 'kasir'): ?>
            <header class="header-staff">
                <div class="header-top">
                    <div class="header-title-box">
                        <h1>Selamat siang,<br><?= htmlspecialchars($user_name) ?></h1>
                        <p>
                            <span class="badge badge-token" style="background: rgba(255,255,255,0.2); color: white; border: none;"><?= htmlspecialchars($shift_name_badge) ?></span>
                            <span>• <?= htmlspecialchars($shift_time_label) ?> • <?= date('d M Y') ?></span>
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
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <a href="javascript:void(0)" onclick="openModal('modalLogoutConfirm')" title="Keluar" style="color: rgba(255,255,255,0.7); display: flex; align-items: center;">
                            <?= get_icon('keluar', 20, '#ffffff') ?>
                        </a>
                        <div class="header-avatar blue-avatar">A</div>
                    </div>
                </div>
            </header>

        <?php elseif ($app_role === 'pelanggan'): ?>
            <header class="header-customer">
                <div class="header-top">
                    <div class="header-title-box">
                        <h1>Selamat datang,<br><?= htmlspecialchars($user_name) ?></h1>
                        <p>
                            <span style="background: rgba(255,255,255,0.25); padding: 2px 8px; border-radius: 12px; font-weight: 700;">Kino Coin Laundry</span>
                            <span>• Buka 24 Jam</span>
                        </p>
                    </div>
                    <div class="header-avatar" style="background: #1e3a8a;"><?= $user_avatar ?></div>
                </div>
            </header>
        <?php endif; ?>
    <?php endif; ?>

    <main class="content-area">
