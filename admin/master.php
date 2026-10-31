<?php
require_once __DIR__ . '/../includes/data.php';

// Handle Add/Edit Shift POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_shift') {
        $nama = trim($_POST['nama_shift'] ?? '');
        $mulai = trim($_POST['mulai'] ?? '08:00');
        $selesai = trim($_POST['selesai'] ?? '14:00');
        
        if (isset($_POST['selected_staff']) && is_array($_POST['selected_staff'])) {
            $staff = implode(', ', array_map('trim', $_POST['selected_staff']));
        } else {
            $staff = trim($_POST['staff'] ?? '');
        }
        $_SESSION['shifts'][] = [
            'id' => count($_SESSION['shifts']) + 1,
            'nama' => $nama,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'jam_tampil' => "$mulai — $selesai",
            'staff' => $staff
        ];
        header('Location: master.php?tab=shift&toast=saved');
        exit;
    } elseif ($_POST['action'] === 'edit_shift') {
        $shift_id = intval($_POST['shift_id'] ?? 0);
        $nama = trim($_POST['nama_shift'] ?? '');
        $mulai = trim($_POST['mulai'] ?? '08:00');
        $selesai = trim($_POST['selesai'] ?? '14:00');
        
        if (isset($_POST['selected_staff']) && is_array($_POST['selected_staff'])) {
            $staff = implode(', ', array_map('trim', $_POST['selected_staff']));
        } else {
            $staff = trim($_POST['staff'] ?? '');
        }
        
        foreach ($_SESSION['shifts'] as &$sh) {
            if ($sh['id'] === $shift_id || $sh['nama'] === $nama) {
                $sh['nama'] = $nama;
                $sh['mulai'] = $mulai;
                $sh['selesai'] = $selesai;
                $sh['jam_tampil'] = "$mulai — $selesai";
                $sh['staff'] = $staff;
                break;
            }
        }
        header('Location: master.php?tab=shift&toast=saved');
        exit;
    }
}

$page_title = 'Master Data - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'master';

require_once __DIR__ . '/../includes/header.php';

// Ambil daftar kasir dari data manajemen pengguna untuk multiple choose
$kasir_users = array_filter($_SESSION['users'] ?? [], function($u) {
    return ($u['role'] ?? '') === 'Kasir';
});

$subtab = $_GET['tab'] ?? 'layanan';


?>

<div style="margin-bottom: 12px;">
    <h2 style="font-size: 16px; font-weight: 800;">Master Data</h2>
    <span style="font-size: 11px; color: var(--text-muted);">Pengaturan katalog layanan &amp; shift</span>
</div>

<!-- Toggle Tabs: Layanan vs Shift (Figma P3, P4) -->
<div style="display: flex; background: #e2e8f0; border-radius: var(--radius-md); padding: 4px; margin-bottom: 16px;">
    <a href="master.php?tab=layanan" style="flex: 1; text-align: center; padding: 8px; font-size: 13px; font-weight: 800; text-decoration: none; border-radius: 8px; <?= $subtab === 'layanan' ? 'background: white; color: #1e3a8a; box-shadow: 0 2px 6px rgba(0,0,0,0.06);' : 'color: #64748b;' ?>">
        Layanan
    </a>
    <a href="master.php?tab=shift" style="flex: 1; text-align: center; padding: 8px; font-size: 13px; font-weight: 800; text-decoration: none; border-radius: 8px; <?= $subtab === 'shift' ? 'background: white; color: #1e3a8a; box-shadow: 0 2px 6px rgba(0,0,0,0.06);' : 'color: #64748b;' ?>">
        Shift
    </a>
</div>

