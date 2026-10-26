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
Buka terminal di `C:\PBL`, lalu jalankan:
```bash
php -S localhost:8000
```
Buka browser di: `http://localhost:8000`
