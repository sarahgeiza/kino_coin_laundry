<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/data.php';

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
        $response = api_call('auth', 'login', 'POST', [], [
            'username' => $wa,
            'password' => $pass,
        ]);

        if (!api_ok($response)) {
            if ($response['status'] === 401) {
                $login_error = 'Nomor WhatsApp belum terdaftar atau password salah.';
            } else {
                $login_error = api_error_message($response);
            }
        } else {
            $account = $response['data']['user'] ?? [];

            if (strtolower((string) ($account['role'] ?? '')) !== 'pelanggan') {
                unset($_SESSION['user_id'], $_SESSION['customer_id'], $_SESSION['role'], $_SESSION['username']);
                $login_error = 'Akun ini bukan akun pelanggan. Gunakan portal staf.';
            } else {
                $_SESSION['customer_id'] = (int) ($account['id'] ?? 0);
                $_SESSION['user_id'] = $_SESSION['customer_id'];
                $_SESSION['username'] = (string) ($account['nama'] ?? '');
                $_SESSION['role'] = 'pelanggan';
                $_SESSION['csrf'] = (string) ($response['data']['csrf_token'] ?? bin2hex(random_bytes(32)));
                header('Location: index.php');
                exit;
            }
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
            <div style="width: 112px; height: 112px; border-radius: 28px; background: #f59e0b; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; box-shadow: 0 6px 18px rgba(0,0,0,0.22); padding: 12px; overflow: hidden;">
                <img src="../assets/img/logo-kino-badge.png" alt="Kino Coin Laundry" style="width:100%; height:100%; object-fit:contain; display:block;">
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

            <form action="login.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="text" id="custLoginWa" name="wa" class="form-control" value="<?= htmlspecialchars($_POST['wa'] ?? '') ?>" autocomplete="off" placeholder="08xxxxxxxxxx" inputmode="numeric" pattern="[0-9]+" title="Nomor WhatsApp hanya boleh berisi angka" aria-describedby="custLoginWaWarning" oninput="validateCustLoginWa(this)" required>
                    <small id="custLoginWaWarning" role="alert" aria-live="polite" style="display:none; color:#dc2626; font-size:12px; margin-top:5px;">
                        Nomor WhatsApp hanya boleh berisi angka.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="inputPassCust" name="password" class="form-control" value="" autocomplete="new-password" style="padding-right: 80px;" required>
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
