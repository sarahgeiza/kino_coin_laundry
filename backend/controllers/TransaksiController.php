<?php

// Controller modul transaksi
if ($module === 'transaksi') {

    // Daftar transaksi
    if ($action === 'list') {
        method('GET');

        $p = integer($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($p - 1) * $limit;

        respond([
            'data' => rows(
                'SELECT t.*,
                        p.nama_pelanggan,
                        u.nama AS nama_kasir,
                        s.nama_shift
                 FROM transaksi t
                 JOIN pelanggan p USING (id_pelanggan)
                 JOIN pengguna u ON u.id_pengguna = t.id_kasir
                 JOIN shift s USING (id_shift)
                 ORDER BY t.id_transaksi DESC
                 LIMIT 20 OFFSET ?',
                [$offset]
            )
        ]);
    }

    // Detail transaksi
    if ($action === 'detail') {
        method('GET');

        $id = idparam();

        $t = one(
            'SELECT t.*,
                    p.nama_pelanggan,
                    p.no_hp,
                    u.nama AS nama_kasir,
                    s.nama_shift
             FROM transaksi t
             JOIN pelanggan p USING (id_pelanggan)
             JOIN pengguna u ON u.id_pengguna = t.id_kasir
             JOIN shift s USING (id_shift)
             WHERE t.id_transaksi = ?',
            [$id]
        );

        if (!$t) {
            respond(['error' => 'Transaksi tidak ditemukan'], 404);
        }

        $t['detail_transaksi'] = rows(
            'SELECT d.*, l.nama_layanan
             FROM detail_transaksi d
             JOIN layanan l USING (id_layanan)
             WHERE d.id_transaksi = ?
             ORDER BY d.id_detail',
            [$id]
        );

        respond(['data' => $t]);
    }

    // Tambah transaksi oleh kasir

    if ($action === 'create') {
        method('POST');
        csrf();

        $u = actor();

        if (($u['role'] ?? '') !== 'kasir') {
            respond([
                'error' => 'Hanya kasir yang mencatat transaksi'
            ], 403);
        }


        $b = body();

        need($b, [
            'no_hp',
            'id_shift',
            'jenis_pembayaran',
            'detail_transaksi'
        ]);

        $noHp = strval_required($b['no_hp'], 20);

        $namaPelanggan = null;

        if (
            isset($b['nama_pelanggan'])
            && is_string($b['nama_pelanggan'])
            && trim($b['nama_pelanggan']) !== ''
        ) {
            $namaPelanggan = strval_required(
                $b['nama_pelanggan'],
                100
            );
        }

        if (!in_array(
            $b['jenis_pembayaran'],
            ['cash', 'qris'],
            true
        )) {
            respond([
                'error' => 'Pembayaran tidak valid'
            ], 422);
        }

        if (
            !is_array($b['detail_transaksi'])
            || count($b['detail_transaksi']) < 1
            || count($b['detail_transaksi']) > 30
        ) {
            respond([
                'error' => 'Detail harus 1–30 baris'
            ], 422);
        }

        $shift = integer($b['id_shift']);

        // Validasi shift sebelum transaksi database dimulai
        if (!one(
            'SELECT 1 FROM shift WHERE id_shift = ?',
            [$shift]
        )) {
            respond(['error' => 'Shift tidak ditemukan'], 422);
        }

        // Cari pelanggan berdasarkan nomor HP
        $pelanggan = one(
            'SELECT id_pelanggan
             FROM pelanggan
             WHERE no_hp = ?',
            [$noHp]
        );

        if (!$pelanggan && $namaPelanggan === null) {
            respond([
                'error' => 'Nama pelanggan wajib diisi untuk pelanggan baru'
            ], 422);
        }

        // Validasi detail dan hitung total dari harga database
        $lines = [];
        $total = 0;

        foreach ($b['detail_transaksi'] as $d) {
            if (!is_array($d)) {
                respond(['error' => 'Detail tidak valid'], 422);
            }

            need($d, ['id_layanan', 'jumlah_koin']);

            $lid = integer($d['id_layanan']);
            $coin = integer($d['jumlah_koin'], 0);

            $service = one(
                'SELECT *
                 FROM layanan
                 WHERE id_layanan = ?',
                [$lid]
            );

            if (!$service) {
                respond(['error' => 'Layanan tidak ditemukan'], 422);
            }

            $weight = isset($d['berat'])
                && $d['berat'] !== ''
                ? positive_weight($d['berat'])
                : null;

            if (
                $weight !== null
                && $service['berat_standar'] !== null
                && (float)$weight > (float)$service['berat_standar']
            ) {
                respond([
                    'error' => 'Berat per detail melebihi kapasitas layanan; pecah menjadi beberapa detail sesuai jumlah penggunaan mesin'
                ], 422);
            }

            // Satu baris detail dihitung sebagai satu layanan.
            $price = (int)$service['harga'];
            $subtotal = $price;
            $total += $subtotal;

            if ($total > 2147483647) {
                respond(['error' => 'Total melebihi batas'], 422);
            }

            $lines[] = [
                $lid,
                $weight,
                $coin,
                $price,
                $subtotal
            ];
        }

        // Koneksi PostgreSQL menggunakan pg_connect(), bukan PDO.
        $conn = getConnection();

        if (pg_query($conn, 'BEGIN') === false) {
            throw new RuntimeException(
                'Gagal memulai transaksi database'
            );
        }

        try {
            // Periksa kembali pelanggan di dalam transaksi database.
            $pelanggan = one(
                'SELECT id_pelanggan
                 FROM pelanggan
                 WHERE no_hp = ?',
                [$noHp]
            );

            if ($pelanggan) {
                $idPelanggan = (int)$pelanggan['id_pelanggan'];
            } else {
                $baru = one(
                    'INSERT INTO pelanggan (nama_pelanggan, no_hp)
                     VALUES (?, ?)
                     RETURNING id_pelanggan',
                    [$namaPelanggan, $noHp]
                );

                if (!$baru) {
                    throw new RuntimeException(
                        'Gagal membuat data pelanggan'
                    );
                }

                $idPelanggan = (int)$baru['id_pelanggan'];
            }

            // Simpan transaksi utama
            $r = one(
                'INSERT INTO transaksi (
                    id_kasir,
                    tanggal_transaksi,
                    id_pelanggan,
                    id_shift,
                    jenis_pembayaran,
                    total_bayar
                )
                VALUES (?, CURRENT_TIMESTAMP, ?, ?, ?, ?)
                RETURNING id_transaksi',
                [
                    (int)$u['id_pengguna'],
                    $idPelanggan,
                    $shift,
                    $b['jenis_pembayaran'],
                    $total
                ]
            );

            if (!$r) {
                throw new RuntimeException(
                    'Gagal menyimpan transaksi'
                );
            }

            $id = (int)$r['id_transaksi'];

            // Simpan semua detail transaksi
            foreach ($lines as $line) {
                execute_sql(
                    'INSERT INTO detail_transaksi (
                        id_transaksi,
                        id_layanan,
                        berat,
                        jumlah_koin,
                        harga_satuan,
                        subtotal
                    )
                    VALUES (?, ?, ?, ?, ?, ?)',
                    array_merge([$id], $line)
                );
            }

            if (pg_query($conn, 'COMMIT') === false) {
                throw new RuntimeException(
                    'Gagal menyelesaikan transaksi database'
                );
            }
        } catch (Throwable $e) {
            pg_query($conn, 'ROLLBACK');
            throw $e;
        }

        respond([
            'message' => 'Transaksi tersimpan',
            'id_transaksi' => $id,
            'id_pelanggan' => $idPelanggan,
            'total_bayar' => $total
        ], 201);
    }
}
