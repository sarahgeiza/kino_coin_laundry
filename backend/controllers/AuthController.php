<?php
declare(strict_types=1);

// Authentication berbasis akun database
if ($module === 'auth') {
    if ($action === 'register') {
        method('POST');
        $b = body();
        need($b, ['nama_pelanggan', 'no_hp', 'password']);
        $name = strval_required($b['nama_pelanggan'], 100);
        $phone = preg_replace('/[\\s()-]+/', '', strval_required($b['no_hp'], 20));
        $password = (string) $b['password'];
        if (!preg_match('/^\\+?[0-9]{9,15}$/', $phone))
            respond(['error' => 'Nomor WhatsApp tidak valid'], 422);
        if (strlen($password) < 8)
            respond(['error' => 'Password minimal 8 karakter'], 422);
        if (one('SELECT 1 FROM pelanggan WHERE no_hp = ?', [$phone]))
            respond(['error' => 'Nomor WhatsApp sudah terdaftar'], 409);
        $customer = one(
            'INSERT INTO pelanggan(nama_pelanggan,no_hp,password_hash) VALUES(?,?,?) RETURNING id_pelanggan,nama_pelanggan',
            [$name, $phone, password_hash($password, PASSWORD_DEFAULT)]
        );
        session_regenerate_id(true);
        unset($_SESSION['user_id']);
        $_SESSION['customer_id'] = (int) $customer['id_pelanggan'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        respond([
            'message' => 'Registrasi berhasil',
            'user' => [
                'id' => (int) $customer['id_pelanggan'],
                'nama' => $customer['nama_pelanggan'],
                'role' => 'pelanggan'
            ],
            'csrf_token' => $_SESSION['csrf']
        ], 201);
    }

    if ($action === 'login') {
        method('POST');
        $b = body();
        need($b, ['username', 'password']);

        $attempt = $_SESSION['login_attempt'] ?? ['count' => 0, 'until' => 0];
        if ((int) $attempt['until'] > time()) {
            respond(['error' => 'Terlalu banyak percobaan, coba lagi nanti'], 429);
        }

        $username = strval_required($b['username'], 80);
        $password = (string) $b['password'];
        $account = one(
            'SELECT id_pengguna AS id, nama, username, role, password AS password_hash
             FROM pengguna WHERE username = ?',
            [$username]
        );

        if ($account && password_verify($password, (string) $account['password_hash'])) {
            unset($account['password_hash']);
            session_regenerate_id(true);
            unset($_SESSION['customer_id']);
            $_SESSION['user_id'] = (int) $account['id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            unset($_SESSION['login_attempt']);
            respond([
                'message' => 'Login berhasil',
                'user' => [
                    'id' => (int) $account['id'],
                    'nama' => $account['nama'],
                    'role' => $account['role']
                ],
                'csrf_token' => $_SESSION['csrf']
            ]);
        }

        // Pelanggan masuk menggunakan nomor WhatsApp dan password yang terdaftar.
        $customer = one(
            'SELECT id_pelanggan AS id, nama_pelanggan AS nama, no_hp, password_hash
             FROM pelanggan WHERE no_hp = ?',
            [$username]
        );
        if (
            $customer && !empty($customer['password_hash']) &&
            password_verify($password, (string) $customer['password_hash'])
        ) {
            unset($customer['password_hash']);
            session_regenerate_id(true);
            unset($_SESSION['user_id']);
            $_SESSION['customer_id'] = (int) $customer['id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            unset($_SESSION['login_attempt']);
            respond([
                'message' => 'Login berhasil',
                'user' => [
                    'id' => (int) $customer['id'],
                    'nama' => $customer['nama'],
                    'role' => 'pelanggan'
                ],
                'csrf_token' => $_SESSION['csrf']
            ]);
        }

        $count = (int) $attempt['count'] + 1;
        $_SESSION['login_attempt'] = [
            'count' => $count,
            'until' => $count >= 5 ? time() + 300 : 0
        ];
        respond(['error' => 'Username/nomor WhatsApp atau password salah'], 401);
    }

    if ($action === 'me') {
        method('GET');
        $u = actor();
        respond(['user' => $u, 'csrf_token' => $_SESSION['csrf'] ?? '']);
    }

    if ($action === 'logout') {
        method('POST');
        actor();
        csrf();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        respond(['message' => 'Logout berhasil']);
    }

    respond(['error' => 'Action tidak dikenal'], 404);
}
