<?php
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
check_auth_guard('pemilik');

$user_error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if (
        empty($_SESSION['csrf'])
        || empty($_POST['csrf_token'])
        || !hash_equals((string)$_SESSION['csrf'], (string)$_POST['csrf_token'])
    ) {
        $user_error = 'Sesi tidak valid. Muat ulang halaman dan coba kembali.';
    } else {
        try {
            if ($action === 'add_user') {
                $name = trim($_POST['nama'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $role = strtolower(trim($_POST['role'] ?? 'kasir'));
                $password = (string)($_POST['password'] ?? '');

                if ($name === '' || $username === '' || !in_array($role, ['pemilik', 'kasir'], true)) {
                    $user_error = 'Nama, username, dan role wajib diisi dengan benar.';
                } elseif (strlen($password) < 8) {
                    $user_error = 'Password minimal 8 karakter.';
                } elseif (one('SELECT id_pengguna FROM pengguna WHERE LOWER(username) = LOWER(?)', [$username])) {
                    $user_error = 'Username sudah digunakan.';
                } else {
                    one(
                        'INSERT INTO pengguna (nama, username, role, password)
                         VALUES (?, ?, ?, ?)',
                        [$name, $username, $role, password_hash($password, PASSWORD_DEFAULT)]
                    );
                    header('Location: users.php?toast=saved');
                    exit;
                }
            } elseif ($action === 'edit_user') {
                execute_sql(
                    'UPDATE pengguna SET nama = ? WHERE id_pengguna = ? AND role <> ?',
                    [trim($_POST['nama'] ?? ''), (int)($_POST['user_id'] ?? 0), 'pemilik']
                );
                header('Location: users.php?toast=saved');
                exit;
            } elseif ($action === 'deactivate_user') {
                $targetId = (int)($_POST['user_id'] ?? 0);
                if ($targetId === (int)($_SESSION['user_id'] ?? 0)) {
                    $user_error = 'Akun yang sedang digunakan tidak dapat dinonaktifkan.';
                } else {
                    $account = one('SELECT id_pengguna, role FROM pengguna WHERE id_pengguna = ?', [$targetId]);
                    $hasHistory = one(
                        'SELECT 1
                         WHERE EXISTS (SELECT 1 FROM transaksi WHERE id_kasir = ?)
                            OR EXISTS (SELECT 1 FROM shift_operasional WHERE id_kasir = ?)
                            OR EXISTS (SELECT 1 FROM jadwal_shift WHERE id_kasir = ?)',
                        [$targetId, $targetId, $targetId]
                    );
                    if (!$account || $account['role'] === 'pemilik') {
                        $user_error = 'Akun pemilik tidak dapat dinonaktifkan.';
                    } elseif ($hasHistory) {
                        $user_error = 'Akun memiliki riwayat operasional dan tidak dapat dihapus.';
                    } else {
                        execute_sql('DELETE FROM pengguna WHERE id_pengguna = ?', [$targetId]);
                        header('Location: users.php?toast=deactivated');
                        exit;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $user_error = 'Perubahan akun tidak dapat disimpan ke database.';
        }
    }
}

$page_title = 'Manajemen Pengguna - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'user';

require_once __DIR__ . '/../includes/header.php';
$users = rows('SELECT id_pengguna AS id, nama, username, role FROM pengguna ORDER BY id_pengguna');
?>

<?php if (!empty($user_error)): ?>
    <div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:12px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:16px;">
        <?= htmlspecialchars($user_error) ?>
    </div>
<?php endif; ?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <h2 style="font-size: 16px; font-weight: 800;">Manajemen Pengguna</h2>
        <span style="font-size: 11px; color: var(--text-muted);"><?= count($users) ?> staf &amp; akun terdaftar</span>
    </div>
    <button type="button" class="btn btn-primary" style="width: auto; padding: 6px 14px; font-size: 12px;" onclick="openModal('modalAddUser')">
        + Tambah
    </button>
</div>

<!-- List of Staff Users (P5) -->
<?php foreach ($users as $u):
    $is_owner = ($u['role'] === 'pemilik');
?>
<div class="card" style="margin-bottom: 10px; padding: 14px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="header-avatar <?= $is_owner ? 'blue-avatar' : '' ?>" style="width: 34px; height: 34px; font-size: 14px;">
                <?= substr($u['nama'], 0, 1) ?>
            </div>
            <div>
                <strong style="font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($u['nama']) ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);">@<?= htmlspecialchars($u['username']) ?></div>
            </div>
        </div>
        <span class="badge <?= $is_owner ? 'badge-token' : 'badge-tunai' ?>">
            <?= $is_owner ? 'Pemilik' : 'Kasir' ?>
        </span>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; border-top: 1px dashed var(--border); padding-top: 8px;">
        <?php if (!$is_owner): ?>
            <button type="button" class="btn btn-outline" style="width: auto; padding: 4px 12px; font-size: 11px;" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
                Edit
            </button>
        <?php endif; ?>
        <?php if (!$is_owner): ?>
            <form action="users.php" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus staf <?= htmlspecialchars($u['nama']) ?>?')">
                <input type="hidden" name="action" value="deactivate_user">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-outline" style="width: auto; padding: 4px 10px; font-size: 11px; color: #dc2626;" title="Hapus">
                    &times;
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal Tambah Pengguna -->
<div class="sheet-overlay" id="modalAddUser">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Tambah Staf / Pengguna</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalAddUser')">&times;</button>
        </div>
        <form action="users.php" method="POST">
            <input type="hidden" name="action" value="add_user">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" placeholder="Contoh: Rian Pratama" required>
            </div>
            <div class="form-group">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" placeholder="Contoh: rian" required>
            </div>
            <div class="form-group">
                <label class="form-label">Role Akses *</label>
                <select name="role" class="form-control" required>
                    <option value="kasir">Kasir</option>
                    <option value="pemilik">Pemilik</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Password awal *</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 14px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalAddUser')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pengguna -->
<div class="sheet-overlay" id="modalEditUser">
    <div class="sheet-card">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Edit Pengguna</h3>
            <button type="button" class="sheet-close" onclick="closeModal('modalEditUser')">&times;</button>
        </div>
        <form action="users.php" method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" id="editUserId" name="user_id" value="">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" id="editUserNama" name="nama" class="form-control" required>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 14px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalEditUser')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(u) {
    document.getElementById('editUserId').value = u.id;
    document.getElementById('editUserNama').value = u.nama;
    openModal('modalEditUser');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
