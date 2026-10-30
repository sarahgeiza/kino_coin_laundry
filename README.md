# Kino Coin Laundry - Sistem Informasi Manajemen Operasional (Frontend Mockup)

Frontend web berbasis PHP Native (tanpa framework) sesuai rancangan proposal PBL dan UI mockup.

## Struktur Direktori
- `index.php` : Layar 01 - Autentikasi / Login multi-peran (Kasir, Pemilik, Pelanggan)
- `logout.php` : Reset session & logout
- `css/style.css` : Stylesheet modern modular (responsive mobile/desktop layout)
- `js/app.js` : Interaksi dinamis (kalkulator live koin 500 & 1000, validasi beban max 7.5 kg, modal popup, rekonsiliasi kas)
- `includes/`
  - `data.php` : Mock session data store (Pelanggan, Layanan, Shift, Transaksi). Siap di-swap ke database PostgreSQL / PDO.
  - `header.php` : Komponen header responsif & status bar
  - `footer.php` : Navigasi bawah per peran (bottom navigation bar)
- `kasir/`
  - `index.php` : Layar 02 - Dashboard Kasir (ringkasan shift, metrik uang/koin, transaksi terbaru, FAB +)
  - `transaksi-baru.php` : Layar 03 - Form Input Transaksi Baru (nama, WA, layanan, timbangan kg, input koin 500 & 1000, live kalkulasi subtotal/kurang/lunas)
  - `transaksi.php` : Layar 04 - Daftar Transaksi Shift Ini & detail faktur modal
  - `tutup-shift.php` : Layar 05 - Tutup Shift & Rekonsiliasi Kas (sistem vs fisik & selisih otomatis)
- `admin/`
  - `index.php` : Layar 06 - Dashboard Pemilik (metrik omzet harian, grafik koin 7 hari, rekap shift)
  - `layanan.php` : Layar 07 - Master Layanan (daftar tarif/kg, modal tambah paket layanan)
  - `laporan.php` : Layar 08 - Laporan Keuangan (grafik uang masuk harian, detail rekap shift & status review)
- `pelanggan/`
  - `index.php` : Layar 09 - Home Pelanggan (status antrian cuci real-time, estimasi waktu, tarif layanan)
  - `riwayat.php` : Riwayat cucian pelanggan

## Cara Menjalankan
Buka PowerShell pada folder proyek, lalu jalankan:
```bash
Set-Location C:\PBL\kino_coin_laundry-git
php -S localhost:8000
```
Buka browser di: `http://localhost:8000`

## Pendaftaran pelanggan dan QR member

Pada halaman **Kasir → Transaksi Baru**, kasir mengisi nama dan nomor HP satu kali. Saat transaksi dikirim, aplikasi mencari atau membuat pelanggan di PostgreSQL dan menampilkan QR yang membuka login member. Data yang sudah terdaftar menggunakan profil lama berdasarkan nomor HP, jadi tidak membuat pelanggan duplikat. Login pelanggan di halaman awal sekarang diarahkan ke pemindai QR, bukan akun demo Budi.

Sebelum menjalankan server, atur `APP_KEY` dengan nilai acak minimal 32 karakter. Buat dan simpan nilainya **sekali saja** sebagai environment variable Windows; jangan membuat key baru setiap aplikasi dijalankan karena QR lama akan tidak berlaku. Untuk QR yang dipindai dari HP lain, atur `APP_BASE_URL` ke alamat server yang dapat dijangkau HP tersebut, misalnya `http://192.168.1.10:8000` (gunakan alamat IP komputer kasir; kamera memerlukan HTTPS atau localhost). Contoh setup pertama kali di PowerShell:

```powershell
# Setup pertama kali saja. Simpan key ini dan jangan generate ulang.
$key = (php -r "echo bin2hex(random_bytes(32));")
[Environment]::SetEnvironmentVariable("APP_KEY", $key, "User")
$env:APP_KEY = $key
$env:APP_BASE_URL = "http://192.168.1.10:8000"
Set-Location C:\PBL\kino_coin_laundry-git
php -S 0.0.0.0:8000
```

Jalankan migrasi `database/02_qr_member.sql` pada database `kino_coin_laundry`. Migrasi menambahkan akses QR dan unique index nomor HP supaya satu nomor tidak membuat pelanggan ganda; jika data lama sudah memiliki nomor duplikat, migrasi berhenti dan data perlu dirapikan dahulu. Token QR disimpan dalam bentuk hash di tabel `akses_pelanggan` dan berlaku lima tahun. Transaksi/shift pada UI kasir utama masih menggunakan data session prototipe; hanya profil pelanggan dan login QR yang pada alur ini tersimpan di PostgreSQL.
