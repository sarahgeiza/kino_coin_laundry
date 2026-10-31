<?php
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
    <button type="button" class="btn btn-primary" style="width: auto; padding: 6px 14px; font-size: 12px;" onclick="showToast('Fitur tambah akun staf aktif', 'success')">
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
        <button type="button" class="btn btn-outline" style="width: auto; padding: 4px 12px; font-size: 11px;" onclick="showToast('Edit akun <?= $u['nama'] ?>', 'success')">
            Edit
        </button>
        <?php if (!$is_owner): ?>
            <button type="button" class="btn btn-outline" style="width: auto; padding: 4px 10px; font-size: 11px; color: #dc2626;" onclick="showToast('Nonaktifkan <?= $u['nama'] ?>', 'error')">
                &times;
            </button>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
