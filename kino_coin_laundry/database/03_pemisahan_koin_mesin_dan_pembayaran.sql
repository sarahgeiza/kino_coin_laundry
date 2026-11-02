-- Kino Coin Laundry: pemisahan koin mesin dari pembayaran.
-- Jalankan SETELAH file PHP versi Cash/QRIS dipasang dan diuji pada salinan proyek.
-- Data kolom lama diarsipkan sebelum kolom transaksi dihapus.
BEGIN;

CREATE TABLE IF NOT EXISTS transaksi_pembayaran_koin_legacy (
    id_transaksi INTEGER PRIMARY KEY,
    koin_500 INTEGER NOT NULL DEFAULT 0,
    koin_1000 INTEGER NOT NULL DEFAULT 0,
    nilai_koin BIGINT NOT NULL DEFAULT 0,
    archived_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'transaksi' AND column_name = 'koin_500'
    ) AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'transaksi' AND column_name = 'koin_1000'
    ) AND EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = current_schema()
          AND table_name = 'transaksi' AND column_name = 'nilai_koin'
    ) THEN
        INSERT INTO transaksi_pembayaran_koin_legacy
            (id_transaksi, koin_500, koin_1000, nilai_koin)
        SELECT id_transaksi, koin_500, koin_1000, nilai_koin
        FROM transaksi
        ON CONFLICT (id_transaksi) DO NOTHING;
    END IF;
END $$;

ALTER TABLE transaksi
    ADD COLUMN IF NOT EXISTS jumlah_dibayar BIGINT NOT NULL DEFAULT 0;

-- Nilai pembayaran lama dipindahkan dari nilai koin rupiah yang sebelumnya dicatat.
-- Dengan demikian data pembayaran lama tetap dapat ditelusuri setelah kolom koin dihapus.
UPDATE transaksi t
SET jumlah_dibayar = LEAST(GREATEST(legacy.nilai_koin, 0), t.total_bayar),
    sisa_bayar = GREATEST(t.total_bayar - LEAST(GREATEST(legacy.nilai_koin, 0), t.total_bayar), 0),
    status_pembayaran = CASE
        WHEN LEAST(GREATEST(legacy.nilai_koin, 0), t.total_bayar) >= t.total_bayar THEN 'lunas'
        WHEN LEAST(GREATEST(legacy.nilai_koin, 0), t.total_bayar) > 0 THEN 'sebagian'
        ELSE 'belum_lunas'
    END
FROM transaksi_pembayaran_koin_legacy legacy
WHERE legacy.id_transaksi = t.id_transaksi
  AND t.jumlah_dibayar = 0;

ALTER TABLE transaksi
    DROP CONSTRAINT IF EXISTS ck_transaksi_koin_nonneg,
    DROP CONSTRAINT IF EXISTS ck_transaksi_nilai_nonneg,
    DROP CONSTRAINT IF EXISTS ck_transaksi_sisa_bayar_nonneg,
    DROP COLUMN IF EXISTS koin_500,
    DROP COLUMN IF EXISTS koin_1000,
    DROP COLUMN IF EXISTS nilai_koin;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'ck_transaksi_sisa_bayar_nonneg'
          AND conrelid = 'transaksi'::regclass
    ) THEN
        ALTER TABLE transaksi
            ADD CONSTRAINT ck_transaksi_sisa_bayar_nonneg CHECK (jumlah_dibayar >= 0 AND sisa_bayar >= 0 AND jumlah_dibayar <= total_bayar);
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'ck_layanan_jumlah_koin_nonneg'
          AND conrelid = 'layanan'::regclass
    ) THEN
        ALTER TABLE layanan
            ADD CONSTRAINT ck_layanan_jumlah_koin_nonneg CHECK (jumlah_koin >= 0);
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'ck_detail_jumlah_koin_nonneg'
          AND conrelid = 'detail_transaksi'::regclass
    ) THEN
        ALTER TABLE detail_transaksi
            ADD CONSTRAINT ck_detail_jumlah_koin_nonneg CHECK (jumlah_koin >= 0);
    END IF;
END $$;

COMMIT;
