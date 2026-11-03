-- Menghapus kolom pembayaran pecahan lama.
-- Backup database terlebih dahulu sebelum menjalankan migrasi ini.
-- Kolom jumlah_koin pada layanan dan detail_transaksi tidak diubah.
BEGIN;

ALTER TABLE transaksi
    DROP COLUMN IF EXISTS koin_500,
    DROP COLUMN IF EXISTS koin_1000,
    DROP COLUMN IF EXISTS nilai_koin;

COMMIT;
