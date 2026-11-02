<?php
$current_role = $current_role ?? 'kasir';
$active_tab = $active_tab ?? 'home';
$show_bottom_nav = $show_bottom_nav ?? true;
?>
</main>

<?php if ($show_bottom_nav): ?>
<nav class="bottom-nav">
    <div class="bottom-nav-inner">
        <?php if ($current_role === 'kasir'): ?>
            <a href="index.php" class="nav-item <?= $active_tab === 'home' ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span>
                <span>Home</span>
            </a>
            <a href="transaksi.php" class="nav-item <?= $active_tab === 'transaksi' ? 'active' : '' ?>">
                <span class="nav-icon">📋</span>
                <span>Transaksi</span>
            </a>
            <a href="tutup-shift.php" class="nav-item <?= $active_tab === 'shift' ? 'active' : '' ?>">
                <span class="nav-icon">⏰</span>
                <span>Shift</span>
            </a>
            <a href="../logout.php" data-logout data-csrf="<?= htmlspecialchars(web_csrf_token(), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
                <span class="nav-icon">🚪</span>
                <span>Keluar</span>
            </a>
        <?php elseif ($current_role === 'admin'): ?>
            <a href="index.php" class="nav-item <?= $active_tab === 'home' ? 'active' : '' ?>">
                <span class="nav-icon">👑</span>
                <span>Home</span>
            </a>
            <a href="layanan.php" class="nav-item <?= $active_tab === 'layanan' ? 'active' : '' ?>">
                <span class="nav-icon">🧺</span>
                <span>Layanan</span>
            </a>
            <a href="laporan.php" class="nav-item <?= $active_tab === 'laporan' ? 'active' : '' ?>">
                <span class="nav-icon">📊</span>
                <span>Laporan</span>
            </a>
            <a href="../logout.php" data-logout data-csrf="<?= htmlspecialchars(web_csrf_token(), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
                <span class="nav-icon">🚪</span>
                <span>Keluar</span>
            </a>
        <?php elseif ($current_role === 'pelanggan'): ?>
            <a href="index.php" class="nav-item <?= $active_tab === 'home' ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span>
                <span>Home</span>
            </a>
            <a href="riwayat.php" class="nav-item <?= $active_tab === 'riwayat' ? 'active' : '' ?>">
                <span class="nav-icon">🧾</span>
                <span>Riwayat</span>
            </a>
            <a href="../logout.php" data-logout data-csrf="<?= htmlspecialchars(web_csrf_token(), ENT_QUOTES, 'UTF-8') ?>" class="nav-item">
                <span class="nav-icon">🚪</span>
                <span>Keluar</span>
            </a>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>

</div><!-- end app-container -->
<script src="../js/app.js"></script>
<script>
document.querySelectorAll('[data-logout]').forEach(link => link.addEventListener('click', async event => {
    event.preventDefault();
    try {
        const response = await fetch(link.href, {
            method:'POST', credentials:'same-origin',
            headers:{'X-CSRF-Token':link.dataset.csrf}
        });
        if (response.redirected) location.href = response.url;
        else location.href = '../index.php';
    } catch (_) { location.href = '../index.php'; }
}));
</script>
</body>
</html>
