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
        $remaining = 0;
        $status = 'lunas';
        return $this->fetchOne(
            'INSERT INTO transaksi (id_kasir, id_pelanggan, id_shift, jenis_pembayaran, total_bayar,
                                    sisa_bayar, status_pembayaran)
             VALUES ($1, $2, $3, $4, $5, $6, $7)
             RETURNING id_transaksi',
            [$id_kasir, $id_pelanggan, $id_shift, $jenis_pembayaran, $total_bayar, $remaining, $status]
        );
    }

    /**
     * Simpan transaksi + detailnya dalam SATU database transaction.
     * Harga diambil dari tabel layanan (bukan dari klien), total dihitung di server.
     *
     * Subtotal dihitung sebagai berat (kg) dikali harga layanan per kilogram.
     *
     * @param array<int, array{id_layanan:int, berat:?float}> $items
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
            if ($layanan === null || (isset($layanan['aktif']) && !in_array($layanan['aktif'], [true, 't', '1', 1], true))) {
                throw new InvalidArgumentException('Layanan tidak ditemukan atau tidak aktif: ' . $item['id_layanan']);
            }

            $weight = isset($item['berat']) ? (float)$item['berat'] : 0.0;
            if (!is_finite($weight) || $weight <= 0 || $weight > 7.5) {
                throw new InvalidArgumentException('Berat setiap detail harus lebih dari 0 dan maksimal 7,5 kg.');
            }
            $harga = (int) $layanan['harga'];
            $subtotal = (int) round($weight * $harga);
            $total += $subtotal;

            $details[] = [
                'id_layanan'   => (int) $item['id_layanan'],
                'berat'        => $weight,
                'jumlah_koin'  => (int) ($layanan['jumlah_koin'] ?? 0),
                'harga_satuan' => $harga,
                'subtotal'     => $subtotal,
            ];
        }

        $totalWeight = array_sum(array_column($details, 'berat'));
        if ($totalWeight > 7.5) {
            throw new InvalidArgumentException('Total berat transaksi maksimal 7,5 kg.');
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
