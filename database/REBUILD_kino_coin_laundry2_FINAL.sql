-- KINO COIN LAUNDRY

BEGIN;

DO $$
BEGIN
    IF current_database() <> 'kino_coin_laundry2' THEN
        RAISE EXCEPTION 'Wrong database: expected kino_coin_laundry2, connected to %', current_database();
    END IF;
END
$$;

SET LOCAL search_path = public;

-- Drop dependencies first. akses_pelanggan is a legacy helper table if present.
DROP TABLE IF EXISTS public.akses_pelanggan;
DROP TABLE IF EXISTS public.detail_transaksi;
DROP TABLE IF EXISTS public.transaksi;
DROP TABLE IF EXISTS public.shift_operasional;
DROP TABLE IF EXISTS public.jadwal_shift;
DROP TABLE IF EXISTS public.pelanggan;
DROP TABLE IF EXISTS public.layanan;
DROP TABLE IF EXISTS public.shift;
DROP TABLE IF EXISTS public.pengguna;

CREATE TABLE public.pengguna (
    id_pengguna SERIAL PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    username    VARCHAR(50) NOT NULL UNIQUE,
    role        VARCHAR(20) NOT NULL CHECK (role IN ('pemilik', 'kasir')),
    password    VARCHAR(255) NOT NULL,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE public.pelanggan (
    id_pelanggan   SERIAL PRIMARY KEY,
    nama_pelanggan VARCHAR(100) NOT NULL,
    no_hp          VARCHAR(20) NOT NULL UNIQUE,
    password_hash  VARCHAR(255),
    kode_kartu     VARCHAR(64) UNIQUE,
    dibuat_pada    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_pelanggan_no_hp_format CHECK (no_hp ~ '^\+?[0-9]{9,15}$')
);

CREATE TABLE public.layanan (
    id_layanan    SERIAL PRIMARY KEY,
    nama_layanan  VARCHAR(50) NOT NULL,
    harga         INTEGER NOT NULL CHECK (harga > 0),
    berat_standar NUMERIC(5,2),
    durasi        VARCHAR(60) NOT NULL DEFAULT '1 hari',
    aktif         BOOLEAN NOT NULL DEFAULT TRUE,
    jumlah_koin   INTEGER NOT NULL DEFAULT 0,
    dibuat_pada   TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_layanan_jumlah_koin CHECK (jumlah_koin BETWEEN 0 AND 100),
    CONSTRAINT ck_layanan_berat_standar CHECK (berat_standar IS NULL OR berat_standar > 0)
);

CREATE TABLE public.shift (
    id_shift    SERIAL PRIMARY KEY,
    nama_shift  VARCHAR(30) NOT NULL,
    jam_mulai   TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    CONSTRAINT ck_shift_durasi CHECK (jam_mulai <> jam_selesai)
);

CREATE TABLE public.jadwal_shift (
    id_jadwal   BIGSERIAL PRIMARY KEY,
    id_shift    INTEGER NOT NULL REFERENCES public.shift(id_shift),
    id_kasir    INTEGER NOT NULL REFERENCES public.pengguna(id_pengguna),
    tanggal     DATE NOT NULL,
    status      VARCHAR(20) NOT NULL DEFAULT 'dijadwalkan'
                CHECK (status IN ('dijadwalkan', 'dimulai', 'selesai', 'dibatalkan')),
    dibuat_oleh INTEGER NOT NULL REFERENCES public.pengguna(id_pengguna),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Required by admin/master.php ON CONFLICT (id_kasir, tanggal).
    CONSTRAINT uq_jadwal_shift_kasir_tanggal UNIQUE (id_kasir, tanggal)
);

CREATE TABLE public.shift_operasional (
    id_shift_operasional BIGSERIAL PRIMARY KEY,
    id_kasir             INTEGER NOT NULL REFERENCES public.pengguna(id_pengguna),
    id_shift             INTEGER NOT NULL REFERENCES public.shift(id_shift),
    id_jadwal            BIGINT REFERENCES public.jadwal_shift(id_jadwal),
    mulai_pada           TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ditutup_pada         TIMESTAMPTZ,
    total_sistem         BIGINT NOT NULL DEFAULT 0,
    total_fisik          BIGINT,
    selisih              BIGINT,
    catatan              TEXT,
    status               VARCHAR(16) NOT NULL DEFAULT 'aktif'
                         CHECK (status IN ('aktif', 'ditutup')),
    CONSTRAINT ck_shift_operasional_fisik CHECK (total_fisik IS NULL OR total_fisik >= 0),
    CONSTRAINT ck_shift_operasional_waktu
        CHECK (ditutup_pada IS NULL OR ditutup_pada >= mulai_pada)
);

CREATE TABLE public.transaksi (
    id_transaksi         SERIAL PRIMARY KEY,
    id_kasir             INTEGER NOT NULL REFERENCES public.pengguna(id_pengguna),
    tanggal_transaksi    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_pelanggan         INTEGER NOT NULL REFERENCES public.pelanggan(id_pelanggan),
    id_shift             INTEGER NOT NULL REFERENCES public.shift(id_shift),
    id_shift_operasional BIGINT REFERENCES public.shift_operasional(id_shift_operasional),
    jenis_pembayaran     VARCHAR(10) NOT NULL
                         CHECK (jenis_pembayaran IN ('cash', 'qris')),
    total_bayar          BIGINT NOT NULL CHECK (total_bayar >= 0),
    jumlah_dibayar       BIGINT NOT NULL DEFAULT 0,
    sisa_bayar           BIGINT NOT NULL DEFAULT 0,
    uang_kembalian       BIGINT NOT NULL DEFAULT 0,
    status_pembayaran    VARCHAR(20) NOT NULL DEFAULT 'belum_lunas'
                         CHECK (status_pembayaran IN ('lunas', 'sebagian', 'belum_lunas')),
    status_pengerjaan    VARCHAR(30) NOT NULL DEFAULT 'antri'
                         CHECK (status_pengerjaan IN ('antri', 'proses', 'siap_diambil', 'selesai', 'diambil')),
    no_invoice           VARCHAR(40),
    CONSTRAINT ck_transaksi_pembayaran_nonnegatif
        CHECK (jumlah_dibayar >= 0 AND sisa_bayar >= 0 AND uang_kembalian >= 0),
    CONSTRAINT ck_transaksi_sisa_bayar
        CHECK (sisa_bayar = GREATEST(total_bayar - jumlah_dibayar, 0)),
    CONSTRAINT ck_transaksi_kembalian
        CHECK (uang_kembalian = GREATEST(jumlah_dibayar - total_bayar, 0)),
    CONSTRAINT ck_transaksi_qris_tidak_lebih_bayar
        CHECK (jenis_pembayaran <> 'qris' OR jumlah_dibayar <= total_bayar),
    CONSTRAINT ck_transaksi_status_pembayaran
        CHECK (
            (status_pembayaran = 'lunas' AND sisa_bayar = 0 AND jumlah_dibayar >= total_bayar)
            OR (status_pembayaran = 'sebagian' AND jumlah_dibayar > 0 AND sisa_bayar > 0)
            OR (status_pembayaran = 'belum_lunas' AND jumlah_dibayar = 0 AND sisa_bayar = total_bayar)
        )
);

CREATE TABLE public.detail_transaksi (
    id_detail    SERIAL PRIMARY KEY,
    id_transaksi INTEGER NOT NULL REFERENCES public.transaksi(id_transaksi) ON DELETE CASCADE,
    id_layanan   INTEGER NOT NULL REFERENCES public.layanan(id_layanan),
    berat        NUMERIC(5,2),
    jumlah       INTEGER NOT NULL DEFAULT 1 CHECK (jumlah > 0),
    -- Snapshot total machine tokens used for this detail row; not currency.
    jumlah_koin  INTEGER NOT NULL CHECK (jumlah_koin >= 0),
    harga_satuan INTEGER NOT NULL CHECK (harga_satuan >= 0),
    subtotal     BIGINT NOT NULL CHECK (subtotal >= 0),
    CONSTRAINT ck_detail_transaksi_berat CHECK (berat IS NULL OR berat > 0)
);

-- At most one open shift per cashier; enforced even under concurrent requests.
CREATE UNIQUE INDEX uq_shift_operasional_kasir_aktif
    ON public.shift_operasional (id_kasir) WHERE status = 'aktif';
CREATE UNIQUE INDEX uq_transaksi_invoice
    ON public.transaksi (no_invoice) WHERE no_invoice IS NOT NULL;

CREATE INDEX idx_jadwal_shift_tanggal ON public.jadwal_shift (tanggal, id_shift);
CREATE INDEX idx_shift_operasional_mulai ON public.shift_operasional (mulai_pada DESC);
CREATE INDEX idx_transaksi_tanggal ON public.transaksi (tanggal_transaksi);
CREATE INDEX idx_transaksi_pelanggan ON public.transaksi (id_pelanggan);
CREATE INDEX idx_transaksi_kasir ON public.transaksi (id_kasir);
CREATE INDEX idx_transaksi_shift ON public.transaksi (id_shift);
CREATE INDEX idx_transaksi_shift_operasional ON public.transaksi (id_shift_operasional);
CREATE INDEX idx_transaksi_status_pengerjaan ON public.transaksi (status_pengerjaan);
CREATE INDEX idx_detail_transaksi_transaksi ON public.detail_transaksi (id_transaksi);
CREATE INDEX idx_detail_transaksi_layanan ON public.detail_transaksi (id_layanan);
CREATE INDEX idx_layanan_aktif ON public.layanan (aktif);

INSERT INTO public.shift (nama_shift, jam_mulai, jam_selesai) VALUES
    ('Pagi', '06:00', '12:00'),
    ('Siang', '12:00', '18:00'),
    ('Malam', '18:00', '00:00'),
    ('Midnight', '00:00', '06:00');

INSERT INTO public.layanan
    (nama_layanan, harga, berat_standar, jumlah_koin, durasi, aktif)
VALUES
    ('Cuci',                  7000, 7.50, 1, '1 hari', TRUE),
    ('Kering',               10000, 7.50, 1, '1 hari', TRUE),
    ('Cuci Kering',          16000, 7.50, 2, '1 hari', TRUE),
    ('Extra Spin',            4000, 7.50, 1, '15 menit', TRUE),
    ('Extra Dry',             5000, NULL, 1, '20 menit', TRUE),
    ('Cuci Kering Drop Off', 20000, 7.50, 2, '1 hari', TRUE),
    ('Lipat',                 1500, NULL, 0, '1 hari', TRUE),
    ('Sabun',                 1500, NULL, 0, 'Langsung', TRUE),
    ('Softener',              1500, NULL, 0, 'Langsung', TRUE),
    ('Kresek',                2000, NULL, 0, 'Langsung', TRUE);

-- Tidak ada akun pemilik maupun kasir yang dibuat secara otomatis.
-- Setelah terhubung melalui skrip PHP yang pengaturannya sudah diamankan:
-- $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
-- pg_query_params($conn,
--     'INSERT INTO public.pengguna (nama, username, role, password) VALUES ($1,$2,$3,$4)',
--     ['Pemilik Kino', 'pemilik', 'pemilik', $hash]);
-- Proses login PHP melakukan pemeriksaan kata sandi menggunakan password_verify().

COMMIT;

-- Query verifikasi setelah proses pembangunan database selesai.
-- Jalankan secara terpisah setelah perintah COMMIT berhasil dijalankan.

SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
  AND table_name IN ('pengguna','pelanggan','layanan','shift','jadwal_shift',
                     'shift_operasional','transaksi','detail_transaksi')
ORDER BY table_name;

SELECT table_name, column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN ('pengguna','pelanggan','layanan','shift','jadwal_shift',
                     'shift_operasional','transaksi','detail_transaksi')
ORDER BY table_name, ordinal_position;

SELECT tc.table_name, tc.constraint_name, tc.constraint_type,
       pg_get_constraintdef(pc.oid) AS definition
FROM information_schema.table_constraints tc
JOIN pg_constraint pc ON pc.conname = tc.constraint_name
JOIN pg_class tbl ON tbl.oid = pc.conrelid AND tbl.relname = tc.table_name
JOIN pg_namespace ns ON ns.oid = tbl.relnamespace AND ns.nspname = tc.constraint_schema
WHERE tc.constraint_schema = 'public'
  AND tc.table_name IN ('pengguna','pelanggan','layanan','shift','jadwal_shift',
                        'shift_operasional','transaksi','detail_transaksi')
ORDER BY tc.table_name, tc.constraint_type, tc.constraint_name;

SELECT tablename, indexname, indexdef
FROM pg_indexes
WHERE schemaname = 'public'
  AND tablename IN ('pengguna','pelanggan','layanan','shift','jadwal_shift',
                    'shift_operasional','transaksi','detail_transaksi')
ORDER BY tablename, indexname;

SELECT nama_layanan, harga, jumlah_koin, aktif
FROM public.layanan ORDER BY id_layanan;

SELECT nama_shift, jam_mulai, jam_selesai
FROM public.shift ORDER BY id_shift;

SELECT column_name
FROM information_schema.columns
WHERE table_schema = 'public' AND table_name = 'transaksi'
  AND column_name IN ('koin_500','koin_1000','nilai_koin');
