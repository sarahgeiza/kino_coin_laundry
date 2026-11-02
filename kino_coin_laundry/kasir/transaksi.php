<?php
$page_title = 'Transaksi Shift Ini - Kino Coin Laundry';
$header_role = 'Kasir';
$header_user = '';
$header_avatar = 'D';
$header_title = 'Transaksi Shift Ini';
$header_subtitle = 'Semua transaksi tercatat pada shift berjalan';
$current_role = 'kasir';
$active_tab = 'transaksi';

require_once __DIR__ . '/../includes/header.php';
$csrfToken = web_csrf_token();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
    <span style="font-size: 13px; color: var(--text-muted); font-weight: 600;">
        Total: <?= count($transactions) ?> Transaksi
    </span>
    <a href="transaksi-baru.php" class="btn btn-primary" style="width: auto; padding: 8px 16px; font-size: 13px;">
        + Transaksi Baru
    </a>
</div>

<!-- List of Cards -->
<?php foreach (array_reverse($transactions) as $trx): 
    $coin_str = (int)$trx['jumlah_koin_mesin'] . ' koin mesin';
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
            <div class="trx-meta"><?= htmlspecialchars($trx['waktu']) ?> | Kasir <?= htmlspecialchars($trx['kasir']) ?> | <?= $coin_str ?> | <?= strtoupper(web_h($trx['jenis_pembayaran'])) ?></div>
        </div>
        <button type="button" class="btn-detail" onclick="showTrxDetail(<?= htmlspecialchars(json_encode($trx)) ?>)">Detail</button>
    </div>
    <div style="display:flex;gap:8px;align-items:center;margin-top:10px;">
        <label style="font-size:12px;color:var(--text-muted);" for="status-<?= (int)$trx['id_transaksi'] ?>">Status cucian</label>
        <select id="status-<?= (int)$trx['id_transaksi'] ?>" class="form-control js-order-status" data-id="<?= (int)$trx['id_transaksi'] ?>" data-previous="<?= web_h($trx['status_pengerjaan']) ?>" style="max-width:190px;">
            <?php foreach (['antri'=>'Antri','proses'=>'Proses','siap_diambil'=>'Siap Diambil','selesai'=>'Selesai','diambil'=>'Diambil'] as $value=>$label): ?>
            <option value="<?= $value ?>" <?= $trx['status_pengerjaan'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
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
document.querySelectorAll('.js-order-status').forEach(select => select.addEventListener('change', async function() {
    const previous = this.dataset.previous;
    try {
        const response = await fetch('../api/index.php?module=transaksi&action=status', {
            method:'POST', credentials:'same-origin',
            headers:{'Content-Type':'application/json','X-CSRF-Token:<?= web_h($csrfToken) ?>'},
            body:JSON.stringify({id_transaksi:Number(this.dataset.id),status_pengerjaan:this.value})
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Gagal memperbarui status');
        this.dataset.previous = this.value;
    } catch (error) {
        alert(error.message);
        this.value = previous;
    }
}));
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[char]));
}
function showTrxDetail(data) {
    document.getElementById('modalTrxId').textContent = data.id;
    const body = `
        <div style="background:#f8fafc; padding:12px; border-radius:8px; margin-bottom:12px; border:1px solid #e2e8f0;">
            <p><strong>Nama Pelanggan:</strong> ${escapeHtml(data.pelanggan)}</p>
            <p><strong>No. WhatsApp:</strong> ${data.telepon || '-'}</p>
            <p><strong>Layanan:</strong> ${escapeHtml(data.layanan)}</p>
            <p><strong>Berat Cucian:</strong> ${data.berat} kg</p>
        </div>
        <div style="background:#f8fafc; padding:12px; border-radius:8px; margin-bottom:12px; border:1px solid #e2e8f0;">
            <p><strong>Kebutuhan koin mesin:</strong> ${Number(data.jumlah_koin_mesin || 0)} koin</p>
            <p><strong>Metode pembayaran:</strong> ${escapeHtml(String(data.jenis_pembayaran || '').toUpperCase())}</p>
            <p style="font-size:16px; margin-top:4px;"><strong>Total Tagihan:</strong> Rp ${(data.total_biaya || 0).toLocaleString('id-ID')}</p>
            <p><strong>Sudah dibayar:</strong> Rp ${(data.jumlah_dibayar || 0).toLocaleString('id-ID')}</p>
            <p><strong>Sisa pembayaran:</strong> Rp ${(data.sisa_bayar || 0).toLocaleString('id-ID')}</p>
            <p><strong>Status:</strong> <span class="badge ${data.status === 'Lunas' ? 'badge-success' : 'badge-danger'}">${escapeHtml(data.status)}</span></p>
        </div>
        <div style="font-size:12px; color:#64748b;">
            Tercatat pada ${escapeHtml(data.tanggal)} pukul ${escapeHtml(data.waktu)} oleh kasir ${escapeHtml(data.kasir)} (Shift ${escapeHtml(data.shift)}).
        </div>
    `;
    document.getElementById('modalBody').innerHTML = body;
    openModal('trxModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
