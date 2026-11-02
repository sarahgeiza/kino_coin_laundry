<?php
$page_title = 'Tutup Shift - Kino Coin Laundry';
$header_role = 'Kasir';
$header_user = '';
$header_avatar = 'D';
$header_title = 'Tutup Shift';
$header_subtitle = 'Rekonsiliasi kas pergantian shift';
$current_role = 'kasir';
$active_tab = 'shift';

require_once __DIR__ . '/../includes/header.php';

$total_revenue = array_sum(array_column($transactions, 'total_biaya'));
$total_cash = array_sum(array_map(static fn(array $trx): int => ($trx['jenis_pembayaran'] ?? '') === 'cash' ? (int)$trx['total_biaya'] : 0, $transactions));
$total_qris = array_sum(array_map(static fn(array $trx): int => ($trx['jenis_pembayaran'] ?? '') === 'qris' ? (int)$trx['total_biaya'] : 0, $transactions));
$total_trx = count($transactions);
$total_kg = array_sum(array_column($transactions, 'berat'));
$display_income = $total_cash;
$display_trx = $total_trx;
$display_kg = $total_kg;
$closed_success = false;
?>

<div class="card">
    <div style="text-align: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
        <span style="font-size: 11px; font-weight: 800; color: var(--primary-light); letter-spacing: 0.5px;">
            REKAPITULASI SHIFT <?= web_h($activeOperationalShift['nama_shift'] ?? '—') ?>
        </span>
        <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
            <?= $activeOperationalShift ? web_h($activeOperationalShift['jam_mulai'] . ' — ' . $activeOperationalShift['jam_selesai']) . ' • ' . web_h(date('d M Y', strtotime($activeOperationalShift['mulai_pada']))) : 'Tidak ada shift aktif' ?>
        </div>
        <div style="font-size: 13px; font-weight: 700; color: var(--text-main); margin-top: 2px;">
            Kasir: <?= web_h($current_user['nama']) ?>
        </div>
    </div>

    <!-- Metrik Volume -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
        <div style="background: #f8fafc; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 11px; color: var(--text-muted);">Total Transaksi</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 2px;"><?= $display_trx ?></div>
        </div>
        <div style="background: #f8fafc; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 11px; color: var(--text-muted);">Total Kg Tercuci</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 2px;"><?= number_format($display_kg, 1, ',', '.') ?> kg</div>
        </div>
    </div>

    <div style="background:#f8fafc;padding:14px;border-radius:var(--radius-md);border:1px solid var(--border-color);margin-bottom:16px;">
        <div style="font-size:12px;font-weight:800;color:var(--text-muted);margin-bottom:8px;">RINCIAN PEMBAYARAN</div>
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
            <span>Cash:</span><strong><?= format_rupiah($total_cash) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;">
            <span>QRIS:</span><strong><?= format_rupiah($total_qris) ?></strong>
        </div>
    </div>

    <form action="tutup-shift.php" method="POST" id="closeShiftForm">
        <input type="hidden" id="closeShiftCsrf" value="<?= web_h(web_csrf_token()) ?>">
        <!-- Rekonsiliasi Kas -->
        <div class="bill-summary" style="margin-top: 0; margin-bottom: 16px;">
            <div class="bill-row">
                <span>Total Sistem:</span>
                <strong id="total-sistem-val" data-value="<?= $display_income ?>"><?= format_rupiah($display_income) ?></strong>
            </div>
            
            <div class="form-group" style="margin: 12px 0 8px 0;">
                <label class="form-label" style="font-size: 13px;">Total Fisik Uang Tunai (Input)</label>
                <input type="number" id="input-fisik-kas" name="fisik_kas" class="form-control" value="<?= $display_income ?>" min="0" required <?= !$activeOperationalShift ? "disabled" : "" ?>>
            </div>

            <div class="bill-row" style="border-top: 1px dashed var(--border-color); padding-top: 8px;">
                <span>Selisih Rekonsiliasi:</span>
                <strong id="selisih-val" style="color: var(--success);">Rp 0 (Sesuai)</strong>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Catatan Shift (opsional)</label>
            <textarea name="catatan" id="closeShiftNote" class="form-control" rows="2" placeholder="Contoh: Tidak ada kendala" <?= !$activeOperationalShift ? "disabled" : "" ?>></textarea>
        </div>

        <button type="submit" class="btn btn-primary" <?= !$activeOperationalShift ? 'disabled' : '' ?>>
            Konfirmasi Tutup Shift
        </button>
        <p id="closeShiftMessage" role="alert" style="font-size:13px;margin-top:10px;"></p>
    </form>
</div>

<script>
document.getElementById('closeShiftForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!confirm('Konfirmasi menutup shift ini?')) return;
    const message = document.getElementById('closeShiftMessage');
    const total = Number(document.getElementById('input-fisik-kas').value);
    if (!Number.isSafeInteger(total) || total < 0) { message.textContent = 'Masukkan jumlah fisik yang valid.'; return; }
    try {
        const response = await fetch('../api/index.php?module=shift-operasional&action=close', {
            method:'POST', credentials:'same-origin',
            headers:{'Content-Type':'application/json','X-CSRF-Token':document.getElementById('closeShiftCsrf').value},
            body:JSON.stringify({total_fisik:total,catatan:document.getElementById('closeShiftNote').value})
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Gagal menutup shift');
        message.style.color = '#15803d';
        message.textContent = 'Shift ditutup. Selisih: ' + formatRupiah(result.data.selisih);
        setTimeout(() => location.href = 'index.php', 1200);
    } catch (err) { message.style.color = '#dc2626'; message.textContent = err.message; }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
