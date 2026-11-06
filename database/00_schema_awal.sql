-- Initial PostgreSQL schema for Kino Coin Laundry.
-- Safe for a fresh database. Existing tables are not dropped or truncated.
BEGIN;

CREATE TABLE IF NOT EXISTS pengguna (
    id_pengguna SERIAL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    role VARCHAR(20) NOT NULL CHECK (role IN ('pemilik','kasir')),
    password VARCHAR(255) NOT NULL,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pelanggan (
    id_pelanggan SERIAL PRIMARY KEY,
    nama_pelanggan VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20) NOT NULL UNIQUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS layanan (
    id_layanan SERIAL PRIMARY KEY,
    nama_layanan VARCHAR(50) NOT NULL,
    harga INTEGER NOT NULL CHECK (harga > 0),
    berat_standar NUMERIC(5,2),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shift (
    id_shift SERIAL PRIMARY KEY,
    nama_shift VARCHAR(20) NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL
);

CREATE TABLE IF NOT EXISTS transaksi (
    id_transaksi SERIAL PRIMARY KEY,
    id_kasir INTEGER NOT NULL REFERENCES pengguna(id_pengguna),
    tanggal_transaksi TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_pelanggan INTEGER NOT NULL REFERENCES pelanggan(id_pelanggan),
    id_shift INTEGER NOT NULL REFERENCES shift(id_shift),
    jenis_pembayaran VARCHAR(10) NOT NULL CHECK (jenis_pembayaran IN ('cash','qris')),
    total_bayar BIGINT NOT NULL CHECK (total_bayar >= 0)
);

CREATE TABLE IF NOT EXISTS detail_transaksi (
    id_detail SERIAL PRIMARY KEY,
    id_transaksi INTEGER NOT NULL REFERENCES transaksi(id_transaksi) ON DELETE CASCADE,
    id_layanan INTEGER NOT NULL REFERENCES layanan(id_layanan),
    berat NUMERIC(5,2),
    jumlah INTEGER NOT NULL DEFAULT 1 CHECK (jumlah > 0),
    jumlah_koin INTEGER NOT NULL DEFAULT 0 CHECK (jumlah_koin >= 0),
    harga_satuan INTEGER NOT NULL CHECK (harga_satuan >= 0),
    subtotal BIGINT NOT NULL CHECK (subtotal >= 0)
);

INSERT INTO shift (nama_shift,jam_mulai,jam_selesai)
SELECT 'Pagi','06:00','12:00'
WHERE NOT EXISTS (SELECT 1 FROM shift WHERE nama_shift = 'Pagi');
INSERT INTO shift (nama_shift,jam_mulai,jam_selesai)
SELECT 'Siang','12:00','18:00'
WHERE NOT EXISTS (SELECT 1 FROM shift WHERE nama_shift = 'Siang');
INSERT INTO shift (nama_shift,jam_mulai,jam_selesai)
SELECT 'Malam','18:00','00:00'
WHERE NOT EXISTS (SELECT 1 FROM shift WHERE nama_shift = 'Malam');
INSERT INTO shift (nama_shift,jam_mulai,jam_selesai)
SELECT 'Midnight','00:00','06:00'
WHERE NOT EXISTS (SELECT 1 FROM shift WHERE nama_shift = 'Midnight');

CREATE INDEX IF NOT EXISTS idx_transaksi_tanggal ON transaksi(tanggal_transaksi);
CREATE INDEX IF NOT EXISTS idx_transaksi_pelanggan ON transaksi(id_pelanggan);
CREATE INDEX IF NOT EXISTS idx_transaksi_kasir ON transaksi(id_kasir);
CREATE INDEX IF NOT EXISTS idx_transaksi_shift ON transaksi(id_shift);
CREATE INDEX IF NOT EXISTS idx_detail_transaksi_transaksi ON detail_transaksi(id_transaksi);

COMMIT;
