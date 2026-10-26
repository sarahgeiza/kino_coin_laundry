
<?php

// Tracking pesanan pelanggan menggunakan nomor HP
if ($module === 'riwayat') {

    if ($action === 'tracking') {
        method('POST');

        $b = body();
        need($b, ['no_hp']);

        $noHp = trim(strval_required($b['no_hp'], 20));

        if ($noHp === '') {
            respond(['error' => 'Nomor HP wajib diisi'], 422);
        }

        // Cari pelanggan berdasarkan nomor HP
        $pelanggan = one(
            'SELECT id_pelanggan, nama_pelanggan
             FROM pelanggan
             WHERE no_hp = $1',
            [$noHp]
        );

        if (!$pelanggan) {
            respond([
                'message' => 'Pelanggan tidak ditemukan',
                'data' => []
            ]);
        }

        // Ambil seluruh transaksi milik pelanggan
        $transaksi = rows(
            'SELECT id_transaksi,
                    tanggal_transaksi,
                    jenis_pembayaran,
                    total_bayar
             FROM transaksi
             WHERE id_pelanggan = $1
             ORDER BY id_transaksi DESC',
            [(int) $pelanggan['id_pelanggan']]
        );

        // Ambil detail layanan untuk setiap transaksi
        foreach ($transaksi as &$t) {
            $t['detail_transaksi'] = rows(
                'SELECT l.nama_layanan,
                        d.berat,
                        d.jumlah_koin,
                        d.harga_satuan,
                        d.subtotal
                 FROM detail_transaksi d
                 JOIN layanan l USING (id_layanan)
                 WHERE d.id_transaksi = $1',
                [(int) $t['id_transaksi']]
            );
        }
        unset($t);

        respond([
            'message' => 'Data tracking berhasil ditemukan',
            'pelanggan' => [
                'nama_pelanggan' => $pelanggan['nama_pelanggan']
            ],
            'data' => $transaksi
        ]);
    }

    respond(['error' => 'Action tidak ditemukan'], 404);
}