<?php if ($subtab === 'layanan'): ?>
    <!-- 1. Group Self Service (Token) -->
    <div class="card">
        <div class="card-title">
            <span>SELF SERVICE (TOKEN)</span>
            <span style="font-size: 11px; color: var(--primary);">Koin Mesin</span>
        </div>
        <?php foreach ($_SESSION['services'] as $svc): 
            if ($svc['kategori'] !== 'SELF_SERVICE') continue;
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
            <div>
                <strong style="font-size: 14px;"><?= htmlspecialchars($svc['nama']) ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($svc['deskripsi']) ?></div>
            </div>
            <strong style="font-size: 14px; color: var(--primary-dark);"><?= format_rupiah($svc['tarif']) ?></strong>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 2. Group Drop Off (Tunai) -->
    <div class="card">
        <div class="card-title">
            <span>DROP OFF (TUNAI)</span>
            <span style="font-size: 11px; color: var(--success);">Non-Token</span>
        </div>
        <?php foreach ($_SESSION['services'] as $svc): 
            if ($svc['kategori'] !== 'DROP_OFF') continue;
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
            <div>
                <strong style="font-size: 14px;"><?= htmlspecialchars($svc['nama']) ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($svc['deskripsi']) ?></div>
            </div>
            <strong style="font-size: 14px; color: var(--success);"><?= format_rupiah($svc['tarif']) ?></strong>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 3. Group Additional (Tunai) -->
    <div class="card">
        <div class="card-title">
            <span>ADDITIONAL (TUNAI)</span>
            <span style="font-size: 11px; color: var(--warning);">Retail</span>
        </div>
        <?php foreach ($_SESSION['services'] as $svc): 
            if ($svc['kategori'] !== 'ADDITIONAL') continue;
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
            <div>
                <strong style="font-size: 14px;"><?= htmlspecialchars($svc['nama']) ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($svc['deskripsi']) ?></div>
            </div>
            <strong style="font-size: 14px; color: var(--text-main);"><?= format_rupiah($svc['tarif']) ?></strong>
        </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <!-- TAB SHIFT (P4) -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <span style="font-size: 13px; font-weight: 700;">Daftar Shift Kerja</span>
        <button type="button" class="btn btn-primary" style="width: auto; padding: 6px 14px; font-size: 12px;" onclick="openModal('modalAddShift')">
            + Tambah
        </button>
    </div>

    <?php foreach ($_SESSION['shifts'] as $sh): ?>
    <div class="card" style="margin-bottom: 10px; padding: 14px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
            <strong style="font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($sh['nama']) ?></strong>
            <button type="button" class="btn btn-outline" style="width: auto; padding: 3px 10px; font-size: 11px;" onclick="editShift(<?= htmlspecialchars(json_encode($sh)) ?>)">
                Edit
            </button>
        </div>
        <div style="font-size: 12px; color: var(--primary); font-weight: 700;">
            <?= htmlspecialchars($sh['jam_tampil']) ?> WIB
        </div>
        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
            Kasir: <?= htmlspecialchars($sh['staff']) ?>
        </div>
    </div>
    <?php endforeach; ?>

<?php endif; ?>

<!-- Modal M8: Tambah Shift -->
<div class="sheet-overlay" id="modalAddShift">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Tambah Shift</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalAddShift')">&times;</button>
        </div>
        <form action="master.php?tab=shift" method="POST">
            <input type="hidden" name="action" value="add_shift">
            <div class="form-group">
                <label class="form-label">Nama Shift</label>
                <input type="text" name="nama_shift" class="form-control" placeholder="Contoh: Shift Sore" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="mulai" class="form-control" value="08:00" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="selesai" class="form-control" value="14:00" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Pilih Kasir Bertugas *</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-height: 140px; overflow-y: auto; background: #f8fafc; padding: 10px; border-radius: 12px; border: 1.5px solid var(--border);">
                    <?php foreach ($kasir_users as $ku): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--text-main); cursor: pointer; padding: 6px 8px; border-radius: 8px; background: white; border: 1px solid var(--border);">
                            <input type="checkbox" name="selected_staff[]" value="<?= htmlspecialchars($ku['nama']) ?>">
                            <span><?= htmlspecialchars($ku['nama']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">
                    Pilih staf kasir yang ditugaskan (terhubung ke Manajemen Pengguna)
                </span>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 14px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAddShift')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal M10: Edit Shift -->
<div class="sheet-overlay" id="modalEditShift">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Edit Shift</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalEditShift')">&times;</button>
        </div>
        <form action="master.php?tab=shift" method="POST">
            <input type="hidden" name="action" value="edit_shift">
            <input type="hidden" id="editShiftId" name="shift_id" value="">
            <div class="form-group">
                <label class="form-label">Nama Shift</label>
                <input type="text" id="editShiftName" name="nama_shift" class="form-control" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" id="editShiftStart" name="mulai" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" id="editShiftEnd" name="selesai" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Pilih Kasir Bertugas *</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-height: 140px; overflow-y: auto; background: #f8fafc; padding: 10px; border-radius: 12px; border: 1.5px solid var(--border);">
                    <?php foreach ($kasir_users as $ku): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--text-main); cursor: pointer; padding: 6px 8px; border-radius: 8px; background: white; border: 1px solid var(--border);">
                            <input type="checkbox" class="edit-staff-checkbox" name="selected_staff[]" value="<?= htmlspecialchars($ku['nama']) ?>">
                            <span><?= htmlspecialchars($ku['nama']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">
                    Pilih staf kasir yang ditugaskan (terhubung ke Manajemen Pengguna)
                </span>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 14px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalEditShift')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editShift(sh) {
    document.getElementById('editShiftId').value = sh.id;
    document.getElementById('editShiftName').value = sh.nama;
    document.getElementById('editShiftStart').value = sh.mulai;
    document.getElementById('editShiftEnd').value = sh.selesai;
    
    // Auto-check kasir bertugas
    const assigned = (sh.staff || '').split(',').map(s => s.trim().toLowerCase());
    document.querySelectorAll('.edit-staff-checkbox').forEach(cb => {
        const val = cb.value.trim().toLowerCase();
        // check if name matches or contains
        cb.checked = assigned.some(a => a.includes(val) || val.includes(a));
    });

    openModal('modalEditShift');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>