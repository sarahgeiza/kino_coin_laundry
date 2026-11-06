-- Kino Coin Laundry: additive operational migration.
-- Run only after the original tables (pengguna, pelanggan, layanan, shift,
-- transaksi, detail_transaksi) have been created.
BEGIN;

ALTER TABLE layanan
    ADD COLUMN IF NOT EXISTS durasi VARCHAR(60) NOT NULL DEFAULT '1 hari',
    ADD COLUMN IF NOT EXISTS aktif BOOLEAN NOT NULL DEFAULT TRUE,
    ADD COLUMN IF NOT EXISTS jumlah_koin INTEGER NOT NULL DEFAULT 0;

ALTER TABLE transaksi
    ADD COLUMN IF NOT EXISTS jumlah_dibayar BIGINT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS uang_kembalian BIGINT NOT NULL DEFAULT 0;

UPDATE transaksi
SET jumlah_dibayar = GREATEST(total_bayar - sisa_bayar, 0)
WHERE jumlah_dibayar = 0 AND total_bayar > sisa_bayar;

ALTER TABLE pelanggan
    ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255);

-- Keep existing shift IDs stable while correcting the four standard schedules.
UPDATE shift SET jam_mulai = '06:00', jam_selesai = '12:00'
WHERE LOWER(BTRIM(nama_shift)) = 'pagi';
UPDATE shift SET jam_mulai = '12:00', jam_selesai = '18:00'
WHERE LOWER(BTRIM(nama_shift)) = 'siang';
UPDATE shift SET jam_mulai = '18:00', jam_selesai = '00:00'
WHERE LOWER(BTRIM(nama_shift)) = 'malam';
INSERT INTO shift (nama_shift, jam_mulai, jam_selesai)
SELECT 'Midnight', '00:00', '06:00'
WHERE NOT EXISTS (
    SELECT 1 FROM shift WHERE LOWER(BTRIM(nama_shift)) = 'midnight'
);

-- Synchronize price list values without inserting duplicate service names.
UPDATE layanan SET harga = 7000, berat_standar = 7.5, jumlah_koin = 1,
                    durasi = '1 hari', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) = 'cuci';
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Cuci', 7000, 7.5, 1, '1 hari', TRUE
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) = 'cuci');

UPDATE layanan SET harga = 10000, berat_standar = 7.5, jumlah_koin = 1,
                    durasi = '1 hari', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) = 'kering';
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Kering', 10000, 7.5, 1, '1 hari', TRUE
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) = 'kering');

UPDATE layanan SET harga = 16000, berat_standar = 7.5, jumlah_koin = 2,
                    durasi = '1 hari', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) = 'cuci kering';
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Cuci Kering', 16000, 7.5, 2, '1 hari', TRUE
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) = 'cuci kering');

UPDATE layanan SET harga = 4000, berat_standar = 7.5, jumlah_koin = 1,
                    durasi = '15 menit', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) = 'extra spin';
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Extra Spin', 4000, 7.5, 1, '15 menit', TRUE
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) = 'extra spin');

UPDATE layanan SET harga = 5000, berat_standar = NULL, jumlah_koin = 1,
                    durasi = '20 menit', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) = 'extra dry';
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Extra Dry', 5000, NULL, 1, '20 menit', TRUE
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) = 'extra dry');

UPDATE layanan SET harga = 20000, berat_standar = 7.5, jumlah_koin = 2,
                    durasi = '1 hari', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) = 'cuci kering drop off';
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Cuci Kering Drop Off', 20000, 7.5, 2, '1 hari', TRUE
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) = 'cuci kering drop off');

UPDATE layanan SET harga = 1500, berat_standar = NULL, jumlah_koin = 0,
                    durasi = '1 hari', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) = 'lipat';
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Lipat', 1500, NULL, 0, '1 hari', TRUE
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) = 'lipat');

UPDATE layanan SET harga = 1500, berat_standar = NULL, jumlah_koin = 0,
                    durasi = 'Langsung', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) = 'sabun';
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Sabun', 1500, NULL, 0, 'Langsung', TRUE
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) = 'sabun');

