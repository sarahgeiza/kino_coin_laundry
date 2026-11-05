<?php
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
check_auth_guard('kasir');

$page_title = 'Daftar Transaksi - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'transaksi';

require_once __DIR__ . '/../includes/header.php';

$filter = in_array($_GET['filter'] ?? '', ['Self Service', 'Drop Off'], true)
    ? $_GET['filter']
    : 'Semua';
$activeShift = one(
    "SELECT id_shift_operasional
     FROM shift_operasional
     WHERE id_kasir = ? AND status = 'aktif'
     ORDER BY id_shift_operasional DESC
     LIMIT 1",
    [(int)$_SESSION['user_id']]
);
$dropOffServiceCondition = "LOWER(BTRIM(l.nama_layanan)) LIKE '%drop off%' OR LOWER(BTRIM(l.nama_layanan)) = 'lipat'";
$selfServiceCondition = "LOWER(BTRIM(l.nama_layanan)) IN ('cuci', 'kering', 'cuci kering', 'extra spin', 'extra dry')";
$filterCondition = match ($filter) {
    'Self Service' => "HAVING BOOL_OR($selfServiceCondition) AND NOT BOOL_OR($dropOffServiceCondition)",
    'Drop Off' => "HAVING BOOL_OR($dropOffServiceCondition)",
    default => '',
};
$tx_list = $activeShift ? rows(
    "SELECT t.no_invoice AS id,
            p.nama_pelanggan AS pelanggan,
            p.no_hp AS wa,
            STRING_AGG(DISTINCT l.nama_layanan, ', ' ORDER BY l.nama_layanan) AS layanan,
            SUM(COALESCE(d.berat, d.jumlah::numeric))::numeric AS kuantitas,
            t.total_bayar,
            t.total_bayar AS total_biaya,
            t.jenis_pembayaran AS metode,
            INITCAP(REPLACE(t.status_pembayaran, '_', ' ')) AS status,
            u.nama AS kasir,
            s.nama_shift AS shift,
            TO_CHAR(t.tanggal_transaksi, 'DD Mon YYYY') AS tanggal,
            TO_CHAR(t.tanggal_transaksi, 'HH24:MI') AS waktu,
            COALESCE(SUM(d.jumlah_koin), 0)::int AS total_koin_mesin,
            CASE WHEN BOOL_OR($dropOffServiceCondition) THEN 'DROP_OFF'
                 WHEN BOOL_OR($selfServiceCondition) THEN 'SELF_SERVICE'
                 ELSE 'ADDITIONAL' END AS kategori
     FROM transaksi t
     JOIN pelanggan p USING (id_pelanggan)
     JOIN pengguna u ON u.id_pengguna = t.id_kasir
     JOIN shift s USING (id_shift)
     JOIN detail_transaksi d USING (id_transaksi)
     JOIN layanan l USING (id_layanan)
     WHERE t.id_shift_operasional = ?
     GROUP BY t.id_transaksi, p.id_pelanggan, u.id_pengguna, s.id_shift
     $filterCondition
     ORDER BY t.tanggal_transaksi DESC",
    [(int)$activeShift['id_shift_operasional']]
) : [];
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
        $coin_desc = (int)$trx['total_koin_mesin'] . ' koin mesin • ' . strtoupper($trx['metode']);
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
                <span style="font-size: 11px; font-weight: 800; color: var(--primary);" id="detailInvoiceId">Invoice</span>
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

    const paymentMethod = String(trx.metode || '').toLowerCase() === 'qris' ? 'QRIS' : 'Cash';
    const paymentDetail = `
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; margin-bottom: 12px; color: #15803d;">
            <strong>Metode Pembayaran: ${paymentMethod}</strong>
            <p style="font-size: 11px; margin-top: 2px;">Koin mesin adalah token operasional, bukan pembayaran.</p>
            <p style="font-size: 11px; margin-top: 2px;">Koin mesin digunakan: ${Number(trx.total_koin_mesin || 0).toLocaleString('id-ID')}</p>
        </div>
    `;

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
