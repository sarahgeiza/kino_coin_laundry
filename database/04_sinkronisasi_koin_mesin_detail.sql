-- Jalankan satu kali SETELAH jumlah_koin pada semua layanan sudah dikonfigurasi.
-- Script ini memperbaiki nilai historis detail_transaksi agar mengikuti jumlah
-- koin mesin dari layanan, bukan angka pecahan uang yang pernah salah tersimpan.
BEGIN;

UPDATE detail_transaksi AS detail
SET jumlah_koin = layanan.jumlah_koin
FROM layanan
WHERE layanan.id_layanan = detail.id_layanan;

COMMIT;
