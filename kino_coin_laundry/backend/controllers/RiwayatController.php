<?php
declare(strict_types=1);

// Riwayat hanya dapat dibaca oleh pelanggan yang sedang login.
// Tidak menerima nomor HP sebagai identitas karena itu memungkinkan enumerasi data.
if ($module === 'riwayat') {
    method('GET');
    $customerId = customer_actor();

    if ($action === 'list') {
        $transactions = rows(
            'SELECT t.id_transaksi,t.no_invoice,t.tanggal_transaksi,t.total_bayar,
                    t.jenis_pembayaran,t.jumlah_dibayar,t.sisa_bayar,t.status_pembayaran,t.status_pengerjaan
             FROM transaksi t
             WHERE t.id_pelanggan = ?
             ORDER BY t.tanggal_transaksi DESC,t.id_transaksi DESC
             LIMIT 100',
            [$customerId]
        );
        respond(['data' => $transactions]);
    }

    if ($action === 'detail') {
        $id = idparam();
        $transaction = one(
            'SELECT t.id_transaksi,t.no_invoice,t.tanggal_transaksi,t.total_bayar,
                    t.jenis_pembayaran,t.jumlah_dibayar,t.sisa_bayar,t.status_pembayaran,t.status_pengerjaan
             FROM transaksi t
             WHERE t.id_transaksi = ? AND t.id_pelanggan = ?',
            [$id,$customerId]
        );
        if (!$transaction) respond(['error'=>'Transaksi tidak ditemukan'],404);
        $transaction['detail_transaksi'] = rows(
            'SELECT d.id_detail,d.berat,d.harga_satuan,d.subtotal,l.nama_layanan
             FROM detail_transaksi d JOIN layanan l USING (id_layanan)
             WHERE d.id_transaksi = ? ORDER BY d.id_detail',
            [$id]
        );
        respond(['data'=>$transaction]);
    }

    respond(['error'=>'Action tidak ditemukan'],404);
}
