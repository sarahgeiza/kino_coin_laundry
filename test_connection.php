<?php

require_once "backend/config/config.php";

$query = "SELECT * FROM layanan ORDER BY id_layanan";
$result = pg_query($conn, $query);

if (!$result) {
    die("Query gagal.");
}

while ($row = pg_fetch_assoc($result)) {
    echo $row['nama_layanan'] . " - Rp " . $row['harga'] . "<br>";
}

?>