# Kino Coin Laundry

Aplikasi manajemen operasional laundry berbasis PHP Native dan PostgreSQL. Frontend PHP/HTML/CSS/JavaScript tetap menggunakan struktur halaman dan stylesheet yang sudah ada; data operasional dibaca dan ditulis melalui PostgreSQL.

## Teknologi dan prasyarat

- PHP 8.1+ (disarankan PHP 8.2 atau lebih baru)
- Ekstensi PHP `pgsql`
- PostgreSQL 13+
- Web server Apache/Nginx atau PHP built-in server untuk pengembangan
- Browser modern dengan JavaScript aktif

Pastikan `pgsql` aktif pada PHP yang digunakan web server dan CLI. Periksa dengan `php -m`.

## Konfigurasi database

1. Buat database PostgreSQL, misalnya `kino_coin_laundry`.
2. Salin `backend/config/database.example.php` menjadi `backend/config/database.local.php`.
3. Isi host, port, nama database, username, dan password di file lokal tersebut.
4. Jangan commit atau membagikan `database.local.php`.

Koneksi memakai `pg_connect()` dan seluruh query aplikasi memakai `pg_query_params()`/parameter binding.

## Menyiapkan skema

Jalankan SQL menggunakan `psql` atau pgAdmin, pada database yang benar, dengan urutan:

```bash
psql -U postgres -d kino_coin_laundry -f database/00_schema_awal.sql
psql -U postgres -d kino_coin_laundry -f database/01_migrasi_database.sql
psql -U postgres -d kino_coin_laundry -f database/02_operasional_backend.sql
```

`00_schema_awal.sql` membuat tabel inti untuk instalasi baru tanpa menghapus tabel/data yang sudah ada. `01_migrasi_database.sql` berisi constraint dan indeks dasar. `02_operasional_backend.sql` menambahkan atribut layanan/pelanggan/transaksi dan tabel `shift_operasional`.

Untuk database produksi yang telah berisi data, lakukan backup terlebih dahulu dan tinjau skema aktual sebelum menjalankan migration. Jangan menjalankan reset/drop/truncate terhadap data produksi.

## Membuat akun awal

Tidak ada kredensial demo yang ditanam dalam source code. Setelah skema dibuat, buat akun pemilik dan kasir dengan hash password:

```bash
php -r "echo password_hash('GANTI_DENGAN_PASSWORD_KUAT', PASSWORD_DEFAULT), PHP_EOL;"
```

Masukkan hasil hash ke kolom `pengguna.password`, bukan password aslinya:

```sql
INSERT INTO pengguna (nama, username, role, password)
VALUES ('Pemilik', 'pemilik', 'pemilik', '<HASH_PASSWORD>');
```

Buat akun kasir dengan perintah serupa dan username berbeda. Pelanggan dapat membuat akun melalui `POST api/index.php?module=auth&action=register`, atau pelanggan yang sudah dibuat kasir dapat diberi akses setelah memiliki `password_hash`.

## Menjalankan aplikasi

Dari direktori project:

```bash
php -S 127.0.0.1:8000
```

Buka `http://127.0.0.1:8000`.

Untuk penggunaan produksi, gunakan Apache/Nginx dengan HTTPS. Atur document root ke direktori project dan pertahankan aturan `.htaccess` yang membatasi akses langsung ke `backend/`, `database/`, `includes/`, dan `scripts/`.

## Autentikasi dan hak akses

- Pemilik: akun pada tabel `pengguna` dengan role `pemilik`.
- Kasir: akun pada tabel `pengguna` dengan role `kasir`.
- Pelanggan: akun pada tabel `pelanggan`, login menggunakan nomor WhatsApp dan password.

Password disimpan menggunakan `password_hash()` dan diverifikasi menggunakan `password_verify()`. Session ID diregenerasi setelah login. Operasi perubahan data API memerlukan CSRF token pada header `X-CSRF-Token`.

Pelanggan hanya dapat membaca dashboard dan transaksi yang terhubung dengan `id_pelanggan` miliknya. Nomor WhatsApp tidak dipakai sebagai pengganti autentikasi untuk membaca riwayat.

## API

Base URL pengembangan: `/api/index.php`.

Semua endpoint mengembalikan JSON. Endpoint yang mengubah data menggunakan `POST`, JSON body, session login, dan CSRF token (kecuali login/registrasi).

