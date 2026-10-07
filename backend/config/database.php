<?php

$host = "localhost";
$port = "5432";
$dbname = "kino_coin_laundry";
$username = "postgres";
$password = "12345678";

$conn = pg_connect(
    "host=$host port=$port dbname=$dbname user=$username password=$password"
);

if (!$conn) {
    die("Koneksi database gagal.");
}

echo "Database berhasil terhubung.";

?>