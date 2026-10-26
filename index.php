<?php
session_start();
require_once __DIR__ . '/includes/data.php';

// Handle login POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $role = $_POST['role'] ?? 'kasir';
    $username = trim($_POST['username'] ?? '');

    $_SESSION['user_role'] = $role;
    $_SESSION['username'] = !empty($username) ? $username : ($role === 'admin' ? 'pemilik' : ($role === 'pelanggan' ? 'budi' : 'daniel'));

    if ($role === 'admin' || $role === 'pemilik') {
        header('Location: admin/index.php');
        exit;
    } elseif ($role === 'pelanggan') {
        header('Location: pelanggan/index.php');
        exit;
    } else {
        header('Location: kasir/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kino Coin Laundry - Login</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: #ffffff;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 16px;
        }

        .login-card-container {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        /* Header in brand blue */
        .login-header {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            padding: 36px 24px 28px 24px;
            text-align: center;
            color: #ffffff;
        }

        .logo-box {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background: #f59e0b;
            color: #1e3a8a;
            font-size: 26px;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4);
            letter-spacing: -0.5px;
        }

        .login-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .login-subtitle {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 4px;
        }

        /* Form Body */
        .login-body {
            padding: 28px 24px 32px 24px;
            background: #ffffff;
        }

        .greeting {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .sub-greeting {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 22px;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 14px;
            color: #0f172a;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        /* Role Selector (3 Grid Cards) */
        .role-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 6px;
        }

        .role-item {
            position: relative;
            cursor: pointer;
            user-select: none;
        }

        .role-item input[type="radio"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
            margin: 0;
        }

        .role-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 12px 6px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background: #ffffff;
            transition: all 0.2s ease;
            text-align: center;
            gap: 6px;
            pointer-events: none;
        }

        .role-icon {
            font-size: 18px;
            line-height: 1;
        }

        .role-name {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }

        /* Selected State */
        .role-item input[type="radio"]:checked + .role-box,
        .role-item.is-selected .role-box {
            border-color: #2563eb;
            background-color: #eff6ff;
        }

        .role-item input[type="radio"]:checked + .role-box .role-name,
        .role-item.is-selected .role-box .role-name {
            color: #1d4ed8;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 14px 20px;
            background: #1d4ed8;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 18px;
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.3);
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background: #1e3a8a;
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        /* Mobile full-height view */
        @media (max-width: 480px) {
            body {
                padding: 0;
                justify-content: flex-end;
            }
            .login-card-container {
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                border: none;
                box-shadow: none;
            }
            .login-body {
                flex: 1;
            }
        }
    </style>
</head>
<body>

<div class="login-card-container">
    <!-- Header tetap sesuai mockup -->
    <div class="login-header">
        <div class="logo-box">KC</div>
        <h1 class="login-title">Kino Coin Laundry</h1>
        <p class="login-subtitle">Sistem Manajemen Koin &amp; Shift</p>
    </div>

    <!-- Form Body -->
    <div class="login-body">
        <h2 class="greeting">Selamat Datang 👋</h2>
        <p class="sub-greeting">Pilih peran dan masuk</p>

        <form action="index.php" method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="usernameInput">Username / Email</label>
                <input type="text" name="username" id="usernameInput" class="form-control" value="daniel" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="passwordInput">Password</label>
                <input type="password" name="password" id="passwordInput" class="form-control" value="password123" required>
            </div>

            <div class="form-group">
                <label class="form-label">Masuk Sebagai</label>
                <div class="role-grid">
                    <!-- Kasir -->
                    <label class="role-item is-selected" id="item-kasir">
                        <input type="radio" name="role" value="kasir" checked onchange="handleRoleChange('kasir', 'daniel')">
                        <div class="role-box">
                            <span class="role-icon">👤</span>
                            <span class="role-name">Kasir</span>
                        </div>
                    </label>

                    <!-- Pemilik -->
                    <label class="role-item" id="item-admin">
                        <input type="radio" name="role" value="admin" onchange="handleRoleChange('admin', 'pemilik')">
                        <div class="role-box">
                            <span class="role-icon">👑</span>
                            <span class="role-name">Pemilik</span>
                        </div>
                    </label>

                    <!-- Pelanggan -->
                    <label class="role-item" id="item-pelanggan">
                        <input type="radio" name="role" value="pelanggan" onchange="handleRoleChange('pelanggan', 'budi')">
                        <div class="role-box">
                            <span class="role-icon">🧺</span>
                            <span class="role-name">Pelanggan</span>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-submit">Masuk</button>
        </form>
    </div>
</div>

<script>
function handleRoleChange(role, defaultUsername) {
    document.querySelectorAll('.role-item').forEach(el => el.classList.remove('is-selected'));
    
    let activeId = 'item-kasir';
    if (role === 'admin' || role === 'pemilik') activeId = 'item-admin';
    else if (role === 'pelanggan') activeId = 'item-pelanggan';
    
    const activeEl = document.getElementById(activeId);
    if (activeEl) activeEl.classList.add('is-selected');

    const uInput = document.getElementById('usernameInput');
    if (uInput) uInput.value = defaultUsername;
}

document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="role"]');
    radios.forEach(radio => {
        radio.addEventListener('click', function() {
            let role = this.value;
            let user = 'daniel';
            if (role === 'admin') user = 'pemilik';
            if (role === 'pelanggan') user = 'budi';
            handleRoleChange(role, user);
        });
    });
});
</script>

</body>
</html>