| Modul | Action | Hak akses | Keterangan |
|---|---|---|---|
| `health` | `list` | Publik | Pemeriksaan API |
| `auth` | `login` | Publik | Login pemilik/kasir/pelanggan |
| `auth` | `register` | Publik | Registrasi pelanggan |
| `auth` | `me` | Login | Profil session aktif dan CSRF token |
| `auth` | `logout` | Login + CSRF | Logout |
| `layanan` | `list`, `detail` | Login | Daftar/detail layanan; pelanggan hanya melihat layanan aktif |
| `layanan` | `create`, `update`, `toggle`, `delete` | Pemilik + CSRF | CRUD layanan |
| `shift` | `list`, `detail` | Login | Master jadwal shift |
| `shift-operasional` | `current`, `open`, `close` | Kasir; mutasi + CSRF | Shift operasional kasir |
| `transaksi` | `list`, `detail` | Pemilik/kasir | Daftar/detail transaksi sesuai pembatasan role |
| `transaksi` | `create` | Kasir + CSRF | Simpan transaksi dan detail secara atomik |
| `transaksi` | `status` | Pemilik/kasir + CSRF | Perbarui status pengerjaan pesanan |
| `dashboard`, `rekap` | `index`, `pemilik`, `layanan`, `shift`, `kasir`, `periode`, `pembayaran`, `koin` | Pemilik | Agregasi laporan |
| `pelanggan-self` | `dashboard` | Pelanggan | Layanan aktif dan status pesanan milik sendiri |
| `riwayat` | `list`, `detail` | Pelanggan | Riwayat transaksi milik sendiri |
| `pengguna` | `list`, `detail`, `create`, `update`, `password` | Pemilik | Manajemen akun staf |
| `pelanggan` | `list`, `detail`, `create`, `update`, `password` | Pemilik | Data pelanggan dan password akun pelanggan |

Contoh login:

```http
POST /api/index.php?module=auth&action=login
Content-Type: application/json

{"username":"pemilik","password":"PASSWORD_ANDA"}
```

Gunakan `csrf_token` dari response login atau `auth&action=me` untuk header operasi tulis.

Contoh transaksi:

```http
POST /api/index.php?module=transaksi&action=create
Content-Type: application/json
X-CSRF-Token: TOKEN_SESSION

{
  "nama_pelanggan": "Nama Pelanggan",
  "no_hp": "081234567890",
  "jenis_pembayaran": "cash",
  "koin_500": 2,
  "koin_1000": 1,
  "detail_transaksi": [
    {"id_layanan": 1, "berat": 3.5}
  ]
}
```

Harga selalu diambil dari database. Server menghitung subtotal `berat × harga layanan/kg`, total koin, sisa pembayaran, status pembayaran, dan nomor invoice.

## Aturan transaksi dan shift

- Berat satu transaksi maksimal 7,5 kg.
- Berat harus lebih dari nol; jumlah koin tidak boleh negatif.
- Layanan harus aktif.
- Kasir harus membuka shift operasional sebelum transaksi.
- Satu kasir hanya dapat memiliki satu shift aktif.
- Transaksi, detail, dan perhitungan total disimpan dalam database transaction.
- Rekonsiliasi shift membandingkan nilai koin yang tercatat dengan jumlah fisik yang dimasukkan saat penutupan. Pendapatan bruto tetap dihitung dari total tagihan transaksi.

## Pengujian

- Jalankan `php -l` pada file PHP yang berubah.
- Jalankan migration pada database uji terlebih dahulu.
- Jalankan `php test_connection.php` dari CLI untuk tes koneksi.
- Gunakan Postman Collection di `postman/Kino_Coin_Laundry.postman_collection.json`.
- Uji login tiap role, CSRF, pembuatan transaksi, shift aktif, penutupan shift, CRUD layanan, laporan, dan isolasi data pelanggan.

Pengujian integrasi database harus dilakukan pada environment yang memiliki ekstensi `pgsql` dan database PostgreSQL yang telah dimigrasikan.

## Struktur direktori utama

- `api/` — router REST API
- `backend/config/` — konfigurasi koneksi PostgreSQL
- `backend/controllers/` — controller API
- `backend/middleware/` — session security
- `backend/models/` — model database
- `database/` — skema dan migration SQL
- `includes/` — bootstrap halaman, data view-model, header/footer
- `admin/`, `kasir/`, `pelanggan/` — halaman aplikasi
- `css/`, `js/` — frontend yang dipertahankan
- `postman/` — koleksi request API
