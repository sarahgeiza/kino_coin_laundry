<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/data.php';

$login_error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = 'Username dan password wajib diisi.';
    } else {
        $response = api_call('auth', 'login', 'POST', [], [
            'username' => $username,
            'password' => $password,
        ]);

        if (!api_ok($response)) {
            $login_error = $response['status'] === 401
                ? 'Username atau password salah.'
                : api_error_message($response);
        } else {
            $account = $response['data']['user'] ?? [];
            $role = strtolower((string) ($account['role'] ?? ''));

            if ($role !== 'kasir') {
                unset($_SESSION['user_id'], $_SESSION['customer_id'], $_SESSION['role'], $_SESSION['username']);
                $login_error = 'Akun ini bukan kasir. Gunakan portal yang sesuai.';
            } else {
                $_SESSION['user_id'] = (int) ($account['id'] ?? 0);
                $_SESSION['username'] = (string) ($account['nama'] ?? '');
                $_SESSION['role'] = 'kasir';
                $_SESSION['csrf'] = (string) ($response['data']['csrf_token'] ?? bin2hex(random_bytes(32)));

                if ('kasir' === 'kasir') {
                    $active = api_call('shift-operasional', 'current', 'GET');
                    $_SESSION['kasir_checked_in'] = api_ok($active) && !empty($active['data']['data']);
                }

                header('Location: kasir/index.php');
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
    <title>Login Kasir - Kino Coin Laundry</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
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
            <div style="width: 112px; height: 112px; border-radius: 28px; background: #f59e0b; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; box-shadow: 0 6px 18px rgba(245, 158, 11, 0.4); padding: 12px; overflow: hidden;">
                <img src="assets/img/logo-kino-badge.png" alt="Kino Coin Laundry" style="width:100%; height:100%; object-fit:contain; display:block;">
            </div>
            <h2 style="font-size: 20px; font-weight: 900;">Kino Coin Laundry</h2>
            <p style="font-size: 13px; opacity: 0.88; margin-top: 2px;">Portal Kasir</p>
        </div>

        <div class="login-sheet-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <h2 style="font-size: 19px; font-weight: 900; color: var(--text-main);">Selamat Datang</h2>
                <span class="badge badge-token" style="font-size: 12px; padding: 4px 10px;">Kasir</span>
            </div>
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 22px;">Masuk dengan akun staff Anda</p>

            <?php if (!empty($login_error)): ?>
                <div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:12px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:16px; line-height: 1.5;">
                    <?= htmlspecialchars($login_error) ?>
                </div>
            <?php endif; ?>

            <form action="login-kasir.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Username / Email</label>
                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? 'sarah') ?>" placeholder="Contoh: sarah atau ramzy" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="inputPassKasir" name="password" class="form-control" value="<?= htmlspecialchars($_POST['password'] ?? 'kasir123') ?>" style="padding-right: 80px;" required>
                        <button type="button" class="btn-toggle-pass" onclick="togglePassword('inputPassKasir', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 12px; font-weight: 700; color: #2563eb; cursor: pointer;">
                            Lihat
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 14px; padding: 14px; font-size: 15px;">
                    Masuk sebagai Kasir
                </button>
            </form>

            <div style="margin-top: 22px; text-align: center;">
                <a href="index.php" style="font-size: 13px; color: var(--text-muted); text-decoration: none; font-weight: 700;">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
