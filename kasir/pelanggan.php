<?php
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

check_auth_guard('kasir');

$member_error = '';

// Handle Add Member POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $wa = trim($_POST['wa'] ?? '');

    if (empty($nama)) {
        $member_error = 'Nama pelanggan wajib diisi.';
    } elseif (empty($wa)) {
        $member_error = 'Nomor WhatsApp wajib diisi.';
    } elseif (!preg_match('/^[0-9]+$/', $wa)) {
        $member_error = 'Nomor WhatsApp hanya boleh berisi angka.';
    } elseif (strlen($wa) < 10 || strlen($wa) > 15) {
        $member_error = 'Nomor WhatsApp tidak valid (minimal 10 digit, maksimal 15 digit).';
    } elseif (
        empty($_SESSION['csrf'])
        || empty($_POST['csrf_token'])
        || !hash_equals((string)$_SESSION['csrf'], (string)$_POST['csrf_token'])
    ) {
        $member_error = 'Sesi tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        $existing = one('SELECT id_pelanggan FROM pelanggan WHERE no_hp = ?', [$wa]);
        if ($existing) {
            $member_error = 'Nomor WhatsApp ' . htmlspecialchars($wa) . ' sudah terdaftar atas nama pelanggan lain.';
        } else {
            try {
                $customer = one(
                    'INSERT INTO pelanggan (nama_pelanggan, no_hp)
                     VALUES (?, ?)
                     RETURNING id_pelanggan',
                    [$nama, $wa]
                );
                $customerId = (int)$customer['id_pelanggan'];
                $code = 'KC-P-' . str_pad((string)$customerId, 4, '0', STR_PAD_LEFT);
                execute_sql(
                    'UPDATE pelanggan SET kode_kartu = ? WHERE id_pelanggan = ?',
                    [$code, $customerId]
                );
                header('Location: pelanggan.php?toast=saved');
                exit;
            } catch (Throwable $e) {
                error_log($e->getMessage());
                $member_error = 'Pelanggan tidak dapat disimpan ke database.';
            }
        }
    }
}

$page_title = 'Pelanggan Member - Kino Coin Laundry';
$app_role = 'kasir';
$active_tab = 'pelanggan';

require_once __DIR__ . '/../includes/header.php';

$search = trim($_GET['q'] ?? '');
$members = rows(
    "SELECT p.id_pelanggan AS id,
            p.nama_pelanggan AS nama,
            p.no_hp AS wa,
            COALESCE(p.kode_kartu, CONCAT('KC-P-', LPAD(p.id_pelanggan::text, 4, '0'))) AS kode_kartu,
            (p.password_hash IS NOT NULL) AS is_member,
            COUNT(t.id_transaksi)::int AS tx_count
     FROM pelanggan p
     LEFT JOIN transaksi t USING (id_pelanggan)
     WHERE p.nama_pelanggan ILIKE ?
        OR p.no_hp ILIKE ?
        OR COALESCE(p.kode_kartu, CONCAT('KC-P-', LPAD(p.id_pelanggan::text, 4, '0'))) ILIKE ?
     GROUP BY p.id_pelanggan
     ORDER BY p.id_pelanggan DESC
     LIMIT 100",
    ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%']
);
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

<?php if ($member_error !== ''): ?>
    <div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:10px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:14px;">
        <?= htmlspecialchars($member_error) ?>
    </div>
<?php endif; ?>

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
            <span class="badge <?= in_array($m['is_member'], [true, 't', '1', 1], true) ? 'badge-token' : 'badge-tunai' ?>">
                <?= in_array($m['is_member'], [true, 't', '1', 1], true) ? 'Akun Aktif' : 'Pelanggan' ?>
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" placeholder="Contoh: Dewi Lestari" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp *</label>
                <input type="text" id="memberWaInput" name="wa" class="form-control" placeholder="Contoh: 08155556666" inputmode="numeric" pattern="[0-9]+" title="Nomor WhatsApp hanya boleh berisi angka" aria-describedby="memberWaWarning" oninput="validateMemberWaInput(this)" required>
                <small id="memberWaWarning" role="alert" aria-live="polite" style="display:none; color:#dc2626; font-size:12px; margin-top:5px;">
                    Nomor WhatsApp hanya boleh berisi angka.
                </small>
            </div>
            <p style="font-size: 11px; color: var(--text-muted);">Kode kartu QR akan dibuat otomatis setelah pelanggan tersimpan.</p>
            <div style="display: flex; gap: 10px; margin-top: 18px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAddMember')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function validateMemberWaInput(input) {
    const hasNonDigits = /[^0-9]/.test(input.value);
    input.value = input.value.replace(/[^0-9]/g, '');
    document.getElementById('memberWaWarning').style.display = hasNonDigits ? 'block' : 'none';
}

function viewMemberCard(m) {
    document.getElementById('cardMemberName').textContent = m.nama;
    document.getElementById('cardMemberId').textContent = m.kode_kartu;
    openModal('modalMemberCard');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
