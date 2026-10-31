<?php
$page_title = 'Daftar Transaksi - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'transaksi';

require_once __DIR__ . '/../includes/header.php';

$filter = $_GET['filter'] ?? 'Semua';

$tx_list = $_SESSION['transactions'];
if ($filter === 'Self Service') {
    $tx_list = array_filter($tx_list, fn($t) => ($t['kategori'] ?? '') === 'SELF_SERVICE');
} elseif ($filter === 'Drop Off') {
    $tx_list = array_filter($tx_list, fn($t) => ($t['kategori'] ?? '') !== 'SELF_SERVICE');
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <h2 style="font-size: 16px; font-weight: 800;">Transaksi Shift Ini</h2>
        <span style="font-size: 11px; color: var(--text-muted);"><?= count($tx_list) ?> transaksi ditemukan</span>
    </div>
    <a href="transaksi-baru.php" class="btn btn-primary" style="width: auto; padding: 7px 14px; font-size: 12px;">
        + Baru
    </a>
</div>

<!-- Filter Chips: Semua, Self Service, Drop Off -->
<div class="filter-scroll">
    <a href="transaksi.php?filter=Semua" class="chip <?= $filter === 'Semua' ? 'active' : '' ?>">Semua</a>
    <a href="transaksi.php?filter=Self+Service" class="chip <?= $filter === 'Self Service' ? 'active' : '' ?>">Self Service</a>
    <a href="transaksi.php?filter=Drop+Off" class="chip <?= $filter === 'Drop Off' ? 'active' : '' ?>">Drop Off</a>
</div>

<!-- List of Transactions -->
<?php if (empty($tx_list)): ?>
    <div class="card" style="text-align: center; padding: 30px; color: var(--text-muted); font-size: 13px;">
        Tidak ada transaksi pada kategori ini.
    </div>
<?php else: ?>
    <?php foreach (array_reverse($tx_list) as $trx): 
        $meta_coin = [];
        if (!empty($trx['koin_cuci'])) $meta_coin[] = $trx['koin_cuci'] . ' Coin Cuci';
        if (!empty($trx['koin_kering'])) $meta_coin[] = $trx['koin_kering'] . ' Coin Kering';
        $coin_desc = !empty($meta_coin) ? implode(' + ', $meta_coin) : ($trx['metode'] ?? 'Tunai');
    ?>
    <div class="trx-item" onclick="showInvoiceDetail(<?= htmlspecialchars(json_encode($trx)) ?>)">
        <div class="trx-top">
            <div>
                <span class="trx-id"><?= htmlspecialchars($trx['id']) ?></span>
                <div class="trx-name"><?= htmlspecialchars($trx['pelanggan']) ?></div>
            </div>
            <span class="badge badge-lunas"><?= htmlspecialchars($trx['status']) ?></span>
        </div>
        <div class="trx-meta-line">
            <?= htmlspecialchars($trx['layanan']) ?> • <?= htmlspecialchars($trx['kuantitas']) ?>
        </div>
        <div class="trx-bottom">
            <div>
                <div class="trx-price"><?= format_rupiah($trx['total_bayar']) ?></div>
                <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">
                    <?= htmlspecialchars($trx['waktu']) ?> | Kasir <?= htmlspecialchars($trx['kasir']) ?> | <?= $coin_desc ?>
                </div>
            </div>
            <button type="button" class="trx-details-btn">Detail</button>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Floating Action Button + -->
<a href="transaksi-baru.php" class="fab-btn" title="Transaksi Baru">+</a>

<!-- Modal M2 / M3: Detail Transaksi -->
<div class="sheet-overlay" id="modalTrxDetail">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <div>
                <span style="font-size: 11px; font-weight: 800; color: var(--primary);" id="detailInvoiceId">INV-2026-001</span>
                <h3 class="sheet-title" id="detailCustomerName">Budi Santoso</h3>
            </div>
            <button type="button" class="sheet-close" onclick="closeModal('modalTrxDetail')">&times;</button>
        </div>
        <div id="detailContentBody" style="font-size: 13px; line-height: 1.6;"></div>
        <button type="button" class="btn btn-outline" style="margin-top: 18px;" onclick="closeModal('modalTrxDetail')">
            Tutup
        </button>
    </div>
</div>

<script>
function showInvoiceDetail(trx) {
    document.getElementById('detailInvoiceId').textContent = trx.id;
    document.getElementById('detailCustomerName').textContent = trx.pelanggan;

    const isToken = (trx.metode === 'Token');
    let paymentDetail = '';

    if (isToken) {
        paymentDetail = `
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
                <div style="font-size: 11px; font-weight: 800; color: #1e40af; text-transform: uppercase; margin-bottom: 6px;">
                    Rincian Token Dibayar
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Coin Cuci (${trx.koin_cuci || 0} pcs):</span>
                    <strong>Rp ${((trx.koin_cuci || 0) * 7000).toLocaleString('id-ID')}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Coin Kering (${trx.koin_kering || 0} pcs):</span>
                    <strong>Rp ${((trx.koin_kering || 0) * 10000).toLocaleString('id-ID')}</strong>
                </div>
            </div>
        `;
    } else {
        paymentDetail = `
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; margin-bottom: 12px; color: #15803d;">
                <strong>✔ Metode Bayar: Tunai / Transfer (Non-Token)</strong>
                <p style="font-size: 11px; margin-top: 2px;">Diterima lunas di meja kasir tanpa penggunaan koin mesin.</p>
            </div>
        `;
    }

    const html = `
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 12px;">
            <p><strong>Layanan:</strong> ${trx.layanan}</p>
            <p><strong>Kuantitas:</strong> ${trx.kuantitas}</p>
            <p><strong>WhatsApp:</strong> ${trx.wa || '-'}</p>
            <p><strong>Waktu:</strong> ${trx.tanggal} • ${trx.waktu}</p>
            <p><strong>Kasir:</strong> ${trx.kasir} (${trx.shift})</p>
        </div>
        ${paymentDetail}
        <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 900; padding: 10px 0; border-top: 1px dashed #e2e8f0;">
            <span>Total Tagihan:</span>
            <span>Rp ${(trx.total_biaya || 0).toLocaleString('id-ID')}</span>
        </div>
    `;

    document.getElementById('detailContentBody').innerHTML = html;
    openModal('modalTrxDetail');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
