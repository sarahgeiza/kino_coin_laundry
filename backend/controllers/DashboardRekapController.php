<?php
declare(strict_types=1);

if ($module === 'dashboard' || $module === 'rekap') {

    method('GET');

    $user = actor();
    $role = strtolower(trim((string) ($user['role'] ?? '')));
    $userId = (int) ($user['id_pengguna'] ?? 0);

    $rekapActions = [
        'layanan',
        'koin',
        'shift',
        'kasir',
        'periode',
        'pembayaran'
    ];

    if ($module === 'dashboard') {

        if ($action === 'kasir') {
            if ($role !== 'kasir') {
                respond(['error' => 'Akses dashboard kasir ditolak'], 403);
            }

            if ($userId <= 0) {
                respond(['error' => 'ID kasir tidak ditemukan'], 401);
            }

        } elseif (in_array($action, ['index', 'pemilik'], true)) {
            if ($role !== 'pemilik') {
                respond(['error' => 'Akses dashboard pemilik ditolak'], 403);
            }

        } else {
            respond(['error' => 'Action dashboard tidak valid'], 404);
        }

    } else {

        if (!in_array($role, ['pemilik', 'kasir'], true)) {
            respond(['error' => 'Akses rekap ditolak'], 403);
        }

        if (!in_array($action, $rekapActions, true)) {
            respond(['error' => 'Action rekap tidak ditemukan'], 404);
        }
    }

    $start = (string) ($_GET['start'] ?? date('Y-m-01'));
    $end = (string) ($_GET['end'] ?? date('Y-m-d'));

    foreach ([$start, $end] as $value) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (!$date || $date->format('Y-m-d') !== $value) {
            respond(['error' => 'Tanggal harus YYYY-MM-DD'], 422);
        }
    }

    if ($start > $end) {
        respond(['error' => 'Tanggal awal melebihi tanggal akhir'], 422);
    }

    $where = ' WHERE t.tanggal_transaksi >= ?::date
               AND t.tanggal_transaksi < (?::date + INTERVAL \'1 day\')';

    $params = [$start, $end];

    if ($module === 'dashboard' && $action === 'kasir') {
        $where .= ' AND t.id_kasir = ?';
        $params[] = $userId;
    }

    $filters = [
        'shift' => 't.id_shift',
        'kasir' => 't.id_kasir',
        'pembayaran' => 't.jenis_pembayaran'
    ];

    foreach ($filters as $key => $column) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $where .= ' AND ' . $column . ' = ?';

            if ($key === 'pembayaran') {
                $payment = strtolower(strval_required($_GET[$key], 10));

                if (!in_array($payment, ['cash', 'qris'], true)) {
                    respond(['error' => 'Jenis pembayaran tidak valid'], 422);
                }

                $params[] = $payment;
            } else {
                $params[] = integer($_GET[$key]);
            }
        }
    }

    $base = '
        FROM transaksi t
        LEFT JOIN (
            SELECT
                id_transaksi,
                SUM(COALESCE(berat, 0)) AS berat_total,
                SUM(COALESCE(jumlah_koin, 0)) AS koin_total
            FROM detail_transaksi
            GROUP BY id_transaksi
        ) d ON d.id_transaksi = t.id_transaksi
    ';

    // DASHBOARD
    if ($module === 'dashboard') {
        $data = one(
            'SELECT
                COUNT(*)::int AS jumlah_transaksi,
                COALESCE(SUM(t.total_bayar), 0)::bigint AS pendapatan,
                COALESCE(SUM(d.berat_total), 0)::numeric AS total_berat,
                COALESCE(SUM(d.koin_total), 0)::bigint AS total_koin_mesin,
                COALESCE(SUM(t.sisa_bayar), 0)::bigint AS sisa_pembayaran
             ' . $base . $where,
            $params
        );

        respond(['data' => $data]);
    }

    // REKAP LAYANAN
    if ($action === 'layanan') {
        $data = rows(
            'SELECT
                l.id_layanan,
                l.nama_layanan,
                COUNT(DISTINCT t.id_transaksi)::int AS jumlah_transaksi,
                COALESCE(SUM(d.berat), 0)::numeric AS total_berat,
                COALESCE(SUM(d.jumlah_koin), 0)::bigint AS total_koin_mesin,
                COALESCE(SUM(d.subtotal), 0)::bigint AS pendapatan
             FROM transaksi t
             JOIN detail_transaksi d USING (id_transaksi)
             JOIN layanan l USING (id_layanan)
             ' . $where . '
             GROUP BY l.id_layanan, l.nama_layanan
             ORDER BY pendapatan DESC',
            $params
        );

        respond(['data' => $data]);
    }

    // REKAP KOIN MESIN
    if ($action === 'koin') {
        $data = rows(
            'SELECT
                l.id_layanan,
                l.nama_layanan,
                COUNT(DISTINCT t.id_transaksi)::int AS jumlah_transaksi,
                COALESCE(SUM(d.jumlah_koin), 0)::bigint AS jumlah_koin_mesin
             FROM transaksi t
             JOIN detail_transaksi d USING (id_transaksi)
             JOIN layanan l USING (id_layanan)
             ' . $where . '
             GROUP BY l.id_layanan, l.nama_layanan
             ORDER BY jumlah_koin_mesin DESC',
            $params
        );

        $total = one(
            'SELECT COALESCE(SUM(d.jumlah_koin), 0)::bigint AS total_koin_mesin
             FROM transaksi t
             JOIN detail_transaksi d USING (id_transaksi)
             ' . $where,
            $params
        );

        respond([
            'data' => [
                'total_koin_mesin' => (int) ($total['total_koin_mesin'] ?? 0),
                'rincian_layanan' => $data
            ]
        ]);
    }

    // REKAP SHIFT, KASIR, PERIODE, PEMBAYARAN
    if (in_array($action, ['shift', 'kasir', 'periode', 'pembayaran'], true)) {

        $group = match ($action) {
            'shift' => [
                's.nama_shift',
                ' JOIN shift s ON s.id_shift = t.id_shift '
            ],
            'kasir' => [
                'u.nama',
                ' JOIN pengguna u ON u.id_pengguna = t.id_kasir '
            ],
            'periode' => [
                't.tanggal_transaksi::date',
                ''
            ],
            'pembayaran' => [
                't.jenis_pembayaran',
                ''
            ]
        };

        $data = rows(
            'SELECT
                ' . $group[0] . ' AS kategori,
                COUNT(*)::int AS jumlah_transaksi,
                COALESCE(SUM(t.total_bayar), 0)::bigint AS pendapatan,
                COALESCE(SUM(d.berat_total), 0)::numeric AS total_berat,
                COALESCE(SUM(d.koin_total), 0)::bigint AS total_koin_mesin
             ' . $base . $group[1] . $where . '
             GROUP BY ' . $group[0] . '
             ORDER BY ' . $group[0],
            $params
        );

        respond(['data' => $data]);
    }

    respond(['error' => 'Action tidak ditemukan'], 404);
}
