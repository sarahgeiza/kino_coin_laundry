<?php
session_start();
require_once __DIR__ . '/../includes/data.php';

$reg_error = '';
$wa_invalid = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $wa = trim($_POST['wa'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[0-9]+$/', $wa)) {
        $wa_invalid = true;
        $reg_error = 'Nomor WhatsApp hanya boleh berisi angka.';
    } elseif ($pass !== $confirm) {
        $reg_error = 'Konfirmasi password tidak cocok. Silakan ulangi.';
    } else {
        $exists = false;
        foreach ($_SESSION['members'] as &$m) {
            if ($m['wa'] === $wa) {
                $m['nama'] = $nama;
                $m['password'] = $pass;
                $_SESSION['username'] = $nama;
                $_SESSION['role'] = 'pelanggan';
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $new_id = count($_SESSION['members']) + 1;
            $_SESSION['members'][] = [
                'id' => $new_id,
                'nama' => $nama,
                'wa' => $wa,
                'kode_kartu' => 'KC-P-' . str_pad($new_id, 4, '0', STR_PAD_LEFT),
                'is_member' => true,
                'tx_count' => 0,
                'coin_cuci' => 0,
                'coin_kering' => 0,
                'password' => $pass
            ];
            $_SESSION['username'] = $nama;
            $_SESSION['role'] = 'pelanggan';
        }
        header('Location: index.php?toast=registered');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pelanggan - Kino Coin Laundry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-page-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-color: var(--bg-main);
        }
        .login-card-box {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        .login-header-top {
            background: linear-gradient(135deg, #d97706 0%, #ea580c 100%);
            padding: 26px 24px 20px 24px;
            color: #ffffff;
            text-align: center;
        }
        .login-sheet-card {
            padding: 24px 24px 30px 24px;
            background: #ffffff;
        }
    </style>
</head>
<body>

<div class="login-page-wrapper">
    <div class="login-card-box">
        <div class="login-header-top">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #ffffff; color: #ea580c; font-weight: 900; font-size: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto;">
                KC
            </div>
            <h2 style="font-size: 19px; font-weight: 900;">Kino Coin Laundry</h2>
            <p style="font-size: 12px; opacity: 0.9;">Portal Pelanggan</p>
        </div>

        <div class="login-sheet-card">
            <h2 style="font-size: 19px; font-weight: 900; color: var(--text-main); margin-bottom: 4px;">Daftar Akun Baru</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 18px;">Isi data diri untuk membuat akun pelanggan</p>
<?php if (!empty($reg_error)): ?>
            <div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:10px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:14px;">
                <?= htmlspecialchars($reg_error) ?>
            </div>
        <?php endif; ?>

            <form action="daftar.php" method="POST" id="formDaftarPelanggan">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" placeholder="Nama lengkap Anda" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="text" id="nomorWhatsApp" name="wa" class="form-control" placeholder="08123456789" inputmode="numeric" value="<?= htmlspecialchars($wa ?? '') ?>" aria-describedby="peringatanNomorWhatsApp" required>
                    <small id="peringatanNomorWhatsApp" role="alert" aria-live="polite" style="display:<?= $wa_invalid ? 'block' : 'none' ?>; color:#dc2626; font-size:12px; margin-top:5px;">
                        Nomor WhatsApp hanya boleh berisi angka.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="passDaftar" name="password" class="form-control" placeholder="Buat password" style="padding-right: 80px;" required>
                        <button type="button" onclick="togglePassword('passDaftar', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 12px; font-weight: 700; color: #ea580c; cursor: pointer;">
                            Lihat
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Konfirmasi Password</label>
                    <div style="position: relative;">
                        <input type="password" id="passConfirm" name="confirm_password" class="form-control" placeholder="Ulangi password" style="padding-right: 80px;" required>
                        <button type="button" onclick="togglePassword('passConfirm', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 12px; font-weight: 700; color: #ea580c; cursor: pointer;">
                            Lihat
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-orange" style="margin-top: 14px; padding: 14px; font-size: 15px;">
                    Daftar Akun
                </button>
            </form>

            <div style="margin-top: 18px; text-align: center; font-size: 13px;">
                <a href="login.php" style="color: #ea580c; text-decoration: none; font-weight: 700;">
                    Sudah punya akun? Masuk di sini
                </a>
            </div>

            <div style="margin-top: 12px; text-align: center;">
                <a href="../index.php" style="font-size: 12px; color: var(--text-muted); text-decoration: none;">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formDaftarPelanggan').addEventListener('submit', function (event) {
    const nomorWhatsApp = document.getElementById('nomorWhatsApp');
    const peringatan = document.getElementById('peringatanNomorWhatsApp');
    const hanyaAngka = /^[0-9]+$/.test(nomorWhatsApp.value.trim());

    peringatan.style.display = hanyaAngka ? 'none' : 'block';
    if (!hanyaAngka) {
        event.preventDefault();
        nomorWhatsApp.focus();
    }
});
</script>
<script src="../assets/js/app.js"></script>
</body>
</html>
