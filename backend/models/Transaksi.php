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
        int $total_bayar,
        int $id_shift_operasional
    ): ?array {
        $remaining = 0;
        $status = 'lunas';
        $invoice = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        return $this->fetchOne(
            'INSERT INTO transaksi (
                id_kasir, id_pelanggan, id_shift, jenis_pembayaran, total_bayar,
                sisa_bayar, status_pembayaran, id_shift_operasional,
                no_invoice, jumlah_dibayar, uang_kembalian
             )
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, 0)
             RETURNING id_transaksi, no_invoice',
            [
                $id_kasir, $id_pelanggan, $id_shift, $jenis_pembayaran,
                $total_bayar, $remaining, $status, $id_shift_operasional,
                $invoice, $total_bayar
            ]
        );
    }

    /**
     * Simpan transaksi + detailnya dalam SATU database transaction.
     * Harga diambil dari tabel layanan (bukan dari klien), total dihitung di server.
     *
     * Layanan proses dihitung per berat standar, Lipat per kg, tambahan per pcs.
     *
     * @param array<int, array{id_layanan:int, berat?:?float, jumlah?:int}> $items
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

            $harga = (int) $layanan['harga'];
            $coinsPerProcess = (int)($layanan['jumlah_koin'] ?? 0);
            $standardWeight = (float)($layanan['berat_standar'] ?? 0);
            if ($coinsPerProcess > 0 || $standardWeight > 0) {
                $weight = isset($item['berat']) ? (float)$item['berat'] : 0.0;
                if (!is_finite($weight) || $weight <= 0 || $weight > 999.99) {
                    throw new InvalidArgumentException('Berat layanan harus lebih dari 0 dan maksimal 999,99 kg.');
                }
                $processWeight = $standardWeight > 0 ? $standardWeight : 7.5;
                $quantity = (int)ceil($weight / $processWeight);
                $coinCount = $quantity * $coinsPerProcess;
                $subtotal = $quantity * $harga;
            } elseif (stripos((string)$layanan['nama_layanan'], 'lipat') !== false) {
                $weight = isset($item['berat']) ? (float)$item['berat'] : 0.0;
                if (!is_finite($weight) || $weight <= 0 || $weight > 999.99) {
                    throw new InvalidArgumentException('Berat Lipat harus lebih dari 0 dan maksimal 999,99 kg.');
                }
                $quantity = 1;
                $coinCount = 0;
                $subtotal = (int)round($weight * $harga);
            } else {
                $weight = null;
                $quantity = isset($item['jumlah']) ? (int)$item['jumlah'] : 1;
                if ($quantity < 1) {
                    throw new InvalidArgumentException('Jumlah layanan harus minimal 1.');
                }
                $coinCount = 0;
                $subtotal = $quantity * $harga;
            }
            $total += $subtotal;

            $details[] = [
                'id_layanan'   => (int) $item['id_layanan'],
                'berat'        => $weight,
                'jumlah'       => $quantity,
                'jumlah_koin'  => $coinCount,
                'harga_satuan' => $harga,
                'subtotal'     => $subtotal,
            ];
        }

        $this->run('BEGIN');

        try {
            $activeShift = $this->fetchOne(
                'SELECT id_shift_operasional
                 FROM shift_operasional
                 WHERE id_kasir = $1 AND id_shift = $2 AND status = $3
                 FOR UPDATE',
                [$id_kasir, $id_shift, 'aktif']
            );
            if ($activeShift === null) {
                throw new RuntimeException('Kasir belum memiliki shift operasional aktif.');
            }

            $header = $this->create(
                $id_kasir,
                $id_pelanggan,
                $id_shift,
                $jenis_pembayaran,
                $total,
                (int)$activeShift['id_shift_operasional']
            );
            $idTransaksi = (int) $header['id_transaksi'];

            $detailModel = new DetailTransaksi($this->conn);
            foreach ($details as $d) {
                $detailModel->create(
                    $idTransaksi,
                    $d['id_layanan'],
                    $d['berat'],
                    $d['jumlah_koin'],
                    $d['harga_satuan'],
                    $d['subtotal'],
                    $d['jumlah']
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
