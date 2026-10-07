```php
<?php

class Pelanggan
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Menampilkan semua pelanggan
    public function getAll()
    {
        $query = "SELECT * FROM pelanggan ORDER BY id_pelanggan";
        $result = pg_query($this->conn, $query);

        return pg_fetch_all($result) ?: [];
    }

    // Menampilkan satu pelanggan berdasarkan ID
    public function getById($id)
    {
        $query = "SELECT * FROM pelanggan WHERE id_pelanggan = $1";
        $result = pg_query_params($this->conn, $query, [$id]);

        return pg_fetch_assoc($result);
    }

    // Menambahkan pelanggan
    public function create($nama_pelanggan, $no_hp)
    {
        $query = "INSERT INTO pelanggan (nama_pelanggan, no_hp)
                  VALUES ($1, $2)";

        return pg_query_params(
            $this->conn,
            $query,
            [$nama_pelanggan, $no_hp]
        );
    }

    // Mengubah pelanggan
    public function update($id, $nama_pelanggan, $no_hp)
    {
        $query = "UPDATE pelanggan
                  SET nama_pelanggan = $1,
                      no_hp = $2
                  WHERE id_pelanggan = $3";

        return pg_query_params(
            $this->conn,
            $query,
            [$nama_pelanggan, $no_hp, $id]
        );
    }

    // Menghapus pelanggan
    public function delete($id)
    {
        $query = "DELETE FROM pelanggan WHERE id_pelanggan = $1";

        return pg_query_params($this->conn, $query, [$id]);
    }
}
