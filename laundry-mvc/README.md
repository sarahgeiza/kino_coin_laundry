# Kino Laundry MVC

Aplikasi contoh modular yang terpisah dari prototipe di direktori utama. Aplikasi menggunakan PHP native, PDO PostgreSQL, dan tabel `pelanggan` serta `pesanan`.

## Konfigurasi

Gunakan PHP 8.1 atau lebih baru dengan ekstensi `pdo_pgsql` dan `mbstring`, serta PostgreSQL aktif. Atur environment berikut sebelum menjalankan:

```powershell
$env:DB_HOST = "localhost"
$env:DB_PORT = "5432"
$env:DB_NAME = "kino_laundry"
$env:DB_USER = "postgres"
$env:DB_PASSWORD = "password-postgres-anda"
```

Buat database `kino_laundry`, lalu jalankan `database/001_create_tables.sql` pada database tersebut.

Jalankan server pengembangan dari folder `laundry-mvc`:

```powershell
php -S localhost:8001
```

Buka `http://localhost:8001`, lalu pilih **Scan QR** dari navigasi atau tombol di Beranda. Scan QR dapat menggunakan kamera (izin kamera diperlukan) atau scanner USB yang bertindak sebagai keyboard. Akses kamera membutuhkan `localhost` atau HTTPS. Kamera memakai pustaka CDN jika tersedia, lalu mencoba `BarcodeDetector` bawaan pada browser yang mendukungnya.

## Struktur

- `config/`: koneksi PDO dan fungsi konfigurasi/helper.
- `controllers/`: validasi dan alur pelanggan, pesanan, serta status.
- `models/`: query PostgreSQL.
- `views/`: halaman beranda, pelanggan baru, scan QR, detail pelanggan, dan layout.
- `database/`: migrasi skema PostgreSQL.
