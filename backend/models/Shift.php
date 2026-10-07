```php
<?php

class Shift
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Menampilkan semua shift
    public function getAll()
    {
        $query = "SELECT * FROM shift ORDER BY id_shift";
        $result = pg_query($this->conn, $query);

        return pg_fetch_all($result) ?: [];
    }

    // Menampilkan satu shift berdasarkan ID
    public function getById($id)
    {
        $query = "SELECT * FROM shift WHERE id_shift = $1";
        $result = pg_query_params($this->conn, $query, [$id]);

        return pg_fetch_assoc($result);
    }
}
