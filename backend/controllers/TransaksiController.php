<?php
declare(strict_types=1);

if ($module === 'transaksi') {
    $u = actor();

    if ($action === 'list') {
        method('GET');

        $role = (string) ($u['role'] ?? '');
        if (!in_array($role, ['pemilik', 'kasir', 'pelanggan'], true)) {
            respond(['error' => 'Akses ditolak'], 403);
        }

        $start = (string) ($_GET['start'] ?? date('Y-m-01'));
        $end = (string) ($_GET['end'] ?? date('Y-m-d'));

        foreach ([$start, $end] as $val) {
            $d = DateTimeImmutable::createFromFormat('!Y-m-d', $val);
            if (!$d || $d->format('Y-m-d') !== $val) {
                respond(['error' => 'Tanggal harus YYYY-MM-DD'], 422);
            }
        }

        if ($start > $end) {
            respond(['error' => 'Tanggal awal melebihi tanggal akhir'], 422);
        }

        $page = max(1, isset($_GET['page']) ? integer($_GET['page']) : 1);
        $limit = min(100, max(1, isset($_GET['limit']) ? integer($_GET['limit']) : 20));
        $offset = ($page - 1) * $limit;
        $wantSummary = (int) ($_GET['summary'] ?? 0) === 1;

        $whereClauses = [];
        $params = [];

        if ($role === 'kasir') {
            if (isset($_GET['kasir']) && (int) $_GET['kasir'] !== (int) $u['id_pengguna']) {
                respond(['error' => 'Akses ditolak'], 403);
            }

            $active = one(
                'SELECT id_shift_operasional
                 FROM shift_operasional
                 WHERE id_kasir = ? AND status = ?',
                [(int) $u['id_pengguna'], 'aktif']
            );

            if (!$active) {
                $emptyResp = [
                    'data'  => [],
                    'page'  => $page,
                    'limit' => $limit,
                    'total' => 0,
                ];
                if ($wantSummary) {
                    $emptyResp['summary'] = [
                        'total_transaksi' => 0,
                        'total_omzet'     => 0,
                        'total_berat'     => 0,
                        'total_koin'      => 0,
                    ];
                }
                respond($emptyResp);
            }

            $whereClauses[] = 't.id_shift_operasional = ?';
            $params[] = (int) $active['id_shift_operasional'];
        } elseif ($role === 'pelanggan') {
            $whereClauses[] = 't.id_pelanggan = ?';
            $params[] = (int) $u['id_pengguna'];

            $whereClauses[] = 't.tanggal_transaksi >= ?::date';
            $params[] = $start;
            $whereClauses[] = 't.tanggal_transaksi < (?::date + INTERVAL \'1 day\')';
            $params[] = $end;
        } else {
            // pemilik
            $whereClauses[] = 't.tanggal_transaksi >= ?::date';
            $params[] = $start;
            $whereClauses[] = 't.tanggal_transaksi < (?::date + INTERVAL \'1 day\')';
            $params[] = $end;

            if (isset($_GET['kasir']) && $_GET['kasir'] !== '') {
                $whereClauses[] = 't.id_kasir = ?';
                $params[] = integer($_GET['kasir']);
            }

            if (isset($_GET['shift']) && $_GET['shift'] !== '') {
                $whereClauses[] = 't.id_shift = ?';
                $params[] = integer($_GET['shift']);
            }
        }

        $whereSql = $whereClauses !== [] ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

        // Count total
        $countRow = one(
            'SELECT COUNT(t.id_transaksi)::int AS total FROM transaksi t' . $whereSql,
            $params
        );
        $total = (int) ($countRow['total'] ?? 0);

        // Fetch data
        $dataParams = array_merge($params, [$limit, $offset]);
        $rows = rows(
            'SELECT t.id_transaksi,
                    t.no_invoice,
                    t.tanggal_transaksi,
                    p.nama_pelanggan,
                    p.no_hp,
                    u.nama AS nama_kasir,
                    s.nama_shift,
                    t.total_bayar,
                    t.jenis_pembayaran,
                    t.status_pembayaran,
                    t.status_pengerjaan,
                    COALESCE(dt.total_berat, 0)::numeric(10,2) AS total_berat,
                    COALESCE(dt.total_koin, 0)::int AS total_koin,
                    COALESCE(dt.layanan, \'\') AS layanan
             FROM transaksi t
             JOIN pelanggan p USING (id_pelanggan)
             JOIN pengguna u ON u.id_pengguna = t.id_kasir
             JOIN shift s USING (id_shift)
             LEFT JOIN (
                 SELECT d.id_transaksi,
                        SUM(COALESCE(d.berat, 0)) AS total_berat,
                        SUM(d.jumlah_koin) AS total_koin,
                        STRING_AGG(DISTINCT l.nama_layanan, \', \') AS layanan
                 FROM detail_transaksi d
                 JOIN layanan l USING (id_layanan)
                 GROUP BY d.id_transaksi
             ) dt ON dt.id_transaksi = t.id_transaksi' . $whereSql . '
             ORDER BY t.id_transaksi DESC
             LIMIT ? OFFSET ?',
            $dataParams
        );

        $data = array_map(static function (array $r): array {
            return [
                'id_transaksi'      => (int) $r['id_transaksi'],
                'no_invoice'        => (string) $r['no_invoice'],
                'tanggal_transaksi' => (string) $r['tanggal_transaksi'],
                'nama_pelanggan'    => (string) $r['nama_pelanggan'],
                'no_hp'             => (string) $r['no_hp'],
                'nama_kasir'        => (string) $r['nama_kasir'],
                'nama_shift'        => (string) $r['nama_shift'],
                'total_bayar'       => (int) $r['total_bayar'],
                'jenis_pembayaran'  => (string) $r['jenis_pembayaran'],
                'status_pembayaran' => (string) $r['status_pembayaran'],
                'status_pengerjaan' => (string) $r['status_pengerjaan'],
                'total_berat'       => (float) $r['total_berat'],
                'total_koin'        => (int) $r['total_koin'],
                'layanan'           => (string) $r['layanan'],
            ];
        }, $rows);

        $response = [
            'data'  => $data,
            'page'  => $page,
            'limit' => $limit,
            'total' => $total,
        ];

        if ($wantSummary) {
            $sumRow = one(
                'SELECT COUNT(DISTINCT t.id_transaksi)::int AS total_transaksi,
                        COALESCE(SUM(t.total_bayar), 0)::bigint AS total_omzet,
                        COALESCE(SUM(dt.total_berat), 0)::numeric(10,2) AS total_berat,
                        COALESCE(SUM(dt.total_koin), 0)::bigint AS total_koin
                 FROM transaksi t
                 LEFT JOIN (
                     SELECT id_transaksi,
                            SUM(COALESCE(berat, 0)) AS total_berat,
                            SUM(jumlah_koin) AS total_koin
                     FROM detail_transaksi
                     GROUP BY id_transaksi
                 ) dt ON dt.id_transaksi = t.id_transaksi' . $whereSql,
                $params
            );

            $response['summary'] = [
                'total_transaksi' => (int) ($sumRow['total_transaksi'] ?? 0),
                'total_omzet'     => (int) ($sumRow['total_omzet'] ?? 0),
                'total_berat'     => (float) ($sumRow['total_berat'] ?? 0),
                'total_koin'      => (int) ($sumRow['total_koin'] ?? 0),
            ];
        }

        respond($response);
    }

    if ($action === 'detail') {
        method('GET');

        $id = idparam();

        $t = one(
            'SELECT t.*, p.nama_pelanggan, p.no_hp,
                    u.nama AS nama_kasir, s.nama_shift
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

        if (
            ($u['role'] ?? '') === 'pelanggan'
            && (int) $t['id_pelanggan'] !== (int) $u['id_pengguna']
        ) {
            respond(['error' => 'Akses ditolak'], 403);
        }

        if (
            ($u['role'] ?? '') === 'kasir'
            && (int) $t['id_kasir'] !== (int) $u['id_pengguna']
        ) {
            respond(['error' => 'Akses ditolak'], 403);
        }

        if (!in_array($u['role'] ?? '', ['pemilik', 'kasir', 'pelanggan'], true)) {
            respond(['error' => 'Akses ditolak'], 403);
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

    if ($action === 'status') {
        method('POST');
        csrf();

        if (!in_array(($u['role'] ?? ''), ['kasir', 'pemilik'], true)) {
            respond(['error' => 'Akses ditolak'], 403);
        }

        $b = body();
        need($b, ['id_transaksi', 'status_pengerjaan']);

        $id = integer($b['id_transaksi']);
        $status = (string) $b['status_pengerjaan'];

        $allowed = ['antri', 'proses', 'siap_diambil', 'selesai', 'diambil'];

        if (!in_array($status, $allowed, true)) {
            respond(['error' => 'Status pengerjaan tidak valid'], 422);
        }

        $transaction = one(
            'SELECT id_kasir FROM transaksi WHERE id_transaksi = ?',
            [$id]
        );

        if (!$transaction) {
            respond(['error' => 'Transaksi tidak ditemukan'], 404);
        }

        if (
            ($u['role'] ?? '') === 'kasir'
            && (int) $transaction['id_kasir'] !== (int) $u['id_pengguna']
        ) {
            respond(['error' => 'Akses ditolak'], 403);
        }

        execute_sql(
            'UPDATE transaksi
             SET status_pengerjaan = ?
             WHERE id_transaksi = ?',
            [$status, $id]
        );

        respond(['message' => 'Status pengerjaan diperbarui']);
    }

    if ($action === 'create') {
        method('POST');
        csrf();

        if (($u['role'] ?? '') !== 'kasir') {
            respond(['error' => 'Hanya kasir yang dapat mencatat transaksi'], 403);
        }

        $b = body();
        need($b, ['nama_pelanggan', 'no_hp', 'detail_transaksi']);

        $name = strval_required($b['nama_pelanggan'], 100);
        $phone = preg_replace('/[\s()-]+/', '', strval_required($b['no_hp'], 20));

        if (!preg_match('/^\+?[0-9]{9,15}$/', $phone)) {
            respond(['error' => 'Nomor WhatsApp tidak valid'], 422);
        }

        if (
            !is_array($b['detail_transaksi'])
            || count($b['detail_transaksi']) < 1
            || count($b['detail_transaksi']) > 10
        ) {
            respond(['error' => 'Pilih minimal satu layanan'], 422);
        }

        $paymentType = strtolower(trim((string) ($b['jenis_pembayaran'] ?? 'cash')));

        if (!in_array($paymentType, ['cash', 'qris'], true)) {
            respond(['error' => 'Metode pembayaran harus Cash atau QRIS'], 422);
        }

        $conn = getConnection();

        if (!pg_query($conn, 'BEGIN')) {
            throw new RuntimeException('Gagal memulai transaksi database');
        }

        try {
            $active = one(
                'SELECT so.id_shift_operasional, so.id_shift
                 FROM shift_operasional so
                 WHERE so.id_kasir = ? AND so.status = ?
                 FOR UPDATE',
                [(int) $u['id_pengguna'], 'aktif']
            );

            if (!$active) {
                pg_query($conn, 'ROLLBACK');
                respond([
                    'error' => 'Buka shift terlebih dahulu sebelum membuat transaksi'
                ], 409);
            }

            $total = 0;
            $totalMachineCoins = 0;
            $details = [];

            foreach ($b['detail_transaksi'] as $item) {
                if (!is_array($item)) {
                    respond(['error' => 'Detail layanan tidak valid'], 422);
                }

                need($item, ['id_layanan']);

                $serviceId = integer($item['id_layanan']);

                $service = one(
                    'SELECT id_layanan, nama_layanan, harga,
                            berat_standar, jumlah_koin, aktif
                     FROM layanan
                     WHERE id_layanan = ?
                     FOR SHARE',
                    [$serviceId]
                );

                if (
                    !$service
                    || !in_array($service['aktif'], [true, 't', '1', 1], true)
                ) {
                    respond(['error' => 'Layanan tidak tersedia'], 422);
                }

                $price = (int) $service['harga'];
                $coinsPerProcess = (int) $service['jumlah_koin'];
                $standardWeight = (float) ($service['berat_standar'] ?? 0);

                if ($price <= 0 || $coinsPerProcess < 0) {
                    respond(['error' => 'Konfigurasi harga atau koin layanan tidak valid'], 422);
                }

                if ($coinsPerProcess > 0 || $standardWeight > 0) {
                    need($item, ['berat']);
                    $weight = (float) positive_weight($item['berat']);
                    $processWeight = $standardWeight > 0 ? $standardWeight : 7.5;
                    $processCount = (int)ceil($weight / $processWeight);
                    $quantity = $processCount;
                    $machineCoins = $processCount * $coinsPerProcess;
                    $subtotal = $processCount * $price;
                } elseif (stripos((string) $service['nama_layanan'], 'lipat') !== false) {
                    need($item, ['berat']);
                    $weight = (float) positive_weight($item['berat']);
                    $quantity = 1;
                    $machineCoins = 0;
                    $subtotal = (int) round($weight * $price);
                } else {
                    need($item, ['jumlah']);
                    $weight = null;
                    $quantity = integer($item['jumlah']);
                    $machineCoins = 0;
                    $subtotal = $quantity * $price;
                }

                $total += $subtotal;
                $totalMachineCoins += $machineCoins;

                $details[] = [
                    'id_layanan' => $serviceId,
                    'berat' => $weight,
                    'jumlah' => $quantity,
                    'jumlah_koin' => $machineCoins,
                    'harga_satuan' => $price,
                    'subtotal' => $subtotal
                ];
            }

            need($b, ['jumlah_dibayar']);
            $paidAmount = integer($b['jumlah_dibayar'], 0);
            if ($paidAmount < $total) {
                respond(['error' => 'Pembayaran kurang. Transaksi hanya dapat disimpan setelah tagihan lunas.'], 422);
            }
            if ($paymentType === 'qris' && $paidAmount !== $total) {
                respond(['error' => 'Pembayaran QRIS harus sama persis dengan total transaksi'], 422);
            }
            $remaining = max(0, $total - $paidAmount);
            $change = $paymentType === 'cash'
                ? max(0, $paidAmount - $total)
                : 0;
            $paymentStatus = $paidAmount >= $total
                ? 'lunas'
                : ($paidAmount > 0 ? 'sebagian' : 'belum_lunas');

            $customer = one(
                'SELECT id_pelanggan
                 FROM pelanggan
                 WHERE no_hp = ?
                 FOR UPDATE',
                [$phone]
            );

            if ($customer) {
                $customerId = (int) $customer['id_pelanggan'];

                execute_sql(
                    'UPDATE pelanggan
                     SET nama_pelanggan = ?
                     WHERE id_pelanggan = ?',
                    [$name, $customerId]
                );
            } else {
                $customer = one(
                    "WITH next_id AS (
                        SELECT nextval(pg_get_serial_sequence('pelanggan', 'id_pelanggan')) AS id
                     )
                     INSERT INTO pelanggan (id_pelanggan, nama_pelanggan, no_hp, kode_kartu)
                     SELECT id, ?, ?, 'KC-P-' || LPAD(id::text, 4, '0')
                     FROM next_id
                     RETURNING id_pelanggan",
                    [$name, $phone]
                );

                $customerId = (int) $customer['id_pelanggan'];
            }

            $invoice = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

            $header = one(
                'INSERT INTO transaksi (
                    id_kasir, tanggal_transaksi, id_pelanggan, id_shift,
                    jenis_pembayaran, total_bayar, no_invoice, sisa_bayar,
                    status_pembayaran, status_pengerjaan, id_shift_operasional,
                    jumlah_dibayar, uang_kembalian
                 )
                 VALUES (?, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 RETURNING id_transaksi, no_invoice',
                [
                    (int) $u['id_pengguna'],
                    $customerId,
                    (int) $active['id_shift'],
                    $paymentType,
                    $total,
                    $invoice,
                    $remaining,
                    $paymentStatus,
                    'antri',
                    (int) $active['id_shift_operasional'],
                    $paidAmount,
                    $change
                ]
            );

            $transactionId = (int) $header['id_transaksi'];

            foreach ($details as $detail) {
                execute_sql(
                    'INSERT INTO detail_transaksi (
                        id_transaksi, id_layanan, berat, jumlah,
                        jumlah_koin, harga_satuan, subtotal
                     )
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $transactionId,
                        $detail['id_layanan'],
                        $detail['berat'],
                        $detail['jumlah'],
                        $detail['jumlah_koin'],
                        $detail['harga_satuan'],
                        $detail['subtotal']
                    ]
                );
            }

            if (!pg_query($conn, 'COMMIT')) {
                throw new RuntimeException('Gagal menyelesaikan transaksi database');
            }

            respond([
                'message' => 'Transaksi berhasil disimpan',
                'data' => [
                    'id_transaksi' => $transactionId,
                    'no_invoice' => $invoice,
                    'total_bayar' => $total,
                    'jenis_pembayaran' => $paymentType,
                    'jumlah_dibayar' => $paidAmount,
                    'sisa_bayar' => $remaining,
                    'uang_kembalian' => $change,
                    'status_pembayaran' => $paymentStatus,
                    'total_koin_mesin' => $totalMachineCoins
                ]
            ], 201);

        } catch (Throwable $e) {
            pg_query($conn, 'ROLLBACK');
            throw $e;
        }
    }

    respond(['error' => 'Action tidak ditemukan'], 404);
}
