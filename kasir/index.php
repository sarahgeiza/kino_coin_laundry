<?php
$page_title = 'Kasir Dashboard - Kino Coin Laundry';
$header_role = 'Kasir';
$header_user = '';
$header_avatar = 'D';
$current_role = 'kasir';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';
$shiftOptions = web_rows('SELECT id_shift,nama_shift,jam_mulai,jam_selesai FROM shift ORDER BY id_shift');

// Calculate summary from database-backed transactions for the active shift
$total_income = 0;
$total_trx = count($transactions);
$total_cash = 0;
$total_qris = 0;
$total_kg = array_sum(array_column($transactions, 'berat'));

foreach ($transactions as $t) {
    $total_income += $t['total_biaya'];
    if (($t['jenis_pembayaran'] ?? '') === 'cash') $total_cash += (int)$t['total_biaya'];
    if (($t['jenis_pembayaran'] ?? '') === 'qris') $total_qris += (int)$t['total_biaya'];
}
?>

<!-- Ringkasan Shift Aktif (Kartu Metrik) -->
<div class="metrics-scroll">
    <div class="metric-card blue">
        <div class="metric-label">Uang Masuk</div>
        <div class="metric-value"><?= format_rupiah($total_income) ?></div>
        <div class="metric-sub"><?= $total_trx ?> transaksi</div>
    </div>
    <div class="metric-card orange">
        <div class="metric-label">Pembayaran Cash</div>
        <div class="metric-value"><?= format_rupiah($total_cash) ?></div>
        <div class="metric-sub">Transaksi tunai</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Pembayaran QRIS</div>
        <div class="metric-value"><?= format_rupiah($total_qris) ?></div>
        <div class="metric-sub">Pembayaran non-tunai</div>
    </div>
    <div class="metric-card blue">
        <div class="metric-label">Berat Cucian</div>
        <div class="metric-value"><?= number_format($total_kg,1,',','.') ?> kg</div>
        <div class="metric-sub">Shift aktif</div>
    </div>
</div>

<?php if (!$activeOperationalShift): ?>
<div class="card">
    <div class="card-title">Mulai Shift</div>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Pilih jadwal shift sebelum mencatat transaksi.</p>
    <form id="openShiftForm">
        <input type="hidden" id="shiftCsrf" value="<?= web_h(web_csrf_token()) ?>">
        <div class="form-group">
            <label class="form-label" for="shiftSelect">Jadwal Shift</label>
            <select id="shiftSelect" class="form-control" required>
                <?php foreach ($shiftOptions as $shift): ?>
                <option value="<?= (int)$shift['id_shift'] ?>"><?= web_h($shift['nama_shift']) ?> (<?= web_h($shift['jam_mulai']) ?>–<?= web_h($shift['jam_selesai']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit" <?= !$shiftOptions ? 'disabled' : '' ?>>Buka Shift</button>
        <p id="shiftMessage" style="font-size:13px;margin-top:8px;" aria-live="polite"></p>
    </form>
</div>
<script>
document.getElementById('openShiftForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const message = document.getElementById('shiftMessage');
    try {
        const response = await fetch('../api/index.php?module=shift-operasional&action=open', {
            method:'POST', credentials:'same-origin',
            headers:{'Content-Type':'application/json','X-CSRF-Token':document.getElementById('shiftCsrf').value},
            body:JSON.stringify({id_shift:document.getElementById('shiftSelect').value})
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Gagal membuka shift');
        location.reload();
    } catch (err) { message.textContent = err.message; message.style.color = '#dc2626'; }
});
</script>
<?php endif; ?>

<!-- Info Banner -->
<div class="info-banner">
    <span style="font-size: 18px;">💡</span>
    <span>Tercatat otomatis: tanggal, waktu, kasir, berat, dan metode pembayaran.</span>
</div>

<!-- Daftar Transaksi Terakhir -->
<div class="card">
    <div class="card-title">
        <span>Transaksi Terakhir</span>
        <a href="transaksi.php" style="color: var(--primary-light); font-size: 13px; text-decoration: none; font-weight: 600;">Lihat Semua</a>
    </div>

    <?php 
    $recent_transactions = array_slice(array_reverse($transactions), 0, 3);
    foreach ($recent_transactions as $trx): 
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
                <div class="trx-meta"><?= htmlspecialchars($trx['waktu']) ?> | Kasir <?= htmlspecialchars($trx['kasir']) ?> | <?= htmlspecialchars(strtoupper($trx['jenis_pembayaran'] ?? '-')) ?></div>
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
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[char]));
}
function showTrxDetail(data) {
    document.getElementById('modalTrxId').textContent = data.id + ' - ' + data.pelanggan;
    const body = `
        <div style="background:#f8fafc; padding:12px; border-radius:8px; margin-bottom:12px;">
            <p><strong>Pelanggan:</strong> ${escapeHtml(data.pelanggan)}</p>
            <p><strong>WhatsApp:</strong> ${data.telepon || '-'}</p>
            <p><strong>Layanan:</strong> ${escapeHtml(data.layanan)}</p>
            <p><strong>Timbangan:</strong> ${data.berat} kg</p>
        </div>
        <div style="background:#f8fafc; padding:12px; border-radius:8px; margin-bottom:12px;">
            <p><strong>Metode Pembayaran:</strong> ${escapeHtml((data.jenis_pembayaran || '-').toUpperCase())}</p>
            <p><strong>Total Biaya:</strong> Rp ${(data.total_biaya || 0).toLocaleString('id-ID')}</p>
        </div>
        <p style="font-size:12px; color:#64748b;">Dicatat pada ${escapeHtml(data.tanggal)} ${escapeHtml(data.waktu)} oleh Kasir ${escapeHtml(data.kasir)} (${escapeHtml(data.shift)})</p>
    `;
    document.getElementById('modalBody').innerHTML = body;
    openModal('trxModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
