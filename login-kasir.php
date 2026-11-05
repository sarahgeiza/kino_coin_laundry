<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$login_error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = 'Username dan password wajib diisi.';
    } else {
        try {
            $matched_user = one(
                'SELECT id_pengguna, nama, username, role, password
                 FROM pengguna
                 WHERE role = $1
                   AND (LOWER(username) = LOWER($2) OR LOWER(nama) = LOWER($2))
                 ORDER BY CASE WHEN LOWER(username) = LOWER($2) THEN 0 ELSE 1 END
                 LIMIT 1',
                ['kasir', $username]
            );

            if (!$matched_user) {
                $login_error = 'Akun kasir tidak terdaftar di database. Hubungi Pemilik toko untuk membuat akun kasir.';
            } else {
                $storedPassword = (string) $matched_user['password'];
                $passwordValid = password_verify($password, $storedPassword);

                // Upgrade password lama plaintext ke hash setelah login yang valid.
                if (!$passwordValid && hash_equals($storedPassword, $password)) {
                    $passwordValid = true;
                    execute_sql(
                        'UPDATE pengguna SET password = $1 WHERE id_pengguna = $2',
                        [password_hash($password, PASSWORD_DEFAULT), (int) $matched_user['id_pengguna']]
                    );
                }

                if (!$passwordValid) {
                    $login_error = 'Password salah. Silakan periksa kembali kata sandi Anda.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $matched_user['id_pengguna'];
                    $_SESSION['username'] = $matched_user['nama'];
                    $_SESSION['role'] = $matched_user['role'];
                    $_SESSION['kasir_checked_in'] = false;
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    header('Location: kasir/index.php');
                    exit;
                }
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $login_error = 'Tidak dapat memeriksa akun kasir di database. Periksa konfigurasi PostgreSQL.';
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
                <div
                    style="width: 52px; height: 52px; border-radius: 14px; background: #f59e0b; color: #1e3a8a; font-weight: 900; font-size: 22px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);">
                    KC
                </div>
                <h2 style="font-size: 20px; font-weight: 900;">Kino Coin Laundry</h2>
                <p style="font-size: 13px; opacity: 0.88; margin-top: 2px;">Portal Kasir</p>
            </div>

            <div class="login-sheet-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <h2 style="font-size: 19px; font-weight: 900; color: var(--text-main);">Selamat Datang</h2>
                    <span class="badge badge-token" style="font-size: 12px; padding: 4px 10px;">Kasir</span>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 22px;">Masuk dengan akun staff Anda
                </p>

                <?php if (!empty($login_error)): ?>
                    <div
                        style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:12px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:16px; line-height: 1.5;">
                        <?= htmlspecialchars($login_error) ?>
                    </div>
                <?php endif; ?>

                <form action="login-kasir.php" method="POST">
                    <div class="form-group">
                        <label class="form-label">Username / Email</label>
                        <input type="text" name="username" class="form-control"
                            value="<?= htmlspecialchars($_POST['username'] ?? 'daniel') ?>" placeholder="Contoh: daniel"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="inputPassKasir" name="password" class="form-control"
                                value="<?= htmlspecialchars($_POST['password'] ?? '123456') ?>"
                                style="padding-right: 80px;" required>
                            <button type="button" class="btn-toggle-pass"
                                onclick="togglePassword('inputPassKasir', this)"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 12px; font-weight: 700; color: #2563eb; cursor: pointer;">
                                Lihat
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"
                        style="margin-top: 14px; padding: 14px; font-size: 15px;">
                        Masuk sebagai Kasir
                    </button>
                </form>

                <div style="margin-top: 22px; text-align: center;">
                    <a href="index.php"
                        style="font-size: 13px; color: var(--text-muted); text-decoration: none; font-weight: 700;">
                        ← Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>

</html>