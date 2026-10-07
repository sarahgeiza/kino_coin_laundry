```php
<?php

class Transaksi
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Menampilkan semua transaksi
    public function getAll()
    {
        $query = "SELECT
                    t.id_transaksi,
                    t.id_kasir,
                    u.nama AS nama_kasir,
                    t.tanggal_transaksi,
                    t.id_pelanggan,
                    p.nama_pelanggan,
                    t.id_shift,
                    s.nama_shift,
                    t.jenis_pembayaran,
                    t.total_bayar
                  FROM transaksi t
                  JOIN pengguna u
                    ON t.id_kasir = u.id_pengguna
                  JOIN pelanggan p
                    ON t.id_pelanggan = p.id_pelanggan
                  JOIN shift s
                    ON t.id_shift = s.id_shift
                  ORDER BY t.id_transaksi";

        $result = pg_query($this->conn, $query);

        return pg_fetch_all($result) ?: [];
    }

    // Menampilkan satu transaksi berdasarkan ID
    public function getById($id)
    {
        $query = "SELECT
                    t.id_transaksi,
                    t.id_kasir,
                    u.nama AS nama_kasir,
                    t.tanggal_transaksi,
                    t.id_pelanggan,
                    p.nama_pelanggan,
                    t.id_shift,
                    s.nama_shift,
                    t.jenis_pembayaran,
                    t.total_bayar
                  FROM transaksi t
                  JOIN pengguna u
                    ON t.id_kasir = u.id_pengguna
                  JOIN pelanggan p
                    ON t.id_pelanggan = p.id_pelanggan
                  JOIN shift s
                    ON t.id_shift = s.id_shift
                  WHERE t.id_transaksi = $1";

        $result = pg_query_params($this->conn, $query, [$id]);

        return pg_fetch_assoc($result);
    }

    // Membuat transaksi baru
    public function create(
        $id_kasir,
        $id_pelanggan,
        $id_shift,
        $jenis_pembayaran,
        $total_bayar
    ) {
        $query = "INSERT INTO transaksi
                    (id_kasir, id_pelanggan, id_shift,
                     jenis_pembayaran, total_bayar)
                  VALUES
                    ($1, $2, $3, $4, $5)
                  RETURNING id_transaksi";

        $result = pg_query_params(
            $this->conn,
            $query,
            [
                $id_kasir,
                $id_pelanggan,
                $id_shift,
                $jenis_pembayaran,
                $total_bayar
            ]
        );

        if ($result) {
            return pg_fetch_assoc($result);
        }

        return false;
    }
}
