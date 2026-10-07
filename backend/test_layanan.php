<?php

require_once "config/config.php";
require_once "models/Layanan.php";

$layanan = new Layanan($conn);

$result = $layanan->getAll();

while ($row = pg_fetch_assoc($result)) {
    echo $row['nama_layanan'] . " - Rp " . $row['harga'] . "<br>";
}

?>