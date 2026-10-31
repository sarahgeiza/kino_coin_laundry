<?php
session_start();
require_once __DIR__ . '/../includes/data.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $_SESSION['username'] = 'Budi';
    $_SESSION['role'] = 'pelanggan';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - Kino Coin Laundry</title>
    <link rel="stylesheet" href="../css/style.css">
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

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="text" name="wa" class="form-control" value="08123456789" placeholder="Contoh: 08123456789" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="inputPassCust" name="password" class="form-control" value="password123" style="padding-right: 80px;" required>
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

<script src="../js/app.js"></script>
</body>
</html>
