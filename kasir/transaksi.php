<?php
$page_title = 'Transaksi Shift Ini - Kino Coin Laundry';
$header_role = 'Kasir';
$header_user = 'Daniel';
$header_avatar = 'D';
$header_title = 'Transaksi Shift Ini';
$header_subtitle = 'Semua transaksi tercatat pada shift berjalan';
$current_role = 'kasir';
$active_tab = 'transaksi';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
    <span style="font-size: 13px; color: var(--text-muted); font-weight: 600;">
        Total: <?= count($_SESSION['transactions']) ?> Transaksi
    </span>
    <a href="transaksi-baru.php" class="btn btn-primary" style="width: auto; padding: 8px 16px; font-size: 13px;">
        + Transaksi Baru
    </a>
</div>

<!-- List of Cards -->
<?php foreach (array_reverse($_SESSION['transactions']) as $trx): 
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
        <span class="badge <?= $trx['status'] === 'Lunas' ? 'badge-success' : 'badge-danger' ?>">
            <?= htmlspecialchars($trx['status']) ?>
        </span>
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

<!-- Modal Detail Transaksi -->
<div class="modal-overlay" id="trxModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTrxId">Detail Faktur</h3>
            <button class="modal-close" data-close-modal>&times;</button>
        </div>
        <div id="modalBody" style="font-size: 14px; line-height: 1.6;"></div>
        <button type="button" class="btn btn-primary" style="margin-top: 18px;" data-close-modal>Tutup</button>
    </div>
</div>

<script>
function showTrxDetail(data) {
    document.getElementById('modalTrxId').textContent = data.id;
    const body = `
        <div style="background:#f8fafc; padding:12px; border-radius:8px; margin-bottom:12px; border:1px solid #e2e8f0;">
            <p><strong>Nama Pelanggan:</strong> ${data.pelanggan}</p>
            <p><strong>No. WhatsApp:</strong> ${data.telepon || '-'}</p>
            <p><strong>Layanan:</strong> ${data.layanan}</p>
            <p><strong>Berat Cucian:</strong> ${data.berat} kg</p>
        </div>
        <div style="background:#f8fafc; padding:12px; border-radius:8px; margin-bottom:12px; border:1px solid #e2e8f0;">
            <p><strong>Koin 500:</strong> ${data.koin_500} pcs (Rp ${(data.koin_500 * 500).toLocaleString('id-ID')})</p>
            <p><strong>Koin 1000:</strong> ${data.koin_1000} pcs (Rp ${(data.koin_1000 * 1000).toLocaleString('id-ID')})</p>
            <p><strong>Nilai Koin Masuk:</strong> Rp ${(data.total_koin_nilai || 0).toLocaleString('id-ID')}</p>
            <p style="font-size:16px; margin-top:4px;"><strong>Total Biaya:</strong> Rp ${(data.total_biaya || 0).toLocaleString('id-ID')}</p>
            <p><strong>Status:</strong> <span class="badge ${data.status === 'Lunas' ? 'badge-success' : 'badge-danger'}">${data.status}</span></p>
        </div>
        <div style="font-size:12px; color:#64748b;">
            Tercatat pada ${data.tanggal} pukul ${data.waktu} oleh kasir ${data.kasir} (Shift ${data.shift}).
        </div>
    `;
    document.getElementById('modalBody').innerHTML = body;
    openModal('trxModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
