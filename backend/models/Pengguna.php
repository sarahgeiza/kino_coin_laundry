```php
<?php

class Pengguna
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Menampilkan semua pengguna
    public function getAll()
    {
        $query = "SELECT * FROM pengguna ORDER BY id_pengguna";
        $result = pg_query($this->conn, $query);

        return pg_fetch_all($result) ?: [];
    }

    // Menampilkan satu pengguna berdasarkan ID
    public function getById($id)
    {
        $query = "SELECT * FROM pengguna WHERE id_pengguna = $1";
        $result = pg_query_params($this->conn, $query, [$id]);

        return pg_fetch_assoc($result);
    }

    // Menambahkan pengguna
    public function create($nama, $username, $role, $password)
    {
        $query = "INSERT INTO pengguna (nama, username, role, password)
                  VALUES ($1, $2, $3, $4)";

        return pg_query_params(
            $this->conn,
            $query,
            [$nama, $username, $role, $password]
        );
    }

    // Mengubah pengguna
    public function update($id, $nama, $username, $role, $password)
    {
        $query = "UPDATE pengguna
                  SET nama = $1,
                      username = $2,
                      role = $3,
                      password = $4
                  WHERE id_pengguna = $5";

        return pg_query_params(
            $this->conn,
            $query,
            [$nama, $username, $role, $password, $id]
        );
    }

    // Menghapus pengguna
    public function delete($id)
    {
        $query = "DELETE FROM pengguna WHERE id_pengguna = $1";

        return pg_query_params($this->conn, $query, [$id]);
    }
}
