-- Add database fields used by customer cards and cashier shift assignments.
BEGIN;

ALTER TABLE pelanggan
    ADD COLUMN IF NOT EXISTS kode_kartu VARCHAR(30);

UPDATE pelanggan
SET kode_kartu = 'KC-P-' || LPAD(id_pelanggan::text, 4, '0')
WHERE kode_kartu IS NULL OR BTRIM(kode_kartu) = '';

CREATE UNIQUE INDEX IF NOT EXISTS uq_pelanggan_kode_kartu
    ON pelanggan(kode_kartu);

CREATE TABLE IF NOT EXISTS jadwal_shift (
    id_jadwal BIGSERIAL PRIMARY KEY,
    id_shift INTEGER NOT NULL REFERENCES shift(id_shift),
    id_kasir INTEGER NOT NULL REFERENCES pengguna(id_pengguna),
    tanggal DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'dijadwalkan'
        CHECK (status IN ('dijadwalkan', 'dimulai', 'selesai', 'dibatalkan')),
    dibuat_oleh INTEGER NOT NULL REFERENCES pengguna(id_pengguna),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_jadwal_shift_kasir_tanggal UNIQUE (id_kasir, tanggal)
);

ALTER TABLE shift_operasional
    ADD COLUMN IF NOT EXISTS id_jadwal BIGINT;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'fk_shift_operasional_jadwal'
          AND conrelid = 'shift_operasional'::regclass
    ) THEN
        ALTER TABLE shift_operasional
            ADD CONSTRAINT fk_shift_operasional_jadwal
            FOREIGN KEY (id_jadwal) REFERENCES jadwal_shift(id_jadwal);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_jadwal_shift_tanggal
    ON jadwal_shift(tanggal, id_shift);

COMMIT;
