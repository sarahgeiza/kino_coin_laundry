<?php
use function LaundryMvc\Config\e;

$title = 'Pelanggan Baru';
$active = 'baru';
$old = $old ?? ['nama_pelanggan' => '', 'no_hp' => ''];
$errors = $errors ?? [];
require __DIR__ . '/partials/header.php';
?>
<h2>Pelanggan Baru</h2>
<div class="card form-card">
    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" autocomplete="off">
        <?= \LaundryMvc\Config\csrfField() ?>
        <input type="hidden" name="action" value="tambah">

        <label for="nama_pelanggan">Nama</label>
        <input type="text" id="nama_pelanggan" name="nama_pelanggan"
               value="<?= e($old['nama_pelanggan']) ?>"
               placeholder="Contoh: Budi Santoso" minlength="2" maxlength="100" required autofocus>

        <label for="no_hp">No HP</label>
        <input type="text" id="no_hp" name="no_hp" value="<?= e($old['no_hp']) ?>"
               placeholder="Contoh: 081234567890" inputmode="tel" required>

        <button class="btn" type="submit">Simpan &amp; Buat QR</button>
    </form>
    <p class="muted">Setelah disimpan, QR pelanggan langsung muncul di halaman berikutnya.</p>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
