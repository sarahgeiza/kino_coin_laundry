<?php
declare(strict_types=1);

// Controller modul layanan

if ($module === 'layanan') {

    if ($action === 'list') {
        method('GET');

        $u = actor();

        $sql = ($u['role'] ?? '') === 'pemilik'
            ? 'SELECT * FROM layanan ORDER BY id_layanan'
            : 'SELECT * FROM layanan WHERE aktif = TRUE ORDER BY id_layanan';

        respond(['data' => rows($sql)]);
    }

    if ($action === 'detail') {
        method('GET');

        $id = integer(idparam());

        $layanan = (new Layanan(getConnection()))->getById($id);

        if (!$layanan) {
            respond(['error' => 'Layanan tidak ditemukan'], 404);
        }

        respond(['data' => $layanan]);
    }

    if ($action === 'create' || $action === 'update') {
        method('POST');
        csrf();

        owner();

        $b = body();

        need($b, ['nama_layanan', 'harga', 'jumlah_koin']);

        $harga = integer($b['harga'], 1);
        $jumlahKoin = integer($b['jumlah_koin'], 0);

        if ($jumlahKoin > 100) {
            respond(['error' => 'Jumlah koin mesin maksimal 100'], 422);
        }

        $berat = isset($b['berat_standar'])
            ? positive_weight($b['berat_standar'])
            : 7.5;

        $durasi = isset($b['durasi'])
            ? strval_required($b['durasi'], 60)
            : '1 hari';

        $aktif = isset($b['aktif'])
            ? filter_var($b['aktif'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        if (isset($b['aktif']) && $aktif === null) {
            respond(['error' => 'Status layanan tidak valid'], 422);
        }

        if ($action === 'create') {
            $aktif = $aktif ?? true;

            $r = one(
                'INSERT INTO layanan (
                    nama_layanan, harga, berat_standar,
                    durasi, aktif, jumlah_koin
                 )
                 VALUES (?, ?, ?, ?, ?, ?)
                 RETURNING id_layanan',
                [
                    strval_required($b['nama_layanan'], 50),
                    $harga,
                    $berat,
                    $durasi,
                    $aktif,
                    $jumlahKoin
                ]
            );

            respond(['data' => $r], 201);
        }

        need($b, ['id_layanan']);

        $id = integer($b['id_layanan']);

        if ($aktif === null) {
            $existing = one(
                'SELECT aktif FROM layanan WHERE id_layanan = ?',
                [$id]
            );

            if (!$existing) {
                respond(['error' => 'Layanan tidak ditemukan'], 404);
            }

            $aktif = in_array($existing['aktif'], [true, 't', '1', 1], true);
        }

        execute_sql(
            'UPDATE layanan
             SET nama_layanan = ?,
                 harga = ?,
                 berat_standar = ?,
                 durasi = ?,
                 aktif = ?,
                 jumlah_koin = ?
             WHERE id_layanan = ?',
            [
                strval_required($b['nama_layanan'], 50),
                $harga,
                $berat,
                $durasi,
                $aktif,
                $jumlahKoin,
                $id
            ]
        );

        respond(['message' => 'Layanan diperbarui']);
    }

    if ($action === 'toggle') {
        method('POST');
        csrf();
        owner();

        $b = body();
        need($b, ['id_layanan', 'aktif']);

        $active = filter_var(
            $b['aktif'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($active === null) {
            respond(['error' => 'Status tidak valid'], 422);
        }

        execute_sql(
            'UPDATE layanan SET aktif = ? WHERE id_layanan = ?',
            [$active, integer($b['id_layanan'])]
        );

        respond(['message' => 'Status layanan diperbarui']);
    }

    if ($action === 'delete') {
        method('POST');
        csrf();
        owner();

        $b = body();
        need($b, ['id_layanan']);

        $id = integer($b['id_layanan']);

        if (
            one(
                'SELECT 1 FROM detail_transaksi
                 WHERE id_layanan = ?
                 LIMIT 1',
                [$id]
            )
        ) {
            respond([
                'error' => 'Layanan sudah dipakai transaksi; tidak dapat dihapus'
            ], 409);
        }

        execute_sql(
            'DELETE FROM layanan WHERE id_layanan = ?',
            [$id]
        );

        respond(['message' => 'Layanan dihapus']);
    }
}
