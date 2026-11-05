<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$reg_error = '';
$wa_invalid = false;
if (empty($_SESSION['customer_register_csrf'])) {
    $_SESSION['customer_register_csrf'] = bin2hex(random_bytes(32));
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $wa = trim($_POST['wa'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (
        empty($_POST['csrf_token'])
        || !hash_equals($_SESSION['customer_register_csrf'], (string)$_POST['csrf_token'])
    ) {
        $reg_error = 'Sesi pendaftaran tidak valid. Muat ulang halaman dan coba kembali.';
    } elseif (empty($nama) || empty($wa) || empty($pass)) {
        $reg_error = 'Seluruh kolom pendaftaran wajib diisi.';
    } elseif (!preg_match('/^[0-9]+$/', $wa)) {
        $wa_invalid = true;
        $reg_error = 'Nomor WhatsApp hanya boleh berisi angka.';
    } elseif (strlen($wa) < 10 || strlen($wa) > 15) {
        $wa_invalid = true;
        $reg_error = 'Nomor WhatsApp tidak valid (minimal 10 digit, maksimal 15 digit).';
    } elseif (strlen($pass) < 8) {
        $reg_error = 'Password minimal 8 karakter.';
    } elseif ($pass !== $confirm) {
        $reg_error = 'Konfirmasi password tidak cocok. Silakan ulangi.';
    } else {
        try {
            $phone = preg_replace('/[\s()-]+/', '', $wa);
            $phoneVariants = [$phone];
            if (str_starts_with($phone, '0')) {
                $phoneVariants[] = '62' . substr($phone, 1);
            } elseif (str_starts_with($phone, '62')) {
                $phoneVariants[] = '0' . substr($phone, 2);
            }

            $conn = getConnection();
            if (!pg_query($conn, 'BEGIN')) {
                throw new RuntimeException('Gagal memulai pendaftaran pelanggan.');
            }

            try {
                $existing = one(
                    'SELECT id_pelanggan, nama_pelanggan, password_hash
                     FROM pelanggan
                     WHERE no_hp = ANY($1)
                     FOR UPDATE',
                    ['{' . implode(',', $phoneVariants) . '}']
                );

                if ($existing) {
                    if (!empty($existing['password_hash'])) {
                        pg_query($conn, 'ROLLBACK');
                        $reg_error = 'Nomor WhatsApp sudah memiliki akun. Silakan masuk melalui halaman login.';
                    } elseif (strcasecmp(trim($existing['nama_pelanggan']), trim($nama)) !== 0) {
                        pg_query($conn, 'ROLLBACK');
                        $reg_error = 'Nama tidak cocok dengan data pelanggan saat transaksi. Periksa nama dan nomor WhatsApp yang digunakan kasir.';
                    } else {
                        execute_sql(
                            'UPDATE pelanggan SET password_hash = ? WHERE id_pelanggan = ?',
                            [password_hash($pass, PASSWORD_DEFAULT), (int)$existing['id_pelanggan']]
                        );
                        if (!pg_query($conn, 'COMMIT')) {
                            throw new RuntimeException('Gagal menyimpan password pelanggan.');
                        }
                        $customerId = (int)$existing['id_pelanggan'];
                        $customerName = $existing['nama_pelanggan'];
                    }
                } else {
                    $nextId = one("SELECT nextval(pg_get_serial_sequence('pelanggan', 'id_pelanggan')) AS id");
                    $customerId = (int)$nextId['id'];
                    $customerName = $nama;
                    $newCode = 'KC-P-' . str_pad((string)$customerId, 4, '0', STR_PAD_LEFT);
                    one(
                        'INSERT INTO pelanggan (id_pelanggan, nama_pelanggan, no_hp, password_hash, kode_kartu)
                         VALUES (?, ?, ?, ?, ?)',
                        [$customerId, $nama, $phone, password_hash($pass, PASSWORD_DEFAULT), $newCode]
                    );
                    if (!pg_query($conn, 'COMMIT')) {
                        throw new RuntimeException('Gagal menyimpan pendaftaran pelanggan.');
                    }
                }

                if (empty($reg_error)) {
                    session_regenerate_id(true);
                    unset($_SESSION['user_id']);
                    $_SESSION['customer_id'] = $customerId;
                    $_SESSION['username'] = $customerName;
                    $_SESSION['role'] = 'pelanggan';
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    header('Location: index.php?toast=registered');
                    exit;
                }
            } catch (Throwable $e) {
                pg_query($conn, 'ROLLBACK');
                throw $e;
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $reg_error = 'Pendaftaran tidak dapat disimpan. Periksa kembali nomor WhatsApp atau hubungi kasir.';
        }
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
                <div style="background:#fee2e2; border:1px solid #fca5a5; color:#dc2626; padding:12px 14px; border-radius:10px; font-size:12px; font-weight:700; margin-bottom:14px; line-height: 1.5;">
                    <?= htmlspecialchars($reg_error) ?>
                </div>
            <?php endif; ?>

            <form action="daftar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['customer_register_csrf']) ?>">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" placeholder="Nama lengkap Anda" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="text" id="custRegWa" name="wa" class="form-control" placeholder="08123456789" value="<?= htmlspecialchars($_POST['wa'] ?? '') ?>" inputmode="numeric" pattern="[0-9]+" title="Nomor WhatsApp hanya boleh berisi angka" aria-describedby="custRegWaWarning" oninput="validateCustRegWa(this)" required>
                    <small id="custRegWaWarning" role="alert" aria-live="polite" style="display:none; color:#dc2626; font-size:12px; margin-top:5px;">
                        Nomor WhatsApp hanya boleh berisi angka.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="passDaftar" name="password" class="form-control" placeholder="Buat password (minimal 8 karakter)" style="padding-right: 80px;" required>
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

<script src="../assets/js/app.js"></script>
<script>
function validateCustRegWa(input) {
    const hasNonDigits = /[^0-9]/.test(input.value);
    document.getElementById('custRegWaWarning').style.display = hasNonDigits ? 'block' : 'none';
}
</script>
</body>
</html>
