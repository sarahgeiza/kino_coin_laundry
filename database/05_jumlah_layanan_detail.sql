-- Store piece quantities separately from machine weight.
BEGIN;

ALTER TABLE detail_transaksi
    ADD COLUMN IF NOT EXISTS jumlah INTEGER NOT NULL DEFAULT 1;

DO $$ BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conrelid = 'detail_transaksi'::regclass
          AND conname = 'ck_detail_transaksi_jumlah_positif'
    ) THEN
        ALTER TABLE detail_transaksi
            ADD CONSTRAINT ck_detail_transaksi_jumlah_positif CHECK (jumlah > 0);
    END IF;
END $$;

COMMIT;
