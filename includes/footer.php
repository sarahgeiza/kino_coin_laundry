<?php
require_once __DIR__ . '/icons.php';

$app_role = $app_role ?? 'kasir';
$active_tab = $active_tab ?? 'home';
$show_bottom_nav = $show_bottom_nav ?? true;
?>
    </main>

    <?php if ($show_bottom_nav): ?>
        <nav class="bottom-nav">
            <?php if ($app_role === 'kasir'): ?>
                <a href="index.php" class="nav-tab <?= $active_tab === 'home' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('home', 20) ?></span>
                    <span>Home</span>
                </a>
                <a href="transaksi.php" class="nav-tab <?= $active_tab === 'transaksi' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('transaksi', 20) ?></span>
                    <span>Transaksi</span>
                </a>
                <a href="pelanggan.php" class="nav-tab <?= $active_tab === 'pelanggan' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('pelanggan', 20) ?></span>
                    <span>Pelanggan</span>
                </a>
                <a href="rekap.php" class="nav-tab <?= $active_tab === 'rekap' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('rekap', 20) ?></span>
                    <span>Rekap</span>
                </a>
                <a href="javascript:void(0)" onclick="openModal('modalLogoutConfirm')" class="nav-tab">
                    <span class="tab-icon"><?= get_icon('keluar', 20) ?></span>
                    <span>Keluar</span>
                </a>

            <?php elseif ($app_role === 'pemilik'): ?>
                <a href="index.php" class="nav-tab <?= $active_tab === 'home' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('home', 20) ?></span>
                    <span>Home</span>
                </a>
                <a href="laporan.php" class="nav-tab <?= $active_tab === 'laporan' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('laporan', 20) ?></span>
                    <span>Laporan</span>
                </a>
                <a href="master.php" class="nav-tab <?= $active_tab === 'master' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('master', 20) ?></span>
                    <span>Master</span>
                </a>
                <a href="users.php" class="nav-tab <?= $active_tab === 'user' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('user', 20) ?></span>
                    <span>User</span>
                </a>
                <a href="presensi.php" class="nav-tab <?= $active_tab === 'presensi' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('presensi', 20) ?></span>
                    <span>Presensi</span>
                </a>

            <?php elseif ($app_role === 'pelanggan'): ?>
                <a href="index.php" class="nav-tab <?= $active_tab === 'riwayat' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('home', 20) ?></span>
                    <span>Riwayat</span>
                </a>
                <a href="javascript:void(0)" onclick="handleOpenCustomerQR()" class="nav-tab <?= $active_tab === 'kartu' ? 'active' : '' ?>">
                    <span class="tab-icon"><?= get_icon('qr', 20) ?></span>
                    <span>Kartu QR</span>
                </a>
                <a href="javascript:void(0)" onclick="openModal('modalLogoutConfirm')" class="nav-tab">
                    <span class="tab-icon"><?= get_icon('keluar', 20) ?></span>
                    <span>Keluar</span>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <!-- Modal M5 / C3: Konfirmasi Keluar (Persis Figma) -->
    <div class="sheet-overlay" id="modalLogoutConfirm">
        <div class="sheet-card">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title">Keluar dari akun?</h3>
                <button type="button" class="sheet-close" onclick="closeModal('modalLogoutConfirm')">&times;</button>
            </div>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
                Anda akan kembali ke halaman beranda.
            </p>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalLogoutConfirm')">Batal</button>
                <a href="../logout.php" class="btn btn-primary" style="background: #1e3a8a;">Ya, Keluar</a>
            </div>
        </div>
    </div>

</div><!-- end .app-screen -->

<script src="../assets/js/vendor/qrcode.min.js"></script>
<script src="../assets/js/vendor/html5-qrcode.min.js"></script>
<script src="../assets/js/api.js"></script>
<script defer src="../assets/js/vendor/alpine.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
