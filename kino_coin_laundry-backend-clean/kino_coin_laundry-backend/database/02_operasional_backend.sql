-- Kino Coin Laundry: additive operational migration.
-- Run only after the original tables (pengguna, pelanggan, layanan, shift,
-- transaksi, detail_transaksi) have been created.
BEGIN;

ALTER TABLE layanan
    ADD COLUMN IF NOT EXISTS durasi VARCHAR(60) NOT NULL DEFAULT '1 hari',
    ADD COLUMN IF NOT EXISTS aktif BOOLEAN NOT NULL DEFAULT TRUE;

ALTER TABLE pelanggan
    ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255);

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
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_transaksi_koin_nonneg') THEN
        ALTER TABLE transaksi ADD CONSTRAINT ck_transaksi_koin_nonneg;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_transaksi_nilai_nonneg') THEN
        ALTER TABLE transaksi ADD CONSTRAINT ck_transaksi_nilai_nonneg;
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
