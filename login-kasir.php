<?php
session_start();
require_once __DIR__ . '/includes/data.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $_SESSION['username'] = 'Daniel';
    $_SESSION['role'] = 'kasir';
    $_SESSION['kasir_checked_in'] = false; // Will trigger M1 checkin modal on dashboard!
    header('Location: kasir/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Kasir - Kino Coin Laundry</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-screen-flow {
            background: linear-gradient(180deg, #1e3a8a 0%, #2563eb 40%, #f8fafc 40%, #f8fafc 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .login-header-top {
            padding: 30px 24px 20px 24px;
            color: #ffffff;
            text-align: center;
        }
        .login-sheet-card {
            background: #ffffff;
            border-top-left-radius: 28px;
            border-top-right-radius: 28px;
            padding: 30px 24px 40px 24px;
            box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.08);
            flex: 1;
        }
    </style>
</head>
<body>

<div class="app-screen login-screen-flow">
    <div>
        <div class="status-bar white-text">
            <span>9:41</span>
            <div class="icons"><span>📶</span><span>🔋</span></div>
        </div>

        <div class="login-header-top">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: #f59e0b; color: #1e3a8a; font-weight: 900; font-size: 22px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto;">
                KC
            </div>
            <h2 style="font-size: 19px; font-weight: 900;">Kino Coin Laundry</h2>
            <p style="font-size: 12px; opacity: 0.85;">Portal Kasir</p>
        </div>
    </div>

    <div class="login-sheet-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <h2 style="font-size: 20px; font-weight: 900; color: var(--text-main);">Selamat Datang</h2>
            <span class="badge badge-token" style="font-size: 12px; padding: 4px 10px;">Kasir</span>
        </div>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px;">Masuk dengan akun staff Anda</p>

        <form action="login-kasir.php" method="POST">
            <div class="form-group">
                <label class="form-label">Username / Email</label>
                <input type="text" name="username" class="form-control" value="daniel" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" id="inputPassKasir" name="password" class="form-control" value="123456" style="padding-right: 80px;" required>
                    <button type="button" class="btn-toggle-pass" onclick="togglePassword('inputPassKasir', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 12px; font-weight: 700; color: #2563eb; cursor: pointer;">
                        Lihat
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 14px; padding: 14px; font-size: 15px;">
                Masuk sebagai Kasir
            </button>
        </form>

        <div style="margin-top: 24px; text-align: center;">
            <a href="index.php" style="font-size: 13px; color: var(--text-muted); text-decoration: none; font-weight: 700;">
                ← Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
