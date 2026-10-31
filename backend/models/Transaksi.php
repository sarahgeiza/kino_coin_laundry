<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/DetailTransaksi.php';
require_once __DIR__ . '/Layanan.php';

class Transaksi extends BaseModel
{
    private const SELECT = 'SELECT t.id_transaksi, t.id_kasir, u.nama AS nama_kasir,
                                   t.tanggal_transaksi, t.id_pelanggan, p.nama_pelanggan,
                                   t.id_shift, s.nama_shift, t.jenis_pembayaran, t.total_bayar
                            FROM transaksi t
                            JOIN pengguna u ON t.id_kasir = u.id_pengguna
                            JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
                            JOIN shift s ON t.id_shift = s.id_shift';

    public function getAll(): array
    {
        return $this->fetchAll(self::SELECT . ' ORDER BY t.id_transaksi');
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne(self::SELECT . ' WHERE t.id_transaksi = $1', [$id]);
    }

    /** tanggal_transaksi diisi otomatis oleh DEFAULT CURRENT_TIMESTAMP. */
    public function create(
        int $id_kasir,
        int $id_pelanggan,
        int $id_shift,
        string $jenis_pembayaran,
        int $total_bayar
    ): ?array {
        return $this->fetchOne(
            'INSERT INTO transaksi (id_kasir, id_pelanggan, id_shift, jenis_pembayaran, total_bayar)
             VALUES ($1, $2, $3, $4, $5)
             RETURNING id_transaksi',
            [$id_kasir, $id_pelanggan, $id_shift, $jenis_pembayaran, $total_bayar]
        );
    }

    /**
     * Simpan transaksi + detailnya dalam SATU database transaction.
     * Harga diambil dari tabel layanan (bukan dari klien), total dihitung di server.
     *
     * ASUMSI (dari data contoh di SQL): subtotal = harga layanan, tidak dikali jumlah_koin
     * (mis. Cuci Kering, 2 koin, subtotal 16000). Jika aturannya berbeda, ubah di bagian
     * "$subtotal" di bawah.
     *
     * @param array<int, array{id_layanan:int, berat:?float, jumlah_koin:int}> $items
     * @return int id_transaksi baru
     */
    public function createWithDetails(
        int $id_kasir,
        int $id_pelanggan,
        int $id_shift,
        string $jenis_pembayaran,
        array $items
    ): int {
        if ($items === []) {
            throw new InvalidArgumentException('Detail transaksi tidak boleh kosong.');
        }
        if (!in_array($jenis_pembayaran, ['cash', 'qris'], true)) {
            throw new InvalidArgumentException('Jenis pembayaran harus cash atau qris.');
        }

        $layananModel = new Layanan($this->conn);

        // Siapkan detail lebih dulu (validasi layanan) sebelum BEGIN.
        $details = [];
        $total = 0;
        foreach ($items as $item) {
            $layanan = $layananModel->getById((int) $item['id_layanan']);
            if ($layanan === null) {
                throw new InvalidArgumentException('Layanan tidak ditemukan: ' . $item['id_layanan']);
            }

            $harga    = (int) $layanan['harga'];
            $subtotal = $harga; // lihat catatan ASUMSI di atas
            $total   += $subtotal;

            $details[] = [
                'id_layanan'   => (int) $item['id_layanan'],
                'berat'        => $item['berat'] ?? null,
                'jumlah_koin'  => (int) $item['jumlah_koin'],
                'harga_satuan' => $harga,
                'subtotal'     => $subtotal,
            ];
        }

        $this->run('BEGIN');

        try {
            $header = $this->create($id_kasir, $id_pelanggan, $id_shift, $jenis_pembayaran, $total);
            $idTransaksi = (int) $header['id_transaksi'];

            $detailModel = new DetailTransaksi($this->conn);
            foreach ($details as $d) {
                $detailModel->create(
                    $idTransaksi,
                    $d['id_layanan'],
                    $d['berat'],
                    $d['jumlah_koin'],
                    $d['harga_satuan'],
                    $d['subtotal']
                );
            }

            $this->run('COMMIT');

            return $idTransaksi;
        } catch (Throwable $e) {
            @pg_query($this->conn, 'ROLLBACK');
            throw $e;
        }
    }
}
