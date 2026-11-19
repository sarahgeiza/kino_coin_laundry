<?php
declare(strict_types=1);

if ($module === 'jadwal-shift') {

    if ($action === 'list') {
        method('GET');
        $u = actor();

        $tanggal = (string) ($_GET['tanggal'] ?? date('Y-m-d'));
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
        if (!$d || $d->format('Y-m-d') !== $tanggal) {
            respond(['error' => 'Tanggal harus YYYY-MM-DD'], 422);
        }

        $countRow = one(
            'SELECT COUNT(*)::int AS cnt FROM jadwal_shift WHERE tanggal = ?',
            [$tanggal]
        );
        $configured = ((int) ($countRow['cnt'] ?? 0)) > 0;

        $rows = rows(
            "SELECT j.id_jadwal,
                    j.tanggal::text AS tanggal,
                    j.id_shift,
                    s.nama_shift,
                    to_char(s.jam_mulai, 'HH24:MI') AS jam_mulai,
                    to_char(s.jam_selesai, 'HH24:MI') AS jam_selesai,
                    j.id_kasir,
                    p.nama AS nama_kasir,
                    j.status
             FROM jadwal_shift j
             JOIN shift s USING (id_shift)
             JOIN pengguna p ON p.id_pengguna = j.id_kasir
             WHERE j.tanggal = ?
               AND j.status <> 'dibatalkan'
             ORDER BY s.jam_mulai, s.id_shift, p.nama",
            [$tanggal]
        );

        $data = array_map(static function (array $r): array {
            return [
                'id_jadwal'   => (int) $r['id_jadwal'],
                'tanggal'     => (string) $r['tanggal'],
                'id_shift'    => (int) $r['id_shift'],
                'nama_shift'  => (string) $r['nama_shift'],
                'jam_mulai'   => (string) $r['jam_mulai'],
                'jam_selesai' => (string) $r['jam_selesai'],
                'id_kasir'    => (int) $r['id_kasir'],
                'nama_kasir'  => (string) $r['nama_kasir'],
                'status'      => (string) $r['status'],
            ];
        }, $rows);

        respond([
            'data'       => $data,
            'configured' => $configured,
        ]);
    }

    if ($action === 'save') {
        method('POST');
        csrf();
        $u = owner();

        $b = body();
        need($b, ['tanggal', 'id_shift', 'kasir_ids']);

        $tanggal = (string) $b['tanggal'];
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
        if (!$d || $d->format('Y-m-d') !== $tanggal) {
            respond(['error' => 'Tanggal harus YYYY-MM-DD'], 422);
        }

        $rawShiftId = integer($b['id_shift']);
        if (!is_array($b['kasir_ids'])) {
            respond(['error' => 'kasir_ids harus berupa array'], 422);
        }
        $kasirIds = array_values(array_unique(array_filter(array_map('intval', $b['kasir_ids']))));

        // Cari shift dan canonical ID (ID terkecil untuk nama shift yang sama)
        $shiftRow = one(
            'SELECT id_shift, nama_shift FROM shift WHERE id_shift = ?',
            [$rawShiftId]
        );
        if (!$shiftRow) {
            respond(['error' => 'Shift tidak ditemukan'], 422);
        }

        $canonShift = one(
            "SELECT MIN(s2.id_shift) AS id
             FROM shift s1
             JOIN shift s2 ON LOWER(TRIM(REGEXP_REPLACE(s2.nama_shift, '^shift\\s+', '', 'i'))) = LOWER(TRIM(REGEXP_REPLACE(s1.nama_shift, '^shift\\s+', '', 'i')))
             WHERE s1.id_shift = ?",
            [$rawShiftId]
        );
        $shiftId = (int) ($canonShift['id'] ?? $rawShiftId);

        // Validasi bahwa semua kasir_ids ada di tabel pengguna dengan role = 'kasir'
        foreach ($kasirIds as $kid) {
            $kasir = one(
                "SELECT id_pengguna, nama FROM pengguna WHERE id_pengguna = ? AND role = 'kasir'",
                [$kid]
            );
            if (!$kasir) {
                respond(['error' => 'Kasir tidak ditemukan'], 422);
            }
        }

        // Cek konflik: apakah ada kasir yang sudah dijadwalkan di shift lain pada tanggal yang sama?
        if (!empty($kasirIds)) {
            $placeholders = implode(',', array_map('intval', $kasirIds));
            $conflict = one(
                "SELECT j.id_kasir, p.nama AS nama_kasir
                 FROM jadwal_shift j
                 JOIN pengguna p ON p.id_pengguna = j.id_kasir
                 WHERE j.tanggal = ?
                   AND j.id_shift <> ?
                   AND j.id_kasir IN ($placeholders)
                   AND j.status <> 'dibatalkan'
                 LIMIT 1",
                [$tanggal, $shiftId]
            );
            if ($conflict) {
                respond(['error' => 'Kasir sudah dijadwalkan di shift lain pada tanggal ini'], 409);
            }
        }

        $conn = getConnection();
        if (!pg_query($conn, 'BEGIN')) {
            throw new RuntimeException('Gagal memulai transaksi database');
        }

        try {
            // 1. Kasir yang sebelumnya dijadwalkan di shift ini pada tanggal ini tapi tidak ada di kasir_ids -> status = 'dibatalkan'
            if (empty($kasirIds)) {
                execute_sql(
                    "UPDATE jadwal_shift SET status = 'dibatalkan' WHERE id_shift = ? AND tanggal = ?",
                    [$shiftId, $tanggal]
                );
            } else {
                $placeholders = implode(',', array_map('intval', $kasirIds));
                execute_sql(
                    "UPDATE jadwal_shift SET status = 'dibatalkan'
                     WHERE id_shift = ?
                       AND tanggal = ?
                       AND id_kasir NOT IN ($placeholders)",
                    [$shiftId, $tanggal]
                );
            }

            // 2. Kasir yang ada di kasir_ids -> status = 'dijadwalkan', dibuat_oleh = actor id
            $actorId = (int) $u['id_pengguna'];
            foreach ($kasirIds as $kid) {
                execute_sql(
                    "INSERT INTO jadwal_shift (id_shift, id_kasir, tanggal, status, dibuat_oleh)
                     VALUES (?, ?, ?, 'dijadwalkan', ?)
                     ON CONFLICT (id_kasir, tanggal)
                     DO UPDATE SET id_shift = EXCLUDED.id_shift,
                                   status = 'dijadwalkan',
                                   dibuat_oleh = EXCLUDED.dibuat_oleh",
                    [$shiftId, $kid, $tanggal, $actorId]
                );
            }

            if (!pg_query($conn, 'COMMIT')) {
                throw new RuntimeException('Gagal menyimpan jadwal shift');
            }

            // Ambil semua kasir pada shift ini di tanggal ini untuk response
            $kasirRows = rows(
                "SELECT j.id_kasir, p.nama AS nama_kasir, j.status
                 FROM jadwal_shift j
                 JOIN pengguna p ON p.id_pengguna = j.id_kasir
                 WHERE j.id_shift = ? AND j.tanggal = ?
                 ORDER BY p.nama",
                [$shiftId, $tanggal]
            );

            $kasirList = array_map(static function (array $kr): array {
                return [
                    'id_kasir'   => (int) $kr['id_kasir'],
                    'nama_kasir' => (string) $kr['nama_kasir'],
                    'status'     => (string) $kr['status'],
                ];
            }, $kasirRows);

            respond([
                'message' => 'Roster shift disimpan',
                'data'    => [
                    'tanggal'  => $tanggal,
                    'id_shift' => $shiftId,
                    'kasir'    => $kasirList,
                ],
            ]);
        } catch (Throwable $e) {
            pg_query($conn, 'ROLLBACK');
            throw $e;
        }
    }

    if ($action === 'cancel') {
        method('POST');
        csrf();
        owner();

        $b = body();
        need($b, ['id_jadwal']);
        $idJadwal = integer($b['id_jadwal']);

        $jadwal = one(
            'SELECT id_jadwal FROM jadwal_shift WHERE id_jadwal = ?',
            [$idJadwal]
        );
        if (!$jadwal) {
            respond(['error' => 'Jadwal tidak ditemukan'], 404);
        }

        execute_sql(
            "UPDATE jadwal_shift SET status = 'dibatalkan' WHERE id_jadwal = ?",
            [$idJadwal]
        );

        respond(['message' => 'Jadwal dibatalkan']);
    }

    respond(['error' => 'Action tidak ditemukan'], 404);
}