UPDATE layanan SET harga = 1500, berat_standar = NULL, jumlah_koin = 0,
                    durasi = 'Langsung', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) IN ('softener', 'softner');
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Softener', 1500, NULL, 0, 'Langsung', TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) IN ('softener', 'softner')
);

UPDATE layanan SET harga = 2000, berat_standar = NULL, jumlah_koin = 0,
                    durasi = 'Langsung', aktif = TRUE
WHERE LOWER(BTRIM(nama_layanan)) = 'kresek';
INSERT INTO layanan (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
SELECT 'Kresek', 2000, NULL, 0, 'Langsung', TRUE
WHERE NOT EXISTS (SELECT 1 FROM layanan WHERE LOWER(BTRIM(nama_layanan)) = 'kresek');

ALTER TABLE transaksi
    ADD COLUMN IF NOT EXISTS no_invoice VARCHAR(40),
    ADD COLUMN IF NOT EXISTS sisa_bayar BIGINT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS status_pembayaran VARCHAR(20) NOT NULL DEFAULT 'belum_lunas',
    ADD COLUMN IF NOT EXISTS status_pengerjaan VARCHAR(30) NOT NULL DEFAULT 'antri',
    ADD COLUMN IF NOT EXISTS id_shift_operasional BIGINT;

CREATE TABLE IF NOT EXISTS shift_operasional (
    id_shift_operasional BIGSERIAL PRIMARY KEY,
    id_kasir INTEGER NOT NULL REFERENCES pengguna(id_pengguna),
    id_shift INTEGER NOT NULL REFERENCES shift(id_shift),
    mulai_pada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ditutup_pada TIMESTAMPTZ,
    total_sistem BIGINT NOT NULL DEFAULT 0,
    total_fisik BIGINT,
    selisih BIGINT,
    catatan TEXT,
    status VARCHAR(16) NOT NULL DEFAULT 'aktif'
        CHECK (status IN ('aktif','ditutup')),
    CONSTRAINT ck_shift_operasional_fisik CHECK (total_fisik IS NULL OR total_fisik >= 0)
);

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conrelid = 'layanan'::regclass
          AND conname = 'ck_layanan_jumlah_koin_nonneg'
    ) THEN
        ALTER TABLE layanan
            ADD CONSTRAINT ck_layanan_jumlah_koin_nonneg CHECK (jumlah_koin >= 0);
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conrelid = 'transaksi'::regclass
          AND conname = 'ck_transaksi_pembayaran_nonnegatif'
    ) THEN
        ALTER TABLE transaksi
            ADD CONSTRAINT ck_transaksi_pembayaran_nonnegatif
            CHECK (jumlah_dibayar >= 0 AND uang_kembalian >= 0 AND sisa_bayar >= 0);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_transaksi_shift_operasional') THEN
        ALTER TABLE transaksi ADD CONSTRAINT fk_transaksi_shift_operasional
            FOREIGN KEY (id_shift_operasional) REFERENCES shift_operasional(id_shift_operasional);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_transaksi_status_pembayaran') THEN
        ALTER TABLE transaksi ADD CONSTRAINT ck_transaksi_status_pembayaran
            CHECK (status_pembayaran IN ('lunas','sebagian','belum_lunas'));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_transaksi_status_pengerjaan') THEN
        ALTER TABLE transaksi ADD CONSTRAINT ck_transaksi_status_pengerjaan
            CHECK (status_pengerjaan IN ('antri','proses','siap_diambil','selesai','diambil'));
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS uq_shift_operasional_kasir_aktif
    ON shift_operasional(id_kasir) WHERE status = 'aktif';

CREATE INDEX IF NOT EXISTS idx_shift_operasional_mulai
    ON shift_operasional(mulai_pada DESC);

CREATE INDEX IF NOT EXISTS idx_transaksi_invoice
    ON transaksi(no_invoice);

CREATE UNIQUE INDEX IF NOT EXISTS uq_transaksi_invoice
    ON transaksi(no_invoice) WHERE no_invoice IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_transaksi_status_pengerjaan
    ON transaksi(status_pengerjaan);

CREATE INDEX IF NOT EXISTS idx_transaksi_shift_operasional
    ON transaksi(id_shift_operasional);

CREATE INDEX IF NOT EXISTS idx_layanan_aktif
    ON layanan(aktif);

COMMIT;
