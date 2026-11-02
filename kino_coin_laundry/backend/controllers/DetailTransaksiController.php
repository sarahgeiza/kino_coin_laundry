<?php
declare(strict_types=1);

if ($module === 'detail-transaksi') {
    method('GET');
    $u = actor();

    if ($action === 'list') {
        $transactionId = integer($_GET['id_transaksi'] ?? null);
        $transaction = one('SELECT id_pelanggan,id_kasir FROM transaksi WHERE id_transaksi = ?', [$transactionId]);
        if (!$transaction)
            respond(['error' => 'Transaksi tidak ditemukan'], 404);
        if (($u['role'] ?? '') === 'kasir' && (int) $transaction['id_kasir'] !== (int) $u['id_pengguna'])
            respond(['error' => 'Akses ditolak'], 403);
        if (($u['role'] ?? '') === 'pelanggan' && (int) $transaction['id_pelanggan'] !== (int) $u['id_pengguna'])
            respond(['error' => 'Akses ditolak'], 403);
        if (!in_array($u['role'], ['pemilik', 'kasir', 'pelanggan'], true))
            respond(['error' => 'Akses ditolak'], 403);
        respond([
            'data' => rows(
                'SELECT d.*,l.nama_layanan FROM detail_transaksi d JOIN layanan l USING(id_layanan)
             WHERE d.id_transaksi = ? ORDER BY d.id_detail',
                [$transactionId]
            )
        ]);
    }

    if ($action === 'detail') {
        $detailId = idparam();
        $detail = one(
            'SELECT d.*,l.nama_layanan,t.id_pelanggan,t.id_kasir
             FROM detail_transaksi d JOIN layanan l USING(id_layanan)
             JOIN transaksi t USING(id_transaksi)
             WHERE d.id_detail = ?',
            [$detailId]
        );
        if (!$detail)
            respond(['error' => 'Detail transaksi tidak ditemukan'], 404);
        if (($u['role'] ?? '') === 'kasir' && (int) $detail['id_kasir'] !== (int) $u['id_pengguna'])
            respond(['error' => 'Akses ditolak'], 403);
        if (($u['role'] ?? '') === 'pelanggan' && (int) $detail['id_pelanggan'] !== (int) $u['id_pengguna'])
            respond(['error' => 'Akses ditolak'], 403);
        unset($detail['id_pelanggan'], $detail['id_kasir']);
        respond(['data' => $detail]);
    }

    respond(['error' => 'Action tidak ditemukan'], 404);
}
