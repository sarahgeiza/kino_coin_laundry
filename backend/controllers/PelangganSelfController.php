<?php
declare(strict_types=1);

if ($module === 'pelanggan-self') {
    method('GET');
    $customerId = customer_actor();

    if ($action === 'dashboard') {
        $customer = one(
            'SELECT id_pelanggan,nama_pelanggan,no_hp FROM pelanggan WHERE id_pelanggan = ?',
            [$customerId]
        );
        $services = rows(
            'SELECT id_layanan,nama_layanan,harga,durasi FROM layanan WHERE aktif = TRUE ORDER BY nama_layanan'
        );
        $queue = rows(
            'SELECT t.id_transaksi,t.no_invoice,t.status_pengerjaan,t.tanggal_transaksi,
                    COALESCE(SUM(d.berat),0)::numeric AS total_berat,
                    STRING_AGG(DISTINCT l.nama_layanan, \', \') AS layanan
             FROM transaksi t
             LEFT JOIN detail_transaksi d USING (id_transaksi)
             LEFT JOIN layanan l USING (id_layanan)
             WHERE t.id_pelanggan = ? AND t.status_pengerjaan <> \'diambil\'
             GROUP BY t.id_transaksi
             ORDER BY t.tanggal_transaksi DESC',
            [$customerId]
        );
        respond(['data'=>['pelanggan'=>$customer,'layanan'=>$services,'antrian'=>$queue]]);
    }

    respond(['error'=>'Action tidak ditemukan'],404);
}
