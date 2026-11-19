<?php
declare(strict_types=1);

if ($module === 'pelanggan') {
    if ($action === 'list') {
        method('GET');
        $q = trim((string)($_GET['q'] ?? ''));
        $page = max(1, isset($_GET['page']) ? integer($_GET['page']) : 1);
        $limit = min(100, max(1, isset($_GET['limit']) ? integer($_GET['limit']) : 20));
        $offset = ($page - 1) * $limit;

        $where = '';
        $params = [];
        if ($q !== '') {
            $where = " WHERE p.nama_pelanggan ILIKE ? OR p.no_hp ILIKE ? OR p.kode_kartu ILIKE ?";
            $params = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%'];
        }

        $countRow = one("SELECT COUNT(*)::int AS total FROM pelanggan p" . $where, $params);
        $total = (int)($countRow['total'] ?? 0);

        $queryParams = array_merge($params, [$limit, $offset]);
        $rows = rows(
            "SELECT p.id_pelanggan, p.nama_pelanggan, p.no_hp,
                    p.nama_pelanggan AS nama, p.no_hp AS wa,
                    p.kode_kartu,
                    (p.password_hash IS NOT NULL) AS is_member,
                    COALESCE(agg.tx_count, 0)::int AS tx_count,
                    COALESCE(agg.total_koin, 0)::int AS total_koin,
                    agg.terakhir_transaksi
             FROM pelanggan p
             LEFT JOIN (
                 SELECT t.id_pelanggan,
                        COUNT(DISTINCT t.id_transaksi) AS tx_count,
                        COALESCE(SUM(dt.jumlah_koin), 0) AS total_koin,
                        MAX(t.tanggal_transaksi) AS terakhir_transaksi
                 FROM transaksi t
                 LEFT JOIN detail_transaksi dt ON dt.id_transaksi = t.id_transaksi
                 GROUP BY t.id_pelanggan
             ) agg ON agg.id_pelanggan = p.id_pelanggan" . $where . "
             ORDER BY p.id_pelanggan DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        $data = array_map(static function (array $row): array {
            return [
                'id_pelanggan'       => (int) $row['id_pelanggan'],
                'nama_pelanggan'     => (string) $row['nama_pelanggan'],
                'no_hp'              => (string) $row['no_hp'],
                'nama'               => (string) $row['nama_pelanggan'],
                'wa'                 => (string) $row['no_hp'],
                'kode_kartu'         => (string) $row['kode_kartu'],
                'is_member'          => in_array($row['is_member'], [true, 't', 1, '1'], true),
                'tx_count'           => (int) ($row['tx_count'] ?? 0),
                'total_koin'         => (int) ($row['total_koin'] ?? 0),
                'terakhir_transaksi' => $row['terakhir_transaksi'] !== null ? (string) $row['terakhir_transaksi'] : null,
            ];
        }, $rows);

        respond([
            'data'  => $data,
            'page'  => $page,
            'limit' => $limit,
            'total' => $total,
        ]);
    }

    if ($action === 'detail') {
        method('GET');
        $p = one(
            "SELECT id_pelanggan, nama_pelanggan, no_hp,
                    nama_pelanggan AS nama, no_hp AS wa,
                    (password_hash IS NOT NULL) AS is_member,
                    COALESCE(kode_kartu, CONCAT('KC-P-', LPAD(id_pelanggan::text, 4, '0'))) AS kode_kartu
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

        $password = !empty($b['password']) ? password_hash((string)$b['password'], PASSWORD_DEFAULT) : null;
        $r = one(
            "INSERT INTO pelanggan (nama_pelanggan, no_hp, password_hash)
             VALUES (?, ?, ?)
             RETURNING id_pelanggan, nama_pelanggan, no_hp,
                       nama_pelanggan AS nama, no_hp AS wa, kode_kartu,
                       (password_hash IS NOT NULL) AS is_member",
            [$name, $phone, $password]
        );

        if ($r) {
            $r['id_pelanggan'] = (int) $r['id_pelanggan'];
            $r['is_member'] = in_array($r['is_member'], [true, 't', 1, '1'], true);
        }

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
