<?php
require_once __DIR__ . '/../includes/data.php';

// Handle Add / Edit / Deactivate User POST BEFORE header output
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_user') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = trim($_POST['role'] ?? 'Kasir');

        if (!empty($nama) && !empty($username)) {
            $_SESSION['users'][] = [
                'id' => count($_SESSION['users']) + 1,
                'nama' => $nama,
                'role' => $role,
                'email' => $email ?: strtolower($username) . '@kino.com',
                'username' => $username,
                'password' => '123456'
            ];
            header('Location: users.php?toast=saved');
            exit;
        }
    } elseif ($action === 'edit_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        foreach ($_SESSION['users'] as &$u) {
            if ($u['id'] === $user_id) {
                $u['nama'] = $nama;
                if ($email) $u['email'] = $email;
                break;
            }
        }
        header('Location: users.php?toast=saved');
        exit;
    } elseif ($action === 'deactivate_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        // Safeguard: Owner cannot be deactivated
        $_SESSION['users'] = array_values(array_filter($_SESSION['users'], function($u) use ($user_id) {
            return ($u['id'] !== $user_id) || ($u['role'] === 'Pemilik');
        }));
        header('Location: users.php?toast=deactivated');
        exit;
    }
}

$page_title = 'Manajemen Pengguna - Kino Coin Laundry';
$app_role = 'pemilik';
$active_tab = 'user';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
    <div>
        <h2 style="font-size: 16px; font-weight: 800;">Manajemen Pengguna</h2>
        <span style="font-size: 11px; color: var(--text-muted);"><?= count($_SESSION['users']) ?> staf &amp; akun terdaftar</span>
    </div>
    <button type="button" class="btn btn-primary" style="width: auto; padding: 6px 14px; font-size: 12px;" onclick="openModal('modalAddUser')">
        + Tambah
    </button>
</div>

<!-- List of Staff Users (P5) -->
<?php foreach ($_SESSION['users'] as $u): 
    $is_owner = ($u['role'] === 'Pemilik');
?>
<div class="card" style="margin-bottom: 10px; padding: 14px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="header-avatar <?= $is_owner ? 'blue-avatar' : '' ?>" style="width: 34px; height: 34px; font-size: 14px;">
                <?= substr($u['nama'], 0, 1) ?>
            </div>
            <div>
                <strong style="font-size: 14px; color: var(--text-main);"><?= htmlspecialchars($u['nama']) ?></strong>
                <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
            </div>
        </div>
        <span class="badge <?= $is_owner ? 'badge-token' : 'badge-tunai' ?>">
            <?= $is_owner ? 'Pemilik' : 'Kasir' ?>
        </span>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; border-top: 1px dashed var(--border); padding-top: 8px;">
        <button type="button" class="btn btn-outline" style="width: auto; padding: 4px 12px; font-size: 11px;" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)">
            Edit
        </button>
        <?php if (!$is_owner): ?>
            <form action="users.php" method="POST" style="margin: 0;" onsubmit="return confirm('Nonaktifkan staf <?= htmlspecialchars($u['nama']) ?>?')">
                <input type="hidden" name="action" value="deactivate_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-outline" style="width: auto; padding: 4px 10px; font-size: 11px; color: #dc2626;" title="Nonaktifkan">
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
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" placeholder="Contoh: Rian Pratama" required>
            </div>
            <div class="form-group">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" placeholder="Contoh: rian" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="Contoh: rian@kino.com">
            </div>
            <div class="form-group">
                <label class="form-label">Role Akses *</label>
                <select name="role" class="form-control" required>
                    <option value="Kasir">Kasir</option>
                    <option value="Pemilik">Pemilik</option>
                </select>
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
            <input type="hidden" id="editUserId" name="user_id" value="">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" id="editUserNama" name="nama" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" id="editUserEmail" name="email" class="form-control">
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
    document.getElementById('editUserEmail').value = u.email || '';
    openModal('modalEditUser');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
