<?php
declare(strict_types=1);

if ($module === 'shift-operasional') {
    $u = actor();
    if (($u['role'] ?? '') !== 'kasir') respond(['error' => 'Akses hanya untuk kasir'], 403);

    if ($action === 'current') {
        method('GET');
        $active = one(
            'SELECT so.*, s.nama_shift, s.jam_mulai, s.jam_selesai
             FROM shift_operasional so
             JOIN shift s USING (id_shift)
             WHERE so.id_kasir = ? AND so.status = ?',
            [(int)$u['id_pengguna'], 'aktif']
        );
        if (!$active) respond(['data' => null]);
        $summary = one(
            'SELECT COUNT(*)::int AS total_transaksi,
                    COALESCE(SUM(t.jumlah_dibayar),0)::bigint AS total_pendapatan,
                    COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'cash\'),0)::bigint AS total_cash,
                    COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'qris\'),0)::bigint AS total_qris,
                    COALESCE(SUM(d.total_berat),0)::numeric AS total_berat
             FROM transaksi t
             LEFT JOIN (
                SELECT id_transaksi, SUM(COALESCE(berat,0)) AS total_berat
                FROM detail_transaksi GROUP BY id_transaksi
             ) d USING (id_transaksi)
             WHERE t.id_shift_operasional = ?',
            [(int)$active['id_shift_operasional']]
        );
        respond(['data' => array_merge($active, $summary ?? [])]);
    }

    if ($action === 'open') {
        method('POST');
        csrf();
        $b = body();
        need($b, ['id_shift']);
        $shiftId = integer($b['id_shift']);
        if (!one('SELECT id_shift FROM shift WHERE id_shift = ?', [$shiftId])) {
            respond(['error' => 'Jenis shift tidak ditemukan'], 404);
        }
        $result = one(
            'INSERT INTO shift_operasional (id_kasir, id_shift)
             VALUES (?, ?) RETURNING id_shift_operasional, mulai_pada',
            [(int)$u['id_pengguna'], $shiftId]
        );
        respond(['message' => 'Shift berhasil dibuka', 'data' => $result], 201);
    }

    if ($action === 'close') {
        method('POST');
        csrf();
        $b = body();
        need($b, ['total_fisik']);
        $physical = integer($b['total_fisik'], 0);
        $conn = getConnection();
        if (!pg_query($conn, 'BEGIN')) throw new RuntimeException('Tidak dapat memulai penutupan shift');
        try {
            $active = one(
                'SELECT id_shift_operasional FROM shift_operasional
                 WHERE id_kasir = ? AND status = ? FOR UPDATE',
                [(int)$u['id_pengguna'], 'aktif']
            );
            if (!$active) respond(['error' => 'Tidak ada shift aktif'], 409);
            $id = (int)$active['id_shift_operasional'];
            $summary = one(
                'SELECT COALESCE(SUM(jumlah_dibayar) FILTER (WHERE jenis_pembayaran = \'cash\'),0)::bigint AS total_sistem
                 FROM transaksi WHERE id_shift_operasional = ?',
                [$id]
            );
            $system = (int)($summary['total_sistem'] ?? 0);
            $difference = $physical - $system;
            execute_sql(
                'UPDATE shift_operasional
                 SET status = ?, ditutup_pada = CURRENT_TIMESTAMP,
                     total_sistem = ?, total_fisik = ?, selisih = ?, catatan = ?
                 WHERE id_shift_operasional = ?',
                ['ditutup', $system, $physical, $difference,
                 isset($b['catatan']) ? substr(trim((string)$b['catatan']), 0, 1000) : null, $id]
            );
            if (!pg_query($conn, 'COMMIT')) throw new RuntimeException('Gagal menyelesaikan penutupan shift');
            respond(['message' => 'Shift berhasil ditutup', 'data' => [
                'total_sistem' => $system, 'total_fisik' => $physical, 'selisih' => $difference
            ]]);
        } catch (Throwable $e) {
            pg_query($conn, 'ROLLBACK');
            throw $e;
        }
    }

    respond(['error' => 'Action tidak ditemukan'], 404);
}
