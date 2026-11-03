<?php
declare(strict_types=1);

if ($module === 'pelanggan') {
    if ($action === 'list') {
        method('GET');
        $q = trim((string)($_GET['q'] ?? ''));
        $sql = "SELECT id_pelanggan, nama_pelanggan, no_hp, dibuat_pada,
                       nama_pelanggan AS nama, no_hp AS wa,
                       CONCAT('KC-P-', LPAD(id_pelanggan::text, 4, '0')) AS kode_kartu
                FROM pelanggan
                WHERE nama_pelanggan ILIKE ? OR no_hp ILIKE ?
                ORDER BY id_pelanggan DESC
                LIMIT 100";
        respond(['data' => rows($sql, ['%' . $q . '%', '%' . $q . '%'])]);
    }

    if ($action === 'detail') {
        method('GET');
        $p = one(
            "SELECT id_pelanggan, nama_pelanggan, no_hp, dibuat_pada,
                    nama_pelanggan AS nama, no_hp AS wa,
                    CONCAT('KC-P-', LPAD(id_pelanggan::text, 4, '0')) AS kode_kartu
             FROM pelanggan WHERE id_pelanggan = ?",
            [idparam()]
        );
        if (!$p) respond(['error' => 'Pelanggan tidak ditemukan'], 404);
        respond(['data' => $p]);
    }

    if ($action === 'create') {
        method('POST');
        csrf();
        $b = body();
        need($b, ['nama_pelanggan', 'no_hp']);

        $name = strval_required($b['nama_pelanggan'], 100);
        $phone = preg_replace('/[\s()-]+/', '', strval_required($b['no_hp'], 20));

        if (!preg_match('/^\+?[0-9]{9,15}$/', $phone)) {
            respond(['error' => 'Nomor WhatsApp tidak valid'], 422);
        }

        $exists = one('SELECT id_pelanggan FROM pelanggan WHERE no_hp = ?', [$phone]);
        if ($exists) {
            respond(['error' => 'Nomor WhatsApp sudah terdaftar'], 409);
        }

        $r = one(
            "INSERT INTO pelanggan(nama_pelanggan, no_hp)
             VALUES(?, ?)
             RETURNING id_pelanggan, nama_pelanggan, no_hp,
                       nama_pelanggan AS nama, no_hp AS wa,
                       CONCAT('KC-P-', LPAD(id_pelanggan::text, 4, '0')) AS kode_kartu",
            [$name, $phone]
        );
        respond(['data' => $r], 201);
    }

    if ($action === 'update') {
        method('POST');
        csrf();
        $b = body();
        need($b, ['id_pelanggan', 'nama_pelanggan', 'no_hp']);
        $id = integer($b['id_pelanggan']);
        $name = strval_required($b['nama_pelanggan'], 100);
        $phone = preg_replace('/[\s()-]+/', '', strval_required($b['no_hp'], 20));

        if (!preg_match('/^\+?[0-9]{9,15}$/', $phone)) {
            respond(['error' => 'Nomor WhatsApp tidak valid'], 422);
        }

        execute_sql('UPDATE pelanggan SET nama_pelanggan = ?, no_hp = ? WHERE id_pelanggan = ?', [$name, $phone, $id]);
        respond(['message' => 'Pelanggan diperbarui']);
    }

    if ($action === 'password') {
        method('POST');
        csrf();
        $b = body();
        need($b, ['id_pelanggan', 'password']);
        if (strlen((string)$b['password']) < 8) {
            respond(['error' => 'Password minimal 8 karakter'], 422);
        }
        execute_sql(
            'UPDATE pelanggan SET password_hash = ? WHERE id_pelanggan = ?',
            [password_hash((string)$b['password'], PASSWORD_DEFAULT), integer($b['id_pelanggan'])]
        );
        respond(['message' => 'Password pelanggan diperbarui']);
    }
}
