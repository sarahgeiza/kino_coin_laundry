```php
<?php

class DetailTransaksi
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Menampilkan semua detail transaksi
    public function getAll()
    {
        $query = "SELECT
                    d.id_detail,
                    d.id_transaksi,
                    d.id_layanan,
                    l.nama_layanan,
                    d.berat,
                    d.jumlah_koin,
                    d.harga_satuan,
                    d.subtotal
                  FROM detail_transaksi d
                  JOIN layanan l
                    ON d.id_layanan = l.id_layanan
                  ORDER BY d.id_detail";

        $result = pg_query($this->conn, $query);

        return pg_fetch_all($result) ?: [];
    }

    // Menampilkan detail berdasarkan ID
    public function getById($id)
    {
        $query = "SELECT
                    d.id_detail,
                    d.id_transaksi,
                    d.id_layanan,
                    l.nama_layanan,
                    d.berat,
                    d.jumlah_koin,
                    d.harga_satuan,
                    d.subtotal
                  FROM detail_transaksi d
                  JOIN layanan l
                    ON d.id_layanan = l.id_layanan
                  WHERE d.id_detail = $1";

        $result = pg_query_params($this->conn, $query, [$id]);

        return pg_fetch_assoc($result);
    }

    // Menampilkan semua detail dari satu transaksi
    public function getByTransaksi($id_transaksi)
    {
        $query = "SELECT
                    d.id_detail,
                    d.id_transaksi,
                    d.id_layanan,
                    l.nama_layanan,
                    d.berat,
                    d.jumlah_koin,
                    d.harga_satuan,
                    d.subtotal
                  FROM detail_transaksi d
                  JOIN layanan l
                    ON d.id_layanan = l.id_layanan
                  WHERE d.id_transaksi = $1
                  ORDER BY d.id_detail";

        $result = pg_query_params(
            $this->conn,
            $query,
            [$id_transaksi]
        );

        return pg_fetch_all($result) ?: [];
    }

    // Menambahkan detail transaksi
    public function create(
        $id_transaksi,
        $id_layanan,
        $berat,
        $jumlah_koin,
        $harga_satuan,
        $subtotal
    ) {
        $query = "INSERT INTO detail_transaksi
                    (id_transaksi, id_layanan, berat,
                     jumlah_koin, harga_satuan, subtotal)
                  VALUES
                    ($1, $2, $3, $4, $5, $6)
                  RETURNING id_detail";

        $result = pg_query_params(
            $this->conn,
            $query,
            [
                $id_transaksi,
                $id_layanan,
                $berat,
                $jumlah_koin,
                $harga_satuan,
                $subtotal
            ]
        );

        if ($result) {
            return pg_fetch_assoc($result);
        }

        return false;
    }
}
