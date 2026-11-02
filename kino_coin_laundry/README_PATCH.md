# Kino Coin Laundry — Patch Integrasi Cash/QRIS dan Koin Mesin

Paket ini merupakan hasil perbaikan dari proyek yang diberikan, bukan proyek baru.

## Perubahan utama

1. Koneksi database diseragamkan ke PostgreSQL native (`pg_connect`, `pg_query`, `pg_query_params`) melalui fungsi `getConnection()` yang memakai koneksi static per request.
2. Form transaksi kasir hanya menyediakan metode pembayaran Cash dan QRIS.
3. Referensi pembayaran berbasis pecahan uang `koin_500`, `koin_1000`, dan `nilai_koin` dihapus dari alur PHP/JS.
4. Kebutuhan koin mesin dibaca dari `layanan.jumlah_koin` di server, lalu disimpan ke `detail_transaksi.jumlah_koin`. Nilai koin tidak lagi dikirim atau dihitung sebagai uang pembayaran.
5. Halaman master layanan menyediakan pengaturan jumlah koin mesin per layanan.
6. Dashboard, laporan, dan tutup shift menghitung uang masuk berdasarkan `jumlah_dibayar`; rekonsiliasi kas fisik hanya menghitung pembayaran Cash, sedangkan QRIS ditampilkan terpisah.
7. `sisa_bayar` tetap dipertahankan. Transaksi baru pada form saat ini dibayar penuh; data lama yang memiliki pembayaran sebagian tetap dapat disimpan dan ditampilkan.
8. Migrasi `03_pemisahan_koin_mesin_dan_pembayaran.sql` mengarsipkan data kolom pecahan lama ke tabel `transaksi_pembayaran_koin_legacy`, menambahkan `jumlah_dibayar`, lalu menghapus kolom lama dari `transaksi`.
9. File `02_operasional_backend.sql` diperbarui agar instalasi baru tidak lagi membuat kolom pecahan uang di tabel transaksi.

## File penting yang diubah

- `backend/config/database.php`
- `backend/controllers/TransaksiController.php`
- `backend/controllers/LayananController.php`
- `backend/controllers/ShiftOperasionalController.php`
- `backend/controllers/DashboardRekapController.php`
- `backend/controllers/RiwayatController.php`
- `backend/models/Transaksi.php`
- `backend/models/Layanan.php`
- `includes/data.php`
- `api/index.php`
- `kasir/transaksi-baru.php`
- `kasir/transaksi.php`
- `kasir/index.php`
- `kasir/tutup-shift.php`
- `admin/index.php`
- `admin/laporan.php`
- `admin/layanan.php`
- `js/app.js`
- `database/02_operasional_backend.sql`
- `database/03_pemisahan_koin_mesin_dan_pembayaran.sql`
- `database/04_sinkronisasi_koin_mesin_detail.sql`

## Cara memasang

1. Backup folder proyek dan database PostgreSQL terlebih dahulu.
2. Salin isi folder `kino_coin_laundry` dari paket ini ke folder proyek yang sudah ada:
   `C:\laragon\www\kino_coin_laundry_backend_integrated\kino_coin_laundry`
3. Jangan menimpa atau menghapus file `.env` yang sudah ada. File `.env` sengaja tidak disertakan dalam paket untuk melindungi password database. Jika belum ada, salin `.env.example` menjadi `.env`, lalu isi kredensial PostgreSQL.
4. Pastikan PHP CLI Laragon mengaktifkan ekstensi `pgsql` dan `pdo_pgsql` tidak lagi diperlukan oleh backend.
5. Jalankan pemeriksaan sintaks PHP (opsional):
   `php -l nama_file.php`
6. Sebelum membuka aplikasi kembali, jalankan migrasi `03` di DBeaver dalam maintenance window.
7. Uji koneksi melalui `test_connection.php`.

## Migrasi database

Jangan jalankan ulang `00_schema_awal.sql` atau `02_operasional_backend.sql` pada database yang sudah digunakan.

Dalam maintenance window (aplikasi tidak menerima transaksi), setelah backup database tersedia dan file kode baru sudah disalin, buka DBeaver, pilih database `kino_coin_laundry_baru`, lalu jalankan:

`database/03_pemisahan_koin_mesin_dan_pembayaran.sql`

Migrasi ini:
- Mengarsipkan kolom `koin_500`, `koin_1000`, dan `nilai_koin` ke `transaksi_pembayaran_koin_legacy`.
- Membuat `jumlah_dibayar` dan menyalin nilai lama `nilai_koin` sebagai nilai awal agar riwayat tidak hilang. Karena kolom lama berasal dari logika koin yang keliru, cocokkan nilai ini dengan bukti transaksi sebelum menganggap laporan historis sebagai laporan keuangan final.
- Mempertahankan `sisa_bayar` dan status pembayaran.
- Menghapus tiga kolom lama dari tabel `transaksi`.
- Menambahkan constraint jumlah koin mesin agar tidak negatif.

Jalankan migrasi hanya setelah memastikan backup berhasil. Jangan jalankan migrasi saat aplikasi sedang menerima transaksi. Data lama tetap diarsipkan, tetapi nilai pembayaran historis perlu direkonsiliasi karena sebelumnya dihitung dari pecahan koin.

## Konfigurasi koin mesin per layanan

Buka menu Admin → Layanan. Isi kolom **Kebutuhan koin mesin** sesuai aturan operasional, misalnya:
- Cuci: 1
- Cuci kering: 2

Pastikan nama layanan yang dipilih benar dan simpan setiap perubahan. Jangan mengisi jumlah koin mesin berdasarkan nominal rupiah.

Setelah seluruh nilai layanan benar, jalankan sekali `database/04_sinkronisasi_koin_mesin_detail.sql` jika ingin memperbaiki jumlah koin pada detail transaksi lama. Script ini memakai konfigurasi layanan saat ini untuk mengoreksi data historis; jangan jalankan ulang setelah konfigurasi layanan berubah karena catatan historis akan ikut berubah.


## Pengujian setelah pemasangan

1. `test_connection.php` harus menunjukkan koneksi PostgreSQL berhasil.
2. Login sebagai pemilik dan kasir.
3. Buka shift kasir.
4. Buat transaksi dengan metode Cash.
5. Buat transaksi dengan metode QRIS.
6. Pastikan rincian transaksi menampilkan kebutuhan koin mesin dari layanan.
7. Pastikan dashboard dan laporan menampilkan pendapatan pembayaran.
8. Pastikan tutup shift membandingkan uang fisik dengan pembayaran Cash saja; QRIS tidak dimasukkan ke uang tunai fisik.
9. Periksa riwayat pelanggan dan status pengerjaan.
10. Uji endpoint API melalui Postman.

## Batas pengujian

Seluruh 44 file PHP di luar folder vendor lolos pemeriksaan sintaks `php -l` pada lingkungan pemeriksaan. Koneksi database dan alur transaksi belum dapat diuji langsung di sini karena ekstensi `pgsql` dan server PostgreSQL Laragon milik pengguna tidak tersedia pada lingkungan pemeriksaan. Karena itu, jalankan pengujian lokal sebelum menggunakan aplikasi untuk transaksi sungguhan.
