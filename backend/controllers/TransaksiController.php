<?php
declare(strict_types=1);

if ($module === 'transaksi') {
    $u = actor();

    if ($action === 'list') {
        method('GET');

        $page = max(1, integer($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $where = '';
        $params = [];

        if (($u['role'] ?? '') === 'kasir') {
            $active = one(
                'SELECT id_shift_operasional
                 FROM shift_operasional
                 WHERE id_kasir = ? AND status = ?',
                [(int) $u['id_pengguna'], 'aktif']
            );

            if (!$active) {
                respond(['data' => [], 'page' => $page, 'total' => 0]);
            }

            $where = ' WHERE t.id_shift_operasional = ?';
            $params[] = (int) $active['id_shift_operasional'];
        } elseif (($u['role'] ?? '') === 'pelanggan') {
            $where = ' WHERE t.id_pelanggan = ?';
            $params[] = (int) $u['id_pengguna'];
        } elseif (($u['role'] ?? '') !== 'pemilik') {
            respond(['error' => 'Akses ditolak'], 403);
        }

        $params[] = $limit;
        $params[] = $offset;

        $data = rows(
            'SELECT t.id_transaksi, t.no_invoice, t.tanggal_transaksi,
                    t.total_bayar, t.jenis_pembayaran, t.sisa_bayar,
                    t.status_pembayaran, t.status_pengerjaan,
                    p.nama_pelanggan, u.nama AS nama_kasir, s.nama_shift
             FROM transaksi t
             JOIN pelanggan p USING (id_pelanggan)
             JOIN pengguna u ON u.id_pengguna = t.id_kasir
             JOIN shift s USING (id_shift)' . $where . '
             ORDER BY t.id_transaksi DESC LIMIT ? OFFSET ?',
            $params
        );

        respond(['data' => $data, 'page' => $page]);
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

                need($item, ['id_layanan', 'berat']);

                $serviceId = integer($item['id_layanan']);
                $weight = (float) positive_weight($item['berat']);

                if ($weight <= 0) {
                    respond(['error' => 'Berat cucian harus lebih dari 0 kg'], 422);
                }

                $service = one(
                    'SELECT id_layanan, nama_layanan, harga,
                            jumlah_koin, aktif
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

                if ($price <= 0 || $coinsPerProcess < 0) {
                    respond(['error' => 'Konfigurasi harga atau koin layanan tidak valid'], 422);
                }

                // Satu proses mesin menampung maksimal 7,5 kg.
                $processCount = (int) ceil($weight / 7.5);

                // Harga dan koin dihitung berdasarkan jumlah proses.
                $subtotal = $processCount * $price;
                $machineCoins = $processCount * $coinsPerProcess;

                $total += $subtotal;
                $totalMachineCoins += $machineCoins;

                $details[] = [
                    $serviceId,
                    $weight,
                    $machineCoins,
                    $price,
                    $subtotal
                ];
            }

            // Pembayaran transaksi dilakukan penuh melalui Cash atau QRIS.
            $paidAmount = $total;
            $remaining = 0;
            $paymentStatus = 'lunas';

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
                    'INSERT INTO pelanggan (nama_pelanggan, no_hp)
                     VALUES (?, ?)
                     RETURNING id_pelanggan',
                    [$name, $phone]
                );

                $customerId = (int) $customer['id_pelanggan'];
            }

            $invoice = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

            $header = one(
                'INSERT INTO transaksi (
                    id_kasir, tanggal_transaksi, id_pelanggan, id_shift,
                    jenis_pembayaran, total_bayar, no_invoice, sisa_bayar,
                    status_pembayaran, status_pengerjaan, id_shift_operasional
                 )
                 VALUES (?, CURRENT_TIMESTAMP, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 RETURNING id_transaksi, no_invoice',
                [
                    (int) $u['id_pengguna'],
                    $customerId,
                    (int) $active['id_shift'],
                    $paymentType,
                    $paidAmount,
                    $invoice,
                    $remaining,
                    $paymentStatus,
                    'antri',
                    (int) $active['id_shift_operasional']
                ]
            );

            $transactionId = (int) $header['id_transaksi'];

            foreach ($details as [$serviceId, $weight, $machineCoins, $price, $subtotal]) {
                execute_sql(
                    'INSERT INTO detail_transaksi (
                        id_transaksi, id_layanan, berat,
                        jumlah_koin, harga_satuan, subtotal
                     )
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        $transactionId,
                        $serviceId,
                        $weight,
                        $machineCoins,
                        $price,
                        $subtotal
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
                    'sisa_bayar' => $remaining,
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
