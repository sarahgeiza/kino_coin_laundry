<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class DetailTransaksi extends BaseModel
{
    private const SELECT = 'SELECT d.id_detail, d.id_transaksi, d.id_layanan, l.nama_layanan,
                                   d.berat, d.jumlah_koin, d.harga_satuan, d.subtotal
                            FROM detail_transaksi d
                            JOIN layanan l ON d.id_layanan = l.id_layanan';

    public function getAll(): array
    {
        return $this->fetchAll(self::SELECT . ' ORDER BY d.id_detail');
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne(self::SELECT . ' WHERE d.id_detail = $1', [$id]);
    }

    public function getByTransaksi(int $id_transaksi): array
    {
        return $this->fetchAll(
            self::SELECT . ' WHERE d.id_transaksi = $1 ORDER BY d.id_detail',
            [$id_transaksi]
        );
    }

    public function create(
        int $id_transaksi,
        int $id_layanan,
        ?float $berat,
        int $jumlah_koin,
        int $harga_satuan,
        int $subtotal
    ): ?array {
        return $this->fetchOne(
            'INSERT INTO detail_transaksi
                (id_transaksi, id_layanan, berat, jumlah_koin, harga_satuan, subtotal)
             VALUES ($1, $2, $3, $4, $5, $6)
             RETURNING id_detail',
            [$id_transaksi, $id_layanan, $berat, $jumlah_koin, $harga_satuan, $subtotal]
        );
    }
}
