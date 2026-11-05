<?php
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
check_auth_guard('pemilik');

$master_error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    if (
        empty($_SESSION['csrf'])
        || empty($_POST['csrf_token'])
        || !hash_equals((string)$_SESSION['csrf'], (string)$_POST['csrf_token'])
    ) {
        $master_error = 'Sesi tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        $action = $_POST['action'];
        $name = trim($_POST['nama_shift'] ?? '');
        $start = trim($_POST['mulai'] ?? '');
        $end = trim($_POST['selesai'] ?? '');
        $scheduleDate = trim($_POST['tanggal'] ?? date('Y-m-d'));
        $staffIds = array_values(array_unique(array_filter(
            array_map('intval', $_POST['selected_staff'] ?? []),
            static fn(int $id): bool => $id > 0
        )));

        if (
            !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start)
            || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end)
        ) {
            $master_error = 'Format jam shift tidak valid.';
        } elseif (
            !DateTimeImmutable::createFromFormat('!Y-m-d', $scheduleDate)
            || DateTimeImmutable::createFromFormat('!Y-m-d', $scheduleDate)->format('Y-m-d') !== $scheduleDate
        ) {
            $master_error = 'Tanggal jadwal tidak valid.';
        } elseif ($name === '' || !$staffIds) {
            $master_error = 'Nama shift dan minimal satu kasir wajib dipilih.';
        } else {
            $connection = getConnection();
            if (!pg_query($connection, 'BEGIN')) {
                throw new RuntimeException('Gagal memulai perubahan shift.');
            }
            try {
                if ($action === 'add_shift') {
                    $created = one(
                        'INSERT INTO shift (nama_shift, jam_mulai, jam_selesai)
                         VALUES (?, ?, ?)
                         RETURNING id_shift',
                        [$name, $start, $end]
                    );
                    $shiftId = (int)$created['id_shift'];
                } elseif ($action === 'edit_shift') {
                    $shiftId = (int)($_POST['shift_id'] ?? 0);
                    execute_sql(
                        'UPDATE shift SET nama_shift = ?, jam_mulai = ?, jam_selesai = ?
                         WHERE id_shift = ?',
                        [$name, $start, $end, $shiftId]
                    );
                    execute_sql(
                        "DELETE FROM jadwal_shift
                         WHERE id_shift = ? AND tanggal = ? AND status = 'dijadwalkan'",
                        [$shiftId, $scheduleDate]
                    );
                } else {
                    throw new RuntimeException('Aksi master shift tidak dikenal.');
                }

                foreach ($staffIds as $staffId) {
                    $schedule = one(
                        "INSERT INTO jadwal_shift
                            (id_shift, id_kasir, tanggal, status, dibuat_oleh)
                         VALUES (?, ?, ?, 'dijadwalkan', ?)
                         ON CONFLICT (id_kasir, tanggal) DO NOTHING
                         RETURNING id_jadwal",
                        [$shiftId, $staffId, $scheduleDate, (int)$_SESSION['user_id']]
                    );
                    if (!$schedule) {
                        throw new RuntimeException('Salah satu kasir sudah memiliki jadwal pada tanggal tersebut.');
                    }
                }

                if (!pg_query($connection, 'COMMIT')) {
                    throw new RuntimeException('Gagal menyimpan jadwal shift.');
                }
                header('Location: master.php?tab=shift&tanggal=' . urlencode($scheduleDate) . '&toast=saved');
                exit;
            } catch (Throwable $e) {
                pg_query($connection, 'ROLLBACK');
                if (str_contains($e->getMessage(), 'sudah memiliki jadwal')) {
                    $master_error = $e->getMessage();
                } else {
                    error_log($e->getMessage());
                    $master_error = 'Perubahan shift tidak dapat disimpan ke database.';
                }
            }
        }
    }
}

$page_title = 'Master Data - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'master';

require_once __DIR__ . '/../includes/header.php';

