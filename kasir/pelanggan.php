<?php
$page_title = 'Pelanggan Member - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'pelanggan';

require_once __DIR__ . '/../includes/header.php';

$search = trim($_GET['q'] ?? '');
$members = $_SESSION['members'];

if (!empty($search)) {
    $members = array_filter($members, function($m) use ($search) {
        return (stripos($m['nama'], $search) !== false) || 
               (stripos($m['wa'], $search) !== false) || 
               (stripos($m['kode_kartu'], $search) !== false);
    });
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <h2 style="font-size: 16px; font-weight: 800;">Pelanggan Member</h2>
        <span style="font-size: 11px; color: var(--text-muted);"><?= count($members) ?> member terdaftar</span>
    </div>
    <button type="button" class="btn btn-primary" style="width: auto; padding: 7px 14px; font-size: 12px;" onclick="openModal('modalAddMember')">
        + Tambah
    </button>
</div>

<!-- Search Bar -->
<form action="pelanggan.php" method="GET" style="margin-bottom: 14px;">
    <input type="text" name="q" class="form-control" placeholder="Cari nama / WA / kode..." value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()">
</form>

<!-- List Members -->
<?php if (empty($members)): ?>
    <div class="card" style="text-align: center; padding: 30px; color: var(--text-muted); font-size: 13px;">
        Tidak ada data member yang cocok dengan pencarian "<?= htmlspecialchars($search) ?>".
    </div>
<?php else: ?>
    <?php foreach ($members as $m): ?>
    <div class="card" style="margin-bottom: 10px; padding: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
            <div>
                <strong style="font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($m['nama']) ?></strong>
                <span style="font-size: 11px; color: var(--primary); font-weight: 800; margin-left: 6px;"><?= $m['kode_kartu'] ?></span>
            </div>
            <span class="badge <?= $m['is_member'] ? 'badge-token' : 'badge-tunai' ?>">
                <?= $m['is_member'] ? 'Member Aktif' : 'Reguler' ?>
            </span>
        </div>
        
        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">
            WhatsApp: <?= htmlspecialchars($m['wa']) ?> • <?= $m['tx_count'] ?> kali transaksi
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="btn btn-outline" style="width: auto; padding: 5px 12px; font-size: 11px;" onclick="viewMemberCard(<?= htmlspecialchars(json_encode($m)) ?>)">
                <?= get_icon('qr', 14) ?> Kartu QR
            </button>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal M7: Kartu Member Digital -->
<div class="sheet-overlay" id="modalMemberCard">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Kartu Pelanggan</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalMemberCard')">&times;</button>
        </div>

        <div class="member-card-blue">
            <div class="store-name">KINO COIN LAUNDRY</div>
            <div class="cust-name" id="cardMemberName">Budi Santoso</div>
            
            <div class="qr-box-white" id="modalMemberQrContainer" style="display:inline-flex; align-items:center; justify-content:center; min-width:130px; min-height:130px;"></div>

            <div class="cust-id" id="cardMemberId">KC-P-0001</div>
            <div class="tip">Tunjukkan kepada kasir saat bertransaksi</div>
        </div>

        <button type="button" class="btn btn-outline" onclick="closeModal('modalMemberCard')">
            Tutup
        </button>
    </div>
</div>

<!-- Modal Tambah Member -->
<div class="sheet-overlay" id="modalAddMember">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Pelanggan Baru</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalAddMember')">&times;</button>
        </div>

        <form method="POST" action="pelanggan.php">
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" placeholder="Contoh: Dewi Lestari" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp *</label>
                <input type="tel" name="wa" class="form-control" placeholder="Contoh: 08155556666" required>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" name="is_member" value="1" checked>
                    <span>Jadikan Member &amp; buat kartu barcode</span>
                </label>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 18px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAddMember')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewMemberCard(m) {
    document.getElementById('cardMemberName').textContent = m.nama;
    document.getElementById('cardMemberId').textContent = m.kode_kartu;
    openModal('modalMemberCard');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
