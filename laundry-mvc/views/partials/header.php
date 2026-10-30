<?php
use function LaundryMvc\Config\consumeFlash;
use function LaundryMvc\Config\e;

$flashMessage = consumeFlash();
$menu = [
    'home' => 'Beranda',
    'baru' => 'Pelanggan Baru',
    'scan' => 'Scan QR',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - Kino Koin Laundry</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
    <div class="bar">
        <h1>Kino Koin Laundry</h1>
        <nav>
            <?php foreach ($menu as $key => $label): ?>
                <a href="?page=<?= e($key) ?>" class="<?= $active === $key ? 'aktif' : '' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<main>
    <?php if ($flashMessage !== null): ?>
        <div class="alert <?= e($flashMessage['type']) ?>"><?= e($flashMessage['msg']) ?></div>
    <?php endif; ?>
