<?php
$page_title = 'Pelanggan - Kino Coin Laundry';
$header_role = 'Pelanggan';
$header_user = 'Budi';
$header_avatar = 'B';
$header_theme = 'orange';
$header_title = 'Selamat datang, Budi 👋';
$header_subtitle = 'Pantau antrian cucian & tarif resmi Kino Laundry';
$current_role = 'pelanggan';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Info Toko -->
<div style="background: rgba(249, 115, 22, 0.12); border: 1px solid rgba(249, 115, 22, 0.3); border-radius: var(--radius-md); padding: 12px 18px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
    <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #c2410c; font-size: 14px;">
        <span>📍</span>
        <span>Kino Coin Laundry</span>
    </div>
    <span style="background: #ea580c; color: white; font-size: 11px; font-weight: 800; padding: 4px 12px; border-radius: 20px;">
        Buka 24 Jam
    </span>
</div>

<!-- Alert Box -->
<div class="info-banner" style="background: #fffbeb; border-color: #fef08a; color: #b45309; margin-bottom: 20px;">
    <span style="font-size: 18px;">💡</span>
    <span>Halaman ini menampilkan antrian laundry Anda secara real-time.</span>
</div>

<!-- 2 Kolom di Desktop/Laptop, 1 Kolom di HP -->
<div class="responsive-grid-2">
    <!-- Kolom Kiri: Antrian Saat Ini -->
    <div class="card">
        <div class="card-title">
            <span>Antrian Saat Ini</span>
            <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">3 Mesin Berjalan</span>
        </div>

        <?php 
        $badge_styles = [
            'Proses' => 'badge-warning',
            'Antri' => 'badge-primary',
            'Siap Diambil' => 'badge-success'
        ];
        foreach ($_SESSION['antrian'] as $antri): 
            $badge_cls = $badge_styles[$antri['status']] ?? 'badge-warning';
        ?>
        <div class="queue-card">
            <div class="queue-number-box">
                <?= htmlspecialchars($antri['nomor']) ?>
            </div>
            <div class="queue-details">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <strong style="font-size: 14px;"><?= htmlspecialchars($antri['pelanggan']) ?></strong>
                    <span class="badge <?= $badge_cls ?>"><?= htmlspecialchars($antri['status']) ?></span>
                </div>
                <div style="font-size: 12px; color: var(--text-muted);">
                    <?= htmlspecialchars($antri['layanan']) ?>
                </div>
                <div class="queue-time" style="margin-top: 4px; font-weight: 600;">
                    ⏱️ Estimasii: <?= htmlspecialchars($antri['estimasi']) ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Kolom Kanan: Informasi Layanan & Tarif -->
    <div class="card">
        <div class="card-title">
            <span>Informasi Layanan &amp; Tarif</span>
            <span style="font-size: 12px; color: var(--primary-light); font-weight: 600;">Resmi</span>
        </div>

        <?php foreach ($_SESSION['services'] as $s): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color);">
            <div>
                <div style="font-weight: 700; font-size: 14px;"><?= htmlspecialchars($s['nama']) ?></div>
                <div style="font-size: 12px; color: var(--text-muted);">Estimasi <?= htmlspecialchars($s['durasi']) ?></div>
            </div>
            <div style="font-weight: 800; font-size: 15px; color: var(--primary);">
                <?= format_rupiah($s['tarif']) ?><span style="font-size: 11px; font-weight: normal; color: var(--text-muted);">/kg</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
