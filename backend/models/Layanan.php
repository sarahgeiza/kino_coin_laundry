```php
<?php

class Layanan
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Menampilkan semua layanan
    public function getAll()
    {
        $query = "SELECT * FROM layanan ORDER BY id_layanan";
        $result = pg_query($this->conn, $query);

        return pg_fetch_all($result) ?: [];
    }

    // Menampilkan satu layanan berdasarkan ID
    public function getById($id)
    {
        $query = "SELECT * FROM layanan WHERE id_layanan = $1";
        $result = pg_query_params($this->conn, $query, [$id]);

        return pg_fetch_assoc($result);
    }

    // Menambahkan layanan
    public function create($nama_layanan, $harga, $berat_standar)
    {
        $query = "INSERT INTO layanan (nama_layanan, harga, berat_standar)
                  VALUES ($1, $2, $3)";

        return pg_query_params(
            $this->conn,
            $query,
            [$nama_layanan, $harga, $berat_standar]
        );
    }

    // Mengubah layanan
    public function update($id, $nama_layanan, $harga, $berat_standar)
    {
        $query = "UPDATE layanan
                  SET nama_layanan = $1,
                      harga = $2,
                      berat_standar = $3
                  WHERE id_layanan = $4";

        return pg_query_params(
            $this->conn,
            $query,
            [$nama_layanan, $harga, $berat_standar, $id]
        );
    }

    // Menghapus layanan
    public function delete($id)
    {
        $query = "DELETE FROM layanan WHERE id_layanan = $1";

        return pg_query_params($this->conn, $query, [$id]);
    }
}
