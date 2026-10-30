BEGIN;

CREATE TABLE IF NOT EXISTS akses_pelanggan (
    id_pelanggan INTEGER PRIMARY KEY
        REFERENCES pelanggan (id_pelanggan) ON DELETE CASCADE,
    kode_hash VARCHAR(255) NOT NULL,
    berlaku_sampai TIMESTAMP NOT NULL
);

DO $$
BEGIN
    IF EXISTS (
        SELECT no_hp
        FROM pelanggan
        GROUP BY no_hp
        HAVING COUNT(*) > 1
    ) THEN
        RAISE EXCEPTION
            'Nomor HP duplikat ditemukan pada pelanggan; rapikan data sebelum menjalankan migrasi QR member.';
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS idx_pelanggan_no_hp_unique
    ON pelanggan (no_hp);

COMMIT;
