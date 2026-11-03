<?php
declare(strict_types=1);

if ($module === 'riwayat') {
    // Tracking publik pelanggan by no_hp (tanpa wajib login sesi)
    if ($action === 'tracking') {
        method('POST');
        $b = body();
        need($b, ['no_hp']);
        $phone = preg_replace('/[\s()-]+/', '', (string)$b['no_hp']);
        if (!preg_match('/^\+?[0-9]{9,15}$/', $phone)) {
            respond(['error' => 'Nomor WhatsApp tidak valid'], 422);
        }

        $altPhone = str_starts_with($phone, '0')
            ? ('62' . substr($phone, 1))
            : (str_starts_with($phone, '62') ? ('0' . substr($phone, 2)) : $phone);

        $transactions = rows(
            'SELECT t.id_transaksi, t.no_invoice, t.tanggal_transaksi,
                    t.total_bayar, t.jenis_pembayaran, t.sisa_bayar,
                    t.status_pembayaran, t.status_pengerjaan,
                    p.nama_pelanggan, p.no_hp
             FROM transaksi t
             JOIN pelanggan p USING (id_pelanggan)
             WHERE p.no_hp = ? OR p.no_hp = ?
             ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC
             LIMIT 50',
            [$phone, $altPhone]
        );

        respond(['data' => $transactions]);
    }

    // Riwayat pelanggan terautentikasi (berdasarkan sesi login)
    method('GET');
    $customerId = customer_actor();

    if ($action === 'list') {
        $transactions = rows(
            'SELECT t.id_transaksi, t.no_invoice, t.tanggal_transaksi,
                    t.total_bayar, t.jenis_pembayaran, t.sisa_bayar,
                    t.status_pembayaran, t.status_pengerjaan
             FROM transaksi t
             WHERE t.id_pelanggan = ?
             ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC
             LIMIT 100',
            [$customerId]
        );

        respond(['data' => $transactions]);
    }

    if ($action === 'detail') {
        $id = idparam();

        $transaction = one(
            'SELECT t.id_transaksi, t.no_invoice, t.tanggal_transaksi,
                    t.total_bayar, t.jenis_pembayaran, t.sisa_bayar,
                    t.status_pembayaran, t.status_pengerjaan
             FROM transaksi t
             WHERE t.id_transaksi = ? AND t.id_pelanggan = ?',
            [$id, $customerId]
        );

        if (!$transaction) {
            respond(['error' => 'Transaksi tidak ditemukan'], 404);
        }

        $transaction['detail_transaksi'] = rows(
            'SELECT d.id_detail, d.berat, d.harga_satuan,
                    d.subtotal, l.nama_layanan
             FROM detail_transaksi d
             JOIN layanan l USING (id_layanan)
             WHERE d.id_transaksi = ?
             ORDER BY d.id_detail',
            [$id]
        );

        respond(['data' => $transaction]);
    }

    respond(['error' => 'Action tidak ditemukan'], 404);
}
