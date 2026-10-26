<?php
// Controller modul auth
if ($module === 'auth') {
    if ($action === 'login') {
        method('POST');
        $b = body();
        need($b, ['username', 'password']);
        $attempt = $_SESSION['login_attempt'] ?? ['count' => 0, 'until' => 0];
        if ($attempt['until'] > time()) respond(['error' => 'Terlalu banyak percobaan, coba lagi nanti'], 429);
        $u = one(
            'SELECT * FROM pengguna WHERE username = $1',
            [strval_required($b['username'], 50)]
        );
        if (!$u || !password_verify((string)$b['password'], $u['password'])) {
            $count = (int)$attempt['count'] + 1;
            $_SESSION['login_attempt'] = ['count' => $count, 'until' => $count >= 5 ? time() + 300 : 0];
            respond(['error' => 'Username atau password salah'], 401);
        }
        unset($_SESSION['login_attempt']);
        session_regenerate_id(true);
        unset($_SESSION['customer_id']);
        $_SESSION['user_id'] = (int)$u['id_pengguna'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        respond(['message' => 'Login berhasil', 'user' => ['id_pengguna' => $u['id_pengguna'], 'nama' => $u['nama'], 'role' => $u['role']], 'csrf_token' => $_SESSION['csrf']]);
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
        session_destroy();
        respond(['message' => 'Logout berhasil']);
    }
    respond(['error' => 'Action tidak dikenal'], 404);
}