$subtab = $_GET['tab'] ?? 'layanan';
$scheduleDate = trim($_GET['tanggal'] ?? date('Y-m-d'));
$dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $scheduleDate);
if (!$dateObject || $dateObject->format('Y-m-d') !== $scheduleDate) {
    $scheduleDate = date('Y-m-d');
}
$kasir_users = rows("SELECT id_pengguna AS id, nama FROM pengguna WHERE role = 'kasir' ORDER BY nama");
$services = rows(
    "SELECT id_layanan, nama_layanan AS nama, harga AS tarif, berat_standar,
            jumlah_koin, durasi, aktif,
            CASE
                WHEN jumlah_koin > 0 THEN 'SELF_SERVICE'
                WHEN LOWER(nama_layanan) LIKE '%drop off%' OR LOWER(nama_layanan) = 'lipat' THEN 'DROP_OFF'
                ELSE 'ADDITIONAL'
            END AS kategori
     FROM layanan
     ORDER BY id_layanan"
);
$shifts = rows(
    "SELECT s.id_shift AS id, s.nama_shift AS nama,
            TO_CHAR(s.jam_mulai, 'HH24:MI') AS mulai,
            TO_CHAR(s.jam_selesai, 'HH24:MI') AS selesai,
            TO_CHAR(s.jam_mulai, 'HH24:MI') || ' — ' || TO_CHAR(s.jam_selesai, 'HH24:MI') AS jam_tampil,
            COALESCE(STRING_AGG(p.nama, ', ' ORDER BY p.nama), 'Belum ada kasir') AS staff,
            COALESCE(STRING_AGG(j.id_kasir::text, ',' ORDER BY j.id_kasir), '') AS assigned_ids
     FROM shift s
     LEFT JOIN jadwal_shift j
       ON j.id_shift = s.id_shift AND j.tanggal = ? AND j.status <> 'dibatalkan'
     LEFT JOIN pengguna p ON p.id_pengguna = j.id_kasir
     GROUP BY s.id_shift
     ORDER BY s.jam_mulai",
    [$scheduleDate]
);

?>

<div style="margin-bottom: 12px;">
    <h2 style="font-size: 16px; font-weight: 800;">Master Data</h2>
    <span style="font-size: 11px; color: var(--text-muted);">Pengaturan katalog layanan &amp; shift</span>
</div>

<?php if ($master_error !== ''): ?>
    <div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:10px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:14px;">
        <?= htmlspecialchars($master_error) ?>
    </div>
<?php endif; ?>

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
        <?php foreach ($services as $svc):
            if ($svc['kategori'] !== 'SELF_SERVICE') continue;
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
            <div>
                <strong style="font-size: 14px;"><?= htmlspecialchars($svc['nama']) ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);"><?= (int)$svc['jumlah_koin'] ?> koin mesin<?= $svc['berat_standar'] !== null ? ' • ' . htmlspecialchars((string)$svc['berat_standar']) . ' kg' : '' ?> • <?= htmlspecialchars($svc['durasi']) ?></div>
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
        <?php foreach ($services as $svc):
            if ($svc['kategori'] !== 'DROP_OFF') continue;
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
            <div>
                <strong style="font-size: 14px;"><?= htmlspecialchars($svc['nama']) ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);"><?= $svc['berat_standar'] !== null ? htmlspecialchars((string)$svc['berat_standar']) . ' kg • ' : '' ?><?= htmlspecialchars($svc['durasi']) ?></div>
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
        <?php foreach ($services as $svc):
            if ($svc['kategori'] !== 'ADDITIONAL') continue;
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
            <div>
                <strong style="font-size: 14px;"><?= htmlspecialchars($svc['nama']) ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($svc['durasi']) ?></div>
            </div>
            <strong style="font-size: 14px; color: var(--text-main);"><?= format_rupiah($svc['tarif']) ?></strong>
        </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <!-- TAB SHIFT (P4) -->
    <form method="GET" action="master.php" style="margin-bottom: 12px;">
        <input type="hidden" name="tab" value="shift">
        <label class="form-label" for="shiftScheduleDate">Tanggal jadwal</label>
        <input type="date" id="shiftScheduleDate" name="tanggal" class="form-control" value="<?= htmlspecialchars($scheduleDate) ?>" onchange="this.form.submit()">
    </form>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <span style="font-size: 13px; font-weight: 700;">Daftar Shift Kerja</span>
        <button type="button" class="btn btn-primary" style="width: auto; padding: 6px 14px; font-size: 12px;" onclick="openModal('modalAddShift')">
            + Tambah
        </button>
    </div>

    <?php foreach ($shifts as $sh): ?>
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" name="tanggal" value="<?= htmlspecialchars($scheduleDate) ?>">
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
                            <input type="checkbox" name="selected_staff[]" value="<?= (int)$ku['id'] ?>">
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" id="editShiftDate" name="tanggal" value="<?= htmlspecialchars($scheduleDate) ?>">
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
                            <input type="checkbox" class="edit-staff-checkbox" name="selected_staff[]" value="<?= (int)$ku['id'] ?>">
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
    const assigned = (sh.assigned_ids || '').split(',').filter(Boolean);
    document.querySelectorAll('.edit-staff-checkbox').forEach(cb => {
        cb.checked = assigned.includes(cb.value);
    });

    openModal('modalEditShift');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>