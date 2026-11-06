<?php
declare(strict_types=1);

if ($module === 'pengguna') {
    if ($action === 'list') {
        method('GET');
        owner();
        respond(['data' => rows('SELECT id_pengguna, nama, username, role FROM pengguna ORDER BY id_pengguna')]);
    }

    if ($action === 'detail') {
        method('GET');
        owner();
        respond(['data' => one('SELECT id_pengguna, nama, username, role FROM pengguna WHERE id_pengguna = ?', [idparam()])]);
    }

    if ($action === 'create') {
        method('POST');
        csrf();
        owner();
        $b = body();
        need($b, ['nama', 'username', 'role', 'password']);
        if (!in_array($b['role'], ['pemilik', 'kasir'], true)) {
            respond(['error' => 'Role tidak valid'], 422);
        }
        if (strlen((string)$b['password']) < 8) {
            respond(['error' => 'Password minimal 8 karakter'], 422);
        }
        $exists = one('SELECT id_pengguna FROM pengguna WHERE username = ?', [strval_required($b['username'], 50)]);
        if ($exists) {
            respond(['error' => 'Username sudah digunakan'], 409);
        }
        $r = one(
            'INSERT INTO pengguna(nama, username, role, password) VALUES(?, ?, ?, ?) RETURNING id_pengguna, nama, username, role',
            [strval_required($b['nama']), strval_required($b['username'], 50), $b['role'], password_hash((string)$b['password'], PASSWORD_DEFAULT)]
        );
        respond(['data' => $r], 201);
    }

    if ($action === 'update') {
        method('POST');
        csrf();
        owner();
        $b = body();
        need($b, ['id_pengguna', 'nama', 'username', 'role']);
        if (!in_array($b['role'], ['pemilik', 'kasir'], true)) {
            respond(['error' => 'Role tidak valid'], 422);
        }
        execute_sql(
            'UPDATE pengguna SET nama = ?, username = ?, role = ? WHERE id_pengguna = ?',
            [strval_required($b['nama']), strval_required($b['username'], 50), $b['role'], integer($b['id_pengguna'])]
        );
        respond(['message' => 'Pengguna diperbarui']);
    }

    if ($action === 'password') {
        method('POST');
        csrf();
        owner();
        $b = body();
        need($b, ['id_pengguna', 'password']);
        if (strlen((string)$b['password']) < 8) {
            respond(['error' => 'Password minimal 8 karakter'], 422);
        }
        execute_sql(
            'UPDATE pengguna SET password = ? WHERE id_pengguna = ?',
            [password_hash((string)$b['password'], PASSWORD_DEFAULT), integer($b['id_pengguna'])]
        );
        respond(['message' => 'Password diperbarui']);
    }

    if ($action === 'delete') {
        method('POST');
        csrf();
        owner();
        $b = body();
        need($b, ['id_pengguna']);
        $targetId = integer($b['id_pengguna']);
        $activeUser = actor();
        if ($targetId === (int)($activeUser['id_pengguna'] ?? 0)) {
            respond(['error' => 'Tidak dapat menghapus akun sendiri yang sedang aktif'], 422);
        }
        $txCount = one('SELECT COUNT(*) AS c FROM transaksi WHERE id_kasir = ?', [$targetId])['c'] ?? 0;
        $shiftCount = one('SELECT COUNT(*) AS c FROM shift_operasional WHERE id_kasir = ?', [$targetId])['c'] ?? 0;
        if ((int)$txCount > 0 || (int)$shiftCount > 0) {
            respond(['error' => 'Akun tidak dapat dihapus karena memiliki riwayat transaksi atau presensi shift'], 409);
        }
        execute_sql('DELETE FROM pengguna WHERE id_pengguna = ?', [$targetId]);
        respond(['message' => 'Pengguna berhasil dihapus']);
    }
}
