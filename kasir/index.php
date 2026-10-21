<?php
$page_title = 'Kasir Dashboard - Kino Coin Laundry';
$header_role = 'Kasir';
$header_user = 'Daniel';
$header_avatar = 'D';
$header_title = 'Selamat siang, Daniel 👋';
$header_subtitle = 'Shift Siang • 14:00 — 20:00 • 12 Mei 2026';
$current_role = 'kasir';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';

// Calculate summary from session transactions
$total_income = 0;
$total_trx = count($_SESSION['transactions']);
$coins_500 = 0;
$coins_1000 = 0;

foreach ($_SESSION['transactions'] as $t) {
    $total_income += $t['total_biaya'];
    $coins_500 += $t['koin_500'];
    $coins_1000 += $t['koin_1000'];
}
?>

<!-- Ringkasan Shift Siang (Kartu Metrik) -->
<div class="metrics-scroll">
    <div class="metric-card blue">
        <div class="metric-label">Uang Masuk</div>
        <div class="metric-value"><?= format_rupiah($total_income) ?></div>
        <div class="metric-sub"><?= $total_trx ?> transaksi</div>
    </div>
    <div class="metric-card orange">
        <div class="metric-label">Koin 500</div>
        <div class="metric-value"><?= number_format($coins_500, 0, ',', '.') ?> pcs</div>
        <div class="metric-sub">= <?= format_rupiah($coins_500 * 500) ?></div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Koin 1000</div>
        <div class="metric-value"><?= number_format($coins_1000, 0, ',', '.') ?> pcs</div>
        <div class="metric-sub">= <?= format_rupiah($coins_1000 * 1000) ?></div>
    </div>
</div>

<!-- Info Banner -->
<div class="info-banner">
    <span style="font-size: 18px;">💡</span>
    <span>Tercatat otomatis: tanggal, waktu, kasir, berat, koin.</span>
</div>

<!-- Daftar Transaksi Terakhir -->
<div class="card">
    <div class="card-title">
        <span>Transaksi Terakhir</span>
        <a href="transaksi.php" style="color: var(--primary-light); font-size: 13px; text-decoration: none; font-weight: 600;">Lihat Semua</a>
    </div>

    <?php 
    $recent_transactions = array_slice(array_reverse($_SESSION['transactions']), 0, 3);
    foreach ($recent_transactions as $trx): 
        $coin_desc = [];
        if ($trx['koin_500'] > 0) $coin_desc[] = $trx['koin_500'] . '×500';
        if ($trx['koin_1000'] > 0) $coin_desc[] = $trx['koin_1000'] . '×1000';
        $coin_str = implode(' + ', $coin_desc) ?: '0 koin';
    ?>
    <div class="transaction-item">
        <div class="trx-header">
            <div>
                <span class="trx-id"><?= htmlspecialchars($trx['id']) ?></span>
                <div class="trx-customer"><?= htmlspecialchars($trx['pelanggan']) ?></div>
            </div>
            <span class="badge badge-success"><?= htmlspecialchars($trx['status']) ?></span>
        </div>
        <div class="trx-service"><?= htmlspecialchars($trx['layanan']) ?> • <?= number_format($trx['berat'], 1, ',', '.') ?> kg</div>
        <div class="trx-footer">
            <div>
                <div class="trx-amount"><?= format_rupiah($trx['total_biaya']) ?></div>
                <div class="trx-meta"><?= htmlspecialchars($trx['waktu']) ?> | Kasir <?= htmlspecialchars($trx['kasir']) ?> | <?= $coin_str ?></div>
            </div>
            <button type="button" class="btn-detail" onclick="showTrxDetail(<?= htmlspecialchars(json_encode($trx)) ?>)">Detail</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Floating Action Button: Transaksi Baru -->
<a href="transaksi-baru.php" class="fab" title="Transaksi Baru">+</a>

<!-- Modal Detail Transaksi -->
<div class="modal-overlay" id="trxModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTrxId">Detail Transaksi</h3>
            <button class="modal-close" data-close-modal>&times;</button>
        </div>
        <div id="modalBody" style="font-size: 14px; line-height: 1.6;">
            <!-- Injected via JS -->
        </div>
        <button type="button" class="btn btn-primary" style="margin-top: 18px;" data-close-modal>Tutup</button>
    </div>
</div>

<script>
function showTrxDetail(data) {
    document.getElementById('modalTrxId').textContent = data.id + ' - ' + data.pelanggan;
    const body = `
        <div style="background:#f8fafc; padding:12px; border-radius:8px; margin-bottom:12px;">
            <p><strong>Pelanggan:</strong> ${data.pelanggan}</p>
            <p><strong>WhatsApp:</strong> ${data.telepon || '-'}</p>
            <p><strong>Layanan:</strong> ${data.layanan}</p>
            <p><strong>Timbangan:</strong> ${data.berat} kg</p>
        </div>
        <div style="background:#f8fafc; padding:12px; border-radius:8px; margin-bottom:12px;">
            <p><strong>Koin 500:</strong> ${data.koin_500} keping (${data.koin_500 * 500})</p>
            <p><strong>Koin 1000:</strong> ${data.koin_1000} keping (${data.koin_1000 * 1000})</p>
            <p><strong>Total Koin:</strong> Rp ${(data.total_koin_nilai || 0).toLocaleString('id-ID')}</p>
            <p><strong>Total Biaya:</strong> Rp ${(data.total_biaya || 0).toLocaleString('id-ID')}</p>
        </div>
        <p style="font-size:12px; color:#64748b;">Dicatat pada ${data.tanggal} ${data.waktu} oleh Kasir ${data.kasir} (${data.shift})</p>
    `;
    document.getElementById('modalBody').innerHTML = body;
    openModal('trxModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
