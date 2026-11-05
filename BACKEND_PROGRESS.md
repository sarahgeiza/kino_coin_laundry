# Backend Progress Kino Coin Laundry

Pembaruan audit: 5 November 2026

## Status

Audit statis repository sudah dilakukan. Belum ada koneksi ke database aktif,
perubahan skema, atau pengujian integrasi. Database `kino_coin_laundry2` belum
boleh dinyatakan rapi/terverifikasi sampai skema aktual dan hasil query baca
saja tersedia.

## Struktur dan skema yang didefinisikan repository

- `api/index.php` merutekan endpoint `module` dan `action`.
- `backend/controllers/` berisi autentikasi, pelanggan, layanan, pengguna,
  transaksi, shift operasional, rekap, serta riwayat.
- `backend/models/` menyediakan model PostgreSQL; beberapa halaman PHP juga
  mengakses database langsung melalui `includes/helpers.php`.
- `database/00_schema_awal.sql` mendefinisikan `pengguna`, `pelanggan`,
  `layanan`, `shift`, `transaksi`, dan `detail_transaksi`.
- Migrasi 01–06 menambahkan constraint/indeks, operasional/pembayaran,
  penghapusan kolom pecahan koin lama, jumlah detail, kode kartu pelanggan,
  jadwal, serta kaitan shift. Migrasi 04 yang menimpa snapshot historis sudah
  dihapus agar tidak dijalankan tanpa sengaja.
- Migrasi 03 menghapus kolom lama dan tetap perlu ditinjau sebelum digunakan
  pada database yang sudah berisi data.

## Perbaikan kode pada audit ini

- `backend/controllers/ShiftController.php`: endpoint detail shift sebelumnya
  memanggil `Shift::find()` yang tidak tersedia di model. Kini memakai query
  parameter dan mengembalikan 404 jika shift tidak ditemukan.
- `backend/controllers/ShiftOperasionalController.php`: saat check-in tanpa
  jadwal tersimpan, `id_jadwal` bernilai SQL NULL. Kode sebelumnya mengubahnya
  menjadi `0` yang dapat melanggar foreign key; kini nilai NULL dipertahankan.

## Endpoint yang terlihat tersedia dalam router/controller

- `health/list`
- `auth/login`, `auth/register`, `auth/me`, `auth/logout`
- `pengguna/list|detail|create|update|password|delete`
- `pelanggan/list|detail|create|update|password`
- `layanan/list|detail|create|update|toggle|delete`
- `shift/list|detail|create|update`
- `shift-operasional/current|open|close|logs` (logs hanya pemilik)
- `transaksi/list|detail|create|status`
- `detail-transaksi/list|detail`
- `dashboard/index|pemilik|kasir`; `rekap/layanan|koin|shift|kasir|periode|pembayaran`
- `riwayat/tracking|list|detail`

Koleksi Postman juga masih berisi alur `riwayat/buat-akses`, `riwayat/login`,
dan `riwayat/logout` berbasis kode akses yang tidak tersedia pada router aktif.
Perlu diselaraskan sebelum koleksi dipakai sebagai bukti uji.

## Belum diverifikasi / masih pending

- Audit tabel, kolom, constraint, indeks, duplikasi, dan data pada database
  `kino_coin_laundry2`; tidak ada konfigurasi lokal, dan ekstensi PHP `pgsql`
  tidak tersedia pada CLI saat audit.
- Query SQL referensi yang disebutkan belum terlampir pada percakapan ini.
- Belum ada perubahan atau pembersihan database; belum dibuat migration baru
  karena skema aktual dan ketergantungan data belum bisa dibandingkan.
- Belum diuji koneksi, endpoint, autentikasi, transaksi, maupun simpan-baca
  melalui Postman, localhost, atau DBeaver.
- Aturan username membership pelanggan belum sesuai skema/kode saat ini yang
  menggunakan nomor WhatsApp dan password; frontend yang ada mengirim nomor HP.
- Pembatalan transaksi, filter laporan periode khusus, dan export XLSX belum
  ditemukan sebagai fitur lengkap. Halaman laporan frontend menyediakan CSV.
- Belum ada verifikasi bahwa frontend terbaru sudah menggunakan kontrak API
  ini; file API client yang tersedia masih menjadi acuan lokal.

## Informasi yang diperlukan untuk melanjutkan verifikasi database

1. Lampirkan query SQL referensi yang dimaksud.
2. Siapkan akses baca ke database `kino_coin_laundry2` dari lingkungan ini,
   misalnya ekstensi PHP `pgsql` aktif dan konfigurasi lokal/lingkungan tersedia.
   Jangan kirim password database di chat; gunakan konfigurasi lokal yang
   diabaikan Git.
3. Setelah akses baca tersedia, inventaris database dilakukan dahulu. Perubahan
   destruktif atau penghapusan data tidak dijalankan tanpa persetujuan terpisah.
