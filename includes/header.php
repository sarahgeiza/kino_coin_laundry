<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/data.php';

$page_title = $page_title ?? 'Kino Coin Laundry';
$header_role = $header_role ?? 'Kasir';
$header_user = $header_user ?? 'Daniel';
$header_avatar = $header_avatar ?? substr($header_user, 0, 1);
$header_theme = $header_theme ?? 'blue';
$header_title = $header_title ?? ('Selamat siang, ' . $header_user . ' 👋');
$header_subtitle = $header_subtitle ?? 'Shift Siang • 14:00 — 20:00 • 12 Mei 2026';
$show_app_header = $show_app_header ?? true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="app-container">
<?php if ($show_app_header): ?>
    <header class="app-header <?= $header_theme === 'orange' ? 'header-orange' : '' ?>">
        <div class="header-top">
            <span class="role-badge"><?= htmlspecialchars($header_role) ?></span>
            <div class="avatar-circle <?= $header_theme === 'orange' ? 'avatar-blue' : '' ?>">
                <?= htmlspecialchars($header_avatar) ?>
            </div>
        </div>
        <h1 class="greeting-text"><?= htmlspecialchars($header_title) ?></h1>
        <?php if (!empty($header_subtitle)): ?>
            <div class="sub-greeting">
                <span><?= htmlspecialchars($header_subtitle) ?></span>
            </div>
        <?php endif; ?>
    </header>
<?php endif; ?>
<main class="app-content">
