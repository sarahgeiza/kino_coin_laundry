<?php
declare(strict_types=1);

if ($module === 'dashboard' || $module === 'rekap') {
    owner();
    method('GET');

    $start = (string) ($_GET['start'] ?? date('Y-m-01'));
    $end = (string) ($_GET['end'] ?? date('Y-m-d'));
    foreach ([$start, $end] as $value) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value)
            respond(['error' => 'Tanggal harus YYYY-MM-DD'], 422);
    }
    if ($start > $end)
        respond(['error' => 'Tanggal awal melebihi tanggal akhir'], 422);

    $where = ' WHERE t.tanggal_transaksi >= ?::date AND t.tanggal_transaksi < (?::date + INTERVAL \'1 day\')';
    $params = [$start, $end];
    foreach (['shift' => 't.id_shift', 'kasir' => 't.id_kasir', 'pembayaran' => 't.jenis_pembayaran'] as $key => $column) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $where .= ' AND ' . $column . ' = ?';
            $params[] = $key === 'pembayaran' ? strval_required($_GET[$key], 10) : integer($_GET[$key]);
        }
    }

    $base = ' FROM transaksi t
        LEFT JOIN (SELECT id_transaksi,SUM(COALESCE(berat,0)) AS berat_total
                   FROM detail_transaksi GROUP BY id_transaksi) d USING(id_transaksi) ';

    if ($module === 'dashboard' || $action === 'index' || $action === 'pemilik') {
        $data = one(
            'SELECT COUNT(*)::int AS jumlah_transaksi,
                    COALESCE(SUM(t.jumlah_dibayar),0)::bigint AS pendapatan,
                    COALESCE(SUM(d.berat_total),0)::numeric AS total_berat,
                    COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'cash\'),0)::bigint AS pendapatan_cash,
                    COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'qris\'),0)::bigint AS pendapatan_qris,
                    COALESCE(SUM(t.sisa_bayar),0)::bigint AS sisa_pembayaran' . $base . $where,
            $params
        );
        respond(['data' => $data]);
    }

    if ($action === 'layanan') {
        $data = rows(
            'SELECT l.id_layanan,l.nama_layanan,COUNT(DISTINCT t.id_transaksi)::int AS jumlah_transaksi,
                    COALESCE(SUM(d.berat),0)::numeric AS total_berat,
                    COALESCE(SUM(d.subtotal),0)::bigint AS pendapatan
             FROM transaksi t JOIN detail_transaksi d USING(id_transaksi)
             JOIN layanan l USING(id_layanan)' . $where . '
             GROUP BY l.id_layanan,l.nama_layanan ORDER BY pendapatan DESC',
            $params
        );
        respond(['data' => $data]);
    }

    if (in_array($action, ['shift', 'kasir', 'periode', 'pembayaran'], true)) {
        $group = match ($action) {
            'shift' => ['s.nama_shift', ' JOIN shift s ON s.id_shift=t.id_shift '],
            'kasir' => ['u.nama', ' JOIN pengguna u ON u.id_pengguna=t.id_kasir '],
            'periode' => ['t.tanggal_transaksi::date', ''],
            'pembayaran' => ['t.jenis_pembayaran', '']
        };
        $data = rows(
            'SELECT ' . $group[0] . ' AS kategori,COUNT(*)::int AS jumlah_transaksi,
                    COALESCE(SUM(t.jumlah_dibayar),0)::bigint AS pendapatan,
                    COALESCE(SUM(d.berat_total),0)::numeric AS total_berat,
                    COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'cash\'),0)::bigint AS pendapatan_cash,
                    COALESCE(SUM(t.jumlah_dibayar) FILTER (WHERE t.jenis_pembayaran = \'qris\'),0)::bigint AS pendapatan_qris' .
            $base . $group[1] . $where . ' GROUP BY ' . $group[0] . ' ORDER BY ' . $group[0],
            $params
        );
        respond(['data' => $data]);
    }


    respond(['error' => 'Action tidak ditemukan'], 404);
}
