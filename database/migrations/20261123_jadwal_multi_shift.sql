-- Izinkan satu kasir mengambil lebih dari satu shift pada tanggal yang sama.
-- Unik per (kasir, shift, tanggal), bukan per (kasir, tanggal).
ALTER TABLE jadwal_shift DROP CONSTRAINT IF EXISTS uq_jadwal_shift_kasir_tanggal;
DROP INDEX IF EXISTS uq_jadwal_shift_kasir_tanggal;
CREATE UNIQUE INDEX IF NOT EXISTS uq_jadwal_shift_kasir_shift_tanggal
    ON jadwal_shift (id_kasir, id_shift, tanggal);
