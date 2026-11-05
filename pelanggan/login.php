<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$login_error = '';
$wa_val = trim($_POST['wa'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $wa = trim($_POST['wa'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (empty($wa) || empty($pass)) {
        $login_error = 'Nomor WhatsApp dan password wajib diisi.';
    } elseif (!preg_match('/^[0-9]+$/', $wa)) {
        $login_error = 'Nomor WhatsApp hanya boleh berisi angka.';
    } else {
        try {
            $phone = preg_replace('/[\s()-]+/', '', $wa);
            $phoneVariants = [$phone];
            if (str_starts_with($phone, '0')) {
                $phoneVariants[] = '62' . substr($phone, 1);
            } elseif (str_starts_with($phone, '62')) {
                $phoneVariants[] = '0' . substr($phone, 2);
            }

            $matched = one(
                'SELECT id_pelanggan, nama_pelanggan, no_hp, password_hash, kode_kartu
                 FROM pelanggan
                 WHERE no_hp = ANY($1)
                 LIMIT 1',
                ['{' . implode(',', $phoneVariants) . '}']
            );

            if (!$matched) {
                $login_error = 'Nomor WhatsApp belum ditemukan. Jika Anda pernah bertransaksi, periksa kembali nomor yang dicatat kasir.';
            } elseif (empty($matched['password_hash'])) {
                $login_error = 'Akun untuk nomor ini belum diaktifkan. Pilih Daftar dan gunakan nama serta nomor yang sama seperti saat transaksi untuk membuat password.';
            } elseif (!password_verify($pass, (string)$matched['password_hash'])) {
                $login_error = 'Password salah. Silakan periksa kembali kata sandi Anda.';
            } else {
                session_regenerate_id(true);
                unset($_SESSION['user_id']);
                $_SESSION['customer_id'] = (int)$matched['id_pelanggan'];
                $_SESSION['username'] = $matched['nama_pelanggan'];
                $_SESSION['role'] = 'pelanggan';
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                header('Location: index.php');
                exit;
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $login_error = 'Tidak dapat memeriksa akun pelanggan. Silakan coba kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - Kino Coin Laundry</title>
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
            padding: 32px 24px 26px 24px;
            color: #ffffff;
            text-align: center;
        }
        .login-sheet-card {
            padding: 28px 24px 34px 24px;
            background: #ffffff;
        }
    </style>
</head>
<body>

<div class="login-page-wrapper">
    <div class="login-card-box">
        <div class="login-header-top">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: #ffffff; color: #ea580c; font-weight: 900; font-size: 22px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                KC
            </div>
            <h2 style="font-size: 20px; font-weight: 900;">Kino Coin Laundry</h2>
            <p style="font-size: 13px; opacity: 0.9; margin-top: 2px;">Portal Pelanggan</p>
        </div>

        <div class="login-sheet-card">
            <h2 style="font-size: 20px; font-weight: 900; color: var(--text-main); margin-bottom: 4px;">Masuk Pelanggan</h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 22px;">Cek riwayat dan detail cucian Anda</p>

            <?php if (!empty($login_error)): ?>
                <div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:12px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:16px; line-height: 1.5;">
                    <?= htmlspecialchars($login_error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="text" id="custLoginWa" name="wa" class="form-control" value="<?= htmlspecialchars($_POST['wa'] ?? '08123456789') ?>" placeholder="Contoh: 08123456789" inputmode="numeric" pattern="[0-9]+" title="Nomor WhatsApp hanya boleh berisi angka" aria-describedby="custLoginWaWarning" oninput="validateCustLoginWa(this)" required>
                    <small id="custLoginWaWarning" role="alert" aria-live="polite" style="display:none; color:#dc2626; font-size:12px; margin-top:5px;">
                        Nomor WhatsApp hanya boleh berisi angka.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="inputPassCust" name="password" class="form-control" style="padding-right: 80px;" required>
                        <button type="button" onclick="togglePassword('inputPassCust', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 12px; font-weight: 700; color: #ea580c; cursor: pointer;">
                            Lihat
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-orange" style="margin-top: 14px; padding: 14px; font-size: 15px;">
                    Masuk
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center; font-size: 13px;">
                <a href="daftar.php" style="color: #ea580c; text-decoration: none; font-weight: 700;">
                    Belum punya akun? Daftar di sini
                </a>
            </div>

            <div style="margin-top: 14px; text-align: center;">
                <a href="../index.php" style="font-size: 12px; color: var(--text-muted); text-decoration: none;">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
function validateCustLoginWa(input) {
    const hasNonDigits = /[^0-9]/.test(input.value);
    document.getElementById('custLoginWaWarning').style.display = hasNonDigits ? 'block' : 'none';
}
</script>
</body>
</html>
