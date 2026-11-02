# Audit dan Catatan Implementasi Kino Coin Laundry

## Ringkasan audit awal

Source ZIP berisi 55 file aplikasi di luar metadata Git. Fondasi PHP Native/PostgreSQL dan sebagian REST API telah tersedia, tetapi beberapa halaman operasional masih bergantung pada array session yang dibuat oleh `includes/data.php`.

Temuan awal yang paling penting:

- `includes/data.php` menginisialisasi layanan, transaksi, rekap shift, dan antrean contoh ke `$_SESSION`.
- Dashboard pemilik dan laporan menampilkan nilai/grafik statis.
- Form manajemen layanan menulis ke session, bukan PostgreSQL.
- Dashboard kasir dan penutupan shift menghitung data dari session dan memiliki fallback angka contoh.
- Form transaksi belum menyimpan transaksi ke PostgreSQL dari frontend.
- Login halaman awal hanya menyimpan role/username pilihan browser; password tidak diverifikasi.
- Halaman pelanggan menggunakan identitas pelanggan hardcoded dan riwayat difilter berdasarkan nama.
- Endpoint tracking riwayat berdasarkan nomor HP dapat membuka data tanpa autentikasi akun.
- Backend telah memiliki router API, controller, model, koneksi PostgreSQL, middleware session, migration parsial, dan Postman Collection, tetapi kontrak beberapa endpoint belum sesuai dengan form frontend.

## Implementasi yang dilakukan

### Database dan konfigurasi
- Menambahkan `database/00_schema_awal.sql` untuk skema inti instalasi baru tanpa `DROP`/`TRUNCATE`.
- Menambahkan `database/02_operasional_backend.sql` untuk kolom invoice, pembayaran koin, status transaksi, password pelanggan, layanan aktif/durasi, dan tabel `shift_operasional`.
- Mempertahankan migration `01_migrasi_database.sql`.
- Memperbaiki petunjuk nama file konfigurasi contoh agar sesuai dengan `database.local.php`.
- Mempertahankan `database.local.php` hanya pada working copy; file tersebut tidak disertakan dalam paket distribusi.

### Authentication dan keamanan
- Login frontend sekarang memanggil API dan role diambil dari database, bukan pilihan role browser.
- Login mendukung akun pemilik/kasir dari `pengguna` dan pelanggan dari nomor WhatsApp + password.
- Menambahkan registrasi akun pelanggan.
- Menambahkan regenerasi session ID, cookie HttpOnly/SameSite, session strict mode, CSRF untuk operasi tulis, pembatasan percobaan login, dan logout POST.
- Halaman admin, kasir, dan pelanggan memerlukan session dengan role yang benar.
- Endpoint riwayat pelanggan tidak lagi menerima nomor HP sebagai bukti identitas; data dibatasi ke `customer_id` pada session.
- Membatasi akses dashboard/rekap owner dan resource pelanggan.
- Mengamankan skrip diagnostik/password agar hanya berjalan melalui CLI; menghapus endpoint `phpinfo()`.

### Transaksi
- Form transaksi kasir dihubungkan ke REST API.
- Backend menghitung ulang harga per kg, subtotal, total, nilai koin, sisa bayar, status pembayaran, dan invoice.
- Layanan harus aktif, nomor WhatsApp divalidasi, berat positif dan maksimal 7,5 kg, jumlah koin tidak negatif.
- Pembuatan pelanggan/transaksi/detail dilakukan dalam satu transaksi PostgreSQL.
- Transaksi membutuhkan shift operasional aktif.
- Menambahkan endpoint pembaruan status pengerjaan.
- Daftar dan detail transaksi menggunakan data PostgreSQL, bukan array session.

### Shift
- Menambahkan pembukaan, pemeriksaan, dan penutupan shift operasional.
- Indeks unik parsial mencegah kasir membuka lebih dari satu shift aktif.
- Penutupan shift menyimpan nilai sistem, jumlah fisik, selisih, catatan, dan waktu penutupan.
- Nilai sistem rekonsiliasi didefinisikan sebagai nilai koin yang tercatat; pendapatan bruto dihitung terpisah dari total tagihan.

### Dashboard, layanan, laporan, pelanggan
- Data layanan, transaksi, rekap shift, dan antrean pada halaman dirender dari query PostgreSQL.
- Dashboard kasir menggunakan transaksi pada shift aktif.
- Dashboard pemilik memakai agregasi harian, mingguan, bulanan, dan koin dari database.
- Manajemen layanan terhubung ke PostgreSQL untuk tambah, ubah, aktif/nonaktif, dan hapus (jika belum digunakan).
- Laporan memiliki filter rentang tanggal, shift, dan kasir serta agregasi detail transaksi untuk menghindari hitung pendapatan ganda.
- Dashboard dan riwayat pelanggan hanya menampilkan data pelanggan yang login.

### Dokumentasi dan Postman
- README diperbarui dengan persyaratan, konfigurasi, urutan migration, pembuatan akun, API, hak akses, dan pengujian.
- Postman Collection diperbarui untuk kontrak transaksi dan autentikasi pelanggan baru serta operasi shift.

## Asumsi bisnis yang dipakai

1. Berat total dalam satu transaksi dibatasi maksimal 7,5 kg (bukan 7,5 kg per baris detail).
2. Nilai koin tidak boleh melebihi total tagihan. Jika lebih, transaksi ditolak; tidak ada perhitungan kembalian.
3. `jenis_pembayaran` tetap memakai nilai `cash` atau `qris` sesuai kontrak/schema API lama. Nilai koin dicatat terpisah.
4. Sisa pembayaran disimpan sebagai `sisa_bayar`; status menjadi `lunas`, `sebagian`, atau `belum_lunas`.
5. Rekonsiliasi kas shift membandingkan total nilai koin yang tercatat dengan nilai fisik yang dimasukkan. Jika operasional juga menerima pembayaran tunai/QRIS di luar koin, skema pembayaran tambahan diperlukan agar rekonsiliasi kas mencakup semua kanal.
6. Status pengerjaan menggunakan `antri`, `proses`, `siap_diambil`, `selesai`, dan `diambil`.

Asumsi 2 dan 5 perlu dikonfirmasi pemilik proses bisnis sebelum dipakai di produksi.

## Hasil pemeriksaan yang dapat dilakukan di workspace ini

- `php -l` pada seluruh file PHP: lulus.
- `node --check js/app.js`: lulus.
- Pemeriksaan sintaks JavaScript inline pada halaman PHP: lulus.
- Tidak ditemukan lagi array operasional `$_SESSION['services']`, `transactions`, `shifts_recap`, atau `antrian`.

## Pengujian yang masih terblokir

Workspace eksekusi saat ini tidak memiliki ekstensi PHP `pgsql` dan tidak menyediakan `psql`/server PostgreSQL. Karena itu koneksi database, eksekusi migration, request API yang memerlukan database, dan pengujian transaksi/shift end-to-end belum dapat dijalankan di sini. Jangan anggap seluruh 20 skenario integrasi telah lulus hanya berdasarkan lint sintaks.

Sebelum produksi:
1. Aktifkan ekstensi PHP `pgsql` pada PHP CLI dan web server.
2. Konfigurasikan `backend/config/database.local.php`.
3. Backup database yang sudah ada.
4. Tinjau skema aktual, lalu jalankan migration sesuai urutan pada README.
5. Jalankan seluruh skenario Postman pada database uji.
6. Periksa asumsi pembayaran/rekonsiliasi dan lakukan uji paralel untuk pembukaan shift serta pembuatan transaksi.
