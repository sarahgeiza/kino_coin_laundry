-- Jalankan pada database kino_coin_laundry yang sudah ada.
-- Periksa dahulu bahwa constraint belum dibuat; blok ini aman diulang.
BEGIN;
DO $$ BEGIN
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='cek_total_bayar' AND conrelid='transaksi'::regclass) THEN
   ALTER TABLE transaksi ADD CONSTRAINT cek_total_bayar CHECK (total_bayar >= 0);
 END IF;
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='cek_harga_satuan' AND conrelid='detail_transaksi'::regclass) THEN
   ALTER TABLE detail_transaksi ADD CONSTRAINT cek_harga_satuan CHECK (harga_satuan >= 0);
 END IF;
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='cek_berat_standar' AND conrelid='layanan'::regclass) THEN
   ALTER TABLE layanan ADD CONSTRAINT cek_berat_standar CHECK (berat_standar IS NULL OR berat_standar > 0);
 END IF;
END $$;
CREATE INDEX IF NOT EXISTS idx_transaksi_tanggal ON transaksi(tanggal_transaksi);
CREATE INDEX IF NOT EXISTS idx_transaksi_shift ON transaksi(id_shift);
CREATE INDEX IF NOT EXISTS idx_transaksi_kasir ON transaksi(id_kasir);
CREATE INDEX IF NOT EXISTS idx_transaksi_pelanggan ON transaksi(id_pelanggan);
CREATE INDEX IF NOT EXISTS idx_detail_transaksi_layanan ON detail_transaksi(id_layanan);
CREATE TABLE IF NOT EXISTS akses_pelanggan (
 id_pelanggan INTEGER PRIMARY KEY REFERENCES pelanggan(id_pelanggan) ON DELETE CASCADE,
 kode_hash VARCHAR(255) NOT NULL,
 berlaku_sampai TIMESTAMP NOT NULL
);
DO $$ BEGIN
 IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname='cek_berat_detail_max' AND conrelid='detail_transaksi'::regclass) THEN
  ALTER TABLE detail_transaksi ADD CONSTRAINT cek_berat_detail_max CHECK (berat IS NULL OR berat <= 7.5);
 END IF;
END $$;
COMMIT;
-- PENTING: password 'sementara' masih plaintext, harus diganti dengan hash dari PHP.
-- Contoh di terminal: php -r "echo password_hash('PasswordBaruKuat!', PASSWORD_DEFAULT), PHP_EOL;"
-- Kemudian: UPDATE pengguna SET password='<HASH_DARI_PHP>' WHERE username='pemilik';
-- Ulangi untuk kasir dengan password berbeda. Jangan commit password/hash nyata ke Git.
-- Tidak perlu mengulang CREATE TABLE atau INSERT seed yang sudah pernah dijalankan.
