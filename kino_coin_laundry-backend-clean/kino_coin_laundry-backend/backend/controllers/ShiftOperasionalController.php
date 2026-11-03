<?php
declare(strict_types=1);

if ($module === 'shift-operasional') {
        $u = actor();
    $role = (string)($u['role'] ?? '');

    // Log presensi untuk pemilik toko (admin/presensi.php)
    if ($action === 'logs' || ($action === 'list' && $role === 'pemilik')) {
        if ($role !== 'pemilik') {
            respond(['error' => 'Akses hanya untuk pemilik'], 403);
        }
        $logs = rows(
            'SELECT so.id_shift_operasional, so.mulai_pada, so.selesai_pada,
                    so.modal_awal, so.total_uang_fisik, so.selisih, so.status,
                    p.nama AS nama_kasir, s.nama_shift, s.jam_mulai, s.jam_selesai
             FROM shift_operasional so
             JOIN pengguna p ON so.id_kasir = p.id_pengguna
             JOIN shift s USING (id_shift)
             ORDER BY so.id_shift_operasional DESC
             LIMIT 100'
        );
        respond(['data' => $logs]);
    }

    if ($role !== 'kasir') {
        respond(['error' => 'Akses operasional kasir hanya untuk kasir'], 403);
    }

    $kasirId = (int)($u['id_pengguna'] ?? 0);

    // ==========================================
    // MELIHAT SHIFT YANG SEDANG AKTIF
    // ==========================================
    if ($action === 'current') {
        method('GET');

        $active = one(
            'SELECT so.*, s.nama_shift, s.jam_mulai, s.jam_selesai
             FROM shift_operasional so
             JOIN shift s USING (id_shift)
             WHERE so.id_kasir = ? AND so.status = ?
             ORDER BY so.id_shift_operasional DESC
             LIMIT 1',
            [$kasirId, 'aktif']
        );

        if (!$active) {
            respond(['data' => null]);
        }

        $summary = one(
            'SELECT
                COUNT(DISTINCT t.id_transaksi)::int AS total_transaksi,
                COALESCE(SUM(t.total_bayar), 0)::bigint AS total_pendapatan,
                COALESCE(
                    SUM(t.total_bayar) FILTER (
                        WHERE t.jenis_pembayaran = \'cash\'
                    ), 0
                )::bigint AS total_cash,
                COALESCE(
                    SUM(t.total_bayar) FILTER (
                        WHERE t.jenis_pembayaran = \'qris\'
                    ), 0
                )::bigint AS total_qris,
                COALESCE(SUM(d.total_berat), 0)::numeric AS total_berat
             FROM transaksi t
             LEFT JOIN (
                SELECT
                    id_transaksi,
                    SUM(COALESCE(berat, 0)) AS total_berat
                FROM detail_transaksi
                GROUP BY id_transaksi
             ) d USING (id_transaksi)
             WHERE t.id_shift_operasional = ?',
            [(int)$active['id_shift_operasional']]
        );

        respond([
            'data' => array_merge($active, $summary ?? [])
        ]);
    }

    // ==========================================
    // MEMBUKA SHIFT
    // ==========================================
    if ($action === 'open') {
        method('POST');
        csrf();

        $b = body();
        need($b, ['id_shift']);

        $shiftId = integer($b['id_shift']);

        if ($shiftId <= 0) {
            respond(['error' => 'ID shift tidak valid'], 422);
        }

        // Pastikan jenis shift tersedia
        $shift = one(
            'SELECT id_shift
             FROM shift
             WHERE id_shift = ?',
            [$shiftId]
        );

        if (!$shift) {
            respond(['error' => 'Jenis shift tidak ditemukan'], 404);
        }

        // Cek apakah kasir masih memiliki shift aktif
        $active = one(
            'SELECT id_shift_operasional
             FROM shift_operasional
             WHERE id_kasir = ? AND status = ?
             LIMIT 1',
            [$kasirId, 'aktif']
        );

        if ($active) {
            respond([
                'error' => 'Kamu masih memiliki shift aktif. Tutup shift sebelumnya terlebih dahulu.'
            ], 409);
        }

        // Simpan shift baru
        $result = one(
            'INSERT INTO shift_operasional
                (id_kasir, id_shift, status, mulai_pada)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP)
             RETURNING id_shift_operasional, mulai_pada, status',
            [$kasirId, $shiftId, 'aktif']
        );

        respond([
            'message' => 'Shift berhasil dibuka',
            'data' => $result
        ], 201);
    }

    // ==========================================
    // MENUTUP SHIFT
    // ==========================================
    if ($action === 'close') {
        method('POST');
        csrf();

        $b = body();
        need($b, ['total_fisik']);

        $physical = integer($b['total_fisik']);

        if ($physical < 0) {
            respond(['error' => 'Total uang fisik tidak valid'], 422);
        }

        $conn = getConnection();

        if (!pg_query($conn, 'BEGIN')) {
            throw new RuntimeException(
                'Tidak dapat memulai penutupan shift'
            );
        }

        try {
            // Ambil shift aktif dan kunci barisnya
            $active = one(
                'SELECT id_shift_operasional
                 FROM shift_operasional
                 WHERE id_kasir = ? AND status = ?
                 FOR UPDATE',
                [$kasirId, 'aktif']
            );

            if (!$active) {
                pg_query($conn, 'ROLLBACK');

                respond([
                    'error' => 'Tidak ada shift aktif untuk ditutup'
                ], 409);
            }

            $id = (int)$active['id_shift_operasional'];

            // Hitung total pembayaran Cash saja
            $summary = one(
                'SELECT
                    COALESCE(
                        SUM(total_bayar) FILTER (
                            WHERE jenis_pembayaran = \'cash\'
                        ), 0
                    )::bigint AS total_sistem
                 FROM transaksi
                 WHERE id_shift_operasional = ?',
                [$id]
            );

            $system = (int)($summary['total_sistem'] ?? 0);

            // Hitung selisih uang fisik dengan uang Cash menurut sistem
            $difference = $physical - $system;

            // Simpan hasil penutupan shift
            execute_sql(
                'UPDATE shift_operasional
                 SET status = ?,
                     ditutup_pada = CURRENT_TIMESTAMP,
                     total_sistem = ?,
                     total_fisik = ?,
                     selisih = ?,
                     catatan = ?
                 WHERE id_shift_operasional = ?',
                [
                    'ditutup',
                    $system,
                    $physical,
                    $difference,
                    isset($b['catatan'])
                        ? substr(trim((string)$b['catatan']), 0, 1000)
                        : null,
                    $id
                ]
            );

            if (!pg_query($conn, 'COMMIT')) {
                throw new RuntimeException(
                    'Gagal menyelesaikan penutupan shift'
                );
            }

            respond([
                'message' => 'Shift berhasil ditutup',
                'data' => [
                    'total_sistem' => $system,
                    'total_fisik' => $physical,
                    'selisih' => $difference
                ]
            ]);

        } catch (Throwable $e) {
            pg_query($conn, 'ROLLBACK');
            throw $e;
        }
    }

    respond(['error' => 'Action tidak ditemukan'], 404);
}
