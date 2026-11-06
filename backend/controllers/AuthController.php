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
                    respond(['error' => 'Nomor WhatsApp sudah memiliki akun'], 409);
                }
                if (strcasecmp(trim($existing['nama_pelanggan']), trim($name)) !== 0) {
                    pg_query($conn, 'ROLLBACK');
                    respond(['error' => 'Nama tidak cocok dengan data transaksi pelanggan'], 409);
                }
                $customer = one(
                    'UPDATE pelanggan
                     SET password_hash = ?
                     WHERE id_pelanggan = ?
                     RETURNING id_pelanggan, nama_pelanggan',
                    [password_hash($password, PASSWORD_DEFAULT), (int)$existing['id_pelanggan']]
                );
            } else {
                $nextId = one("SELECT nextval(pg_get_serial_sequence('pelanggan', 'id_pelanggan')) AS id");
                $customerId = (int)$nextId['id'];
                $cardCode = 'KC-P-' . str_pad((string)$customerId, 4, '0', STR_PAD_LEFT);
                $customer = one(
                    'INSERT INTO pelanggan(id_pelanggan, nama_pelanggan, no_hp, password_hash, kode_kartu)
                     VALUES(?, ?, ?, ?, ?)
                     RETURNING id_pelanggan, nama_pelanggan',
                    [$customerId, $name, $phone, password_hash($password, PASSWORD_DEFAULT), $cardCode]
                );
            }
            if (!pg_query($conn, 'COMMIT')) {
                throw new RuntimeException('Gagal menyimpan akun pelanggan.');
            }
        } catch (Throwable $e) {
            pg_query($conn, 'ROLLBACK');
            throw $e;
        }

        session_regenerate_id(true);
        unset($_SESSION['user_id']);
        $_SESSION['customer_id'] = (int) $customer['id_pelanggan'];
        $_SESSION['username'] = $customer['nama_pelanggan'];
        $_SESSION['role'] = 'pelanggan';
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
        $phone = preg_replace('/[\s()-]+/', '', $username);
        $phoneVariants = [$phone];
        if (str_starts_with($phone, '0')) {
            $phoneVariants[] = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '62')) {
            $phoneVariants[] = '0' . substr($phone, 2);
        }
        $customer = preg_match('/^\+?[0-9]{9,15}$/', $phone)
            ? one(
                'SELECT id_pelanggan AS id, nama_pelanggan AS nama, no_hp, password_hash
                 FROM pelanggan WHERE no_hp = ANY($1)',
                ['{' . implode(',', $phoneVariants) . '}']
            )
            : null;
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
        if (!empty($_SESSION['customer_id'])) {
            $customer = one(
                'SELECT id_pelanggan AS id, nama_pelanggan AS nama, no_hp
                 FROM pelanggan WHERE id_pelanggan = ?',
                [(int)$_SESSION['customer_id']]
            );
            if (!$customer) respond(['error' => 'Akun pelanggan tidak ditemukan'], 401);
            respond(['user' => $customer + ['role' => 'pelanggan'], 'csrf_token' => $_SESSION['csrf'] ?? '']);
        }
        $u = actor();
        respond(['user' => $u, 'csrf_token' => $_SESSION['csrf'] ?? '']);
    }

    if ($action === 'logout') {
        method('POST');
        if (empty($_SESSION['customer_id'])) actor();
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
