-- KINO COIN LAUNDRY - REBUILD DATABASE SCHEMA (SUPABASE & LOCAL POSTGRESQL READY)

BEGIN;

-- 1. Validasi database (mendukung nama lokal kino_coin_laundry2 dan Supabase 'postgres')
DO $$
BEGIN
    IF current_database() NOT IN ('kino_coin_laundry2', 'postgres') THEN
        RAISE EXCEPTION 'Database tidak cocok: diharapkan kino_coin_laundry2 atau postgres, terhubung ke %', current_database();
    END IF;
END
$$;

SET LOCAL search_path = public;

-- 2. Drop view, fungsi, dan tabel dependensi terlebih dahulu (dengan CASCADE)
DROP VIEW IF EXISTS public.v_rekap_shift_operasional CASCADE;
DROP FUNCTION IF EXISTS public.sp_tutup_shift_kasir(BIGINT, BIGINT, TEXT) CASCADE;
DROP FUNCTION IF EXISTS public.fn_auto_kode_kartu_pelanggan() CASCADE;
DROP TABLE IF EXISTS public.akses_pelanggan CASCADE;
DROP TABLE IF EXISTS public.detail_transaksi CASCADE;
DROP TABLE IF EXISTS public.transaksi CASCADE;
DROP TABLE IF EXISTS public.shift_operasional CASCADE;
DROP TABLE IF EXISTS public.jadwal_shift CASCADE;
DROP TABLE IF EXISTS public.pelanggan CASCADE;
DROP TABLE IF EXISTS public.layanan CASCADE;
DROP TABLE IF EXISTS public.shift CASCADE;
DROP TABLE IF EXISTS public.pengguna CASCADE;

-- 3. Tabel Pengguna (Staf Kasir & Pemilik)
CREATE TABLE public.pengguna (
    id_pengguna SERIAL PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    username    VARCHAR(50) NOT NULL UNIQUE,
    role        VARCHAR(20) NOT NULL CHECK (role IN ('pemilik', 'kasir')),
    password    VARCHAR(255) NOT NULL,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 4. Tabel Pelanggan & Member
CREATE TABLE public.pelanggan (
    id_pelanggan   SERIAL PRIMARY KEY,
    nama_pelanggan VARCHAR(100) NOT NULL,
    no_hp          VARCHAR(20) NOT NULL UNIQUE,
    password_hash  VARCHAR(255),
    kode_kartu     VARCHAR(64) UNIQUE,
    dibuat_pada    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_pelanggan_no_hp_format CHECK (no_hp ~ '^\+?[0-9]{9,15}$')
);

-- 5. Tabel Katalog Layanan
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

-- 6. Tabel Master Shift Operasional
CREATE TABLE public.shift (
    id_shift    SERIAL PRIMARY KEY,
    nama_shift  VARCHAR(30) NOT NULL UNIQUE,
    jam_mulai   TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    CONSTRAINT ck_shift_durasi CHECK (jam_mulai <> jam_selesai)
);

-- 7. Tabel Roster Jadwal Shift Kasir
CREATE TABLE public.jadwal_shift (
    id_jadwal   BIGSERIAL PRIMARY KEY,
    id_shift    INTEGER NOT NULL REFERENCES public.shift(id_shift),
    id_kasir    INTEGER NOT NULL REFERENCES public.pengguna(id_pengguna),
    tanggal     DATE NOT NULL,
    status      VARCHAR(20) NOT NULL DEFAULT 'dijadwalkan'
                CHECK (status IN ('dijadwalkan', 'dimulai', 'selesai', 'dibatalkan')),
    dibuat_oleh INTEGER NOT NULL REFERENCES public.pengguna(id_pengguna),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_jadwal_shift_kasir_tanggal UNIQUE (id_kasir, tanggal)
);

-- 8. Tabel Shift Operasional Aktif Kasir
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
    CONSTRAINT ck_shift_operasional_waktu CHECK (ditutup_pada IS NULL OR ditutup_pada >= mulai_pada)
);

-- 9. Tabel Transaksi
CREATE TABLE public.transaksi (
    id_transaksi         SERIAL PRIMARY KEY,
    id_kasir             INTEGER NOT NULL REFERENCES public.pengguna(id_pengguna),
    tanggal_transaksi    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_pelanggan         INTEGER NOT NULL REFERENCES public.pelanggan(id_pelanggan),
    id_shift             INTEGER NOT NULL REFERENCES public.shift(id_shift),
    id_shift_operasional BIGINT REFERENCES public.shift_operasional(id_shift_operasional),
    jenis_pembayaran     VARCHAR(10) NOT NULL CHECK (jenis_pembayaran IN ('cash', 'qris')),
    total_bayar          BIGINT NOT NULL CHECK (total_bayar >= 0),
    jumlah_dibayar       BIGINT NOT NULL DEFAULT 0,
    sisa_bayar           BIGINT NOT NULL DEFAULT 0,
    uang_kembalian       BIGINT NOT NULL DEFAULT 0,
    status_pembayaran    VARCHAR(20) NOT NULL DEFAULT 'belum_lunas'
                         CHECK (status_pembayaran IN ('lunas', 'sebagian', 'belum_lunas')),
    status_pengerjaan    VARCHAR(30) NOT NULL DEFAULT 'antri'
                         CHECK (status_pengerjaan IN ('antri', 'proses', 'siap_diambil', 'selesai', 'diambil')),
    no_invoice           VARCHAR(40),
    CONSTRAINT ck_transaksi_pembayaran_nonnegatif CHECK (jumlah_dibayar >= 0 AND sisa_bayar >= 0 AND uang_kembalian >= 0),
    CONSTRAINT ck_transaksi_sisa_bayar CHECK (sisa_bayar = GREATEST(total_bayar - jumlah_dibayar, 0)),
    CONSTRAINT ck_transaksi_kembalian CHECK (uang_kembalian = GREATEST(jumlah_dibayar - total_bayar, 0)),
    CONSTRAINT ck_transaksi_qris_tidak_lebih_bayar CHECK (jenis_pembayaran <> 'qris' OR jumlah_dibayar <= total_bayar),
    CONSTRAINT ck_transaksi_status_pembayaran CHECK (
        (status_pembayaran = 'lunas' AND sisa_bayar = 0 AND jumlah_dibayar >= total_bayar)
        OR (status_pembayaran = 'sebagian' AND jumlah_dibayar > 0 AND sisa_bayar > 0)
        OR (status_pembayaran = 'belum_lunas' AND jumlah_dibayar = 0 AND sisa_bayar = total_bayar)
    )
);

-- 10. Tabel Detail Layanan Transaksi
CREATE TABLE public.detail_transaksi (
    id_detail    SERIAL PRIMARY KEY,
    id_transaksi INTEGER NOT NULL REFERENCES public.transaksi(id_transaksi) ON DELETE CASCADE,
    id_layanan   INTEGER NOT NULL REFERENCES public.layanan(id_layanan),
    berat        NUMERIC(5,2),
    jumlah       INTEGER NOT NULL DEFAULT 1 CHECK (jumlah > 0),
    jumlah_koin  INTEGER NOT NULL CHECK (jumlah_koin >= 0),
    harga_satuan INTEGER NOT NULL CHECK (harga_satuan >= 0),
    subtotal     BIGINT NOT NULL CHECK (subtotal >= 0),
    CONSTRAINT ck_detail_transaksi_berat CHECK (berat IS NULL OR berat > 0)
);

-- 11. Indexes & Unique Constraints
CREATE UNIQUE INDEX uq_shift_operasional_kasir_aktif ON public.shift_operasional (id_kasir) WHERE status = 'aktif';
CREATE UNIQUE INDEX uq_transaksi_invoice ON public.transaksi (no_invoice) WHERE no_invoice IS NOT NULL;

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

-- 12. Seeding Master Data: Shift
INSERT INTO public.shift (nama_shift, jam_mulai, jam_selesai) VALUES
    ('Pagi', '06:00', '12:00'),
    ('Siang', '12:00', '18:00'),
    ('Malam', '18:00', '00:00'),
    ('Midnight', '00:00', '06:00')
ON CONFLICT (nama_shift) DO UPDATE SET jam_mulai = EXCLUDED.jam_mulai, jam_selesai = EXCLUDED.jam_selesai;

-- 13. Seeding Master Data: Layanan
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

-- 14. Seeding Akun Awal (Pemilik & Kasir)
-- Akun Pemilik: username 'pemilik', password 'pemilik123'
-- Akun Kasir:
--   - username 'kasir', password 'kasir123' (Sarah Kasir)
--   - username 'ramzy', password 'kasir123' (Ramzy)
--   - username 'Geiza', password 'kasir123' (Geiza)
INSERT INTO public.pengguna (nama, username, role, password) VALUES
    ('Pemilik Kino', 'pemilik', 'pemilik', '$2y$10$yrItimnr.x16qzz4nWZZWuC.HoVqEKtIgi1Bs2aCQtL6zlJ/t0kvi'),
    ('Sarah Kasir',  'kasir',   'kasir',   '$2y$10$MlcxEBjU6AjasLv.xdrfDu4cZ3p4PkHhW8l2mc.I7/k/cwUliMv6.'),
    ('Ramzy',        'ramzy',   'kasir',   '$2y$10$MlcxEBjU6AjasLv.xdrfDu4cZ3p4PkHhW8l2mc.I7/k/cwUliMv6.'),
    ('Geiza',        'Geiza',   'kasir',   '$2y$10$MlcxEBjU6AjasLv.xdrfDu4cZ3p4PkHhW8l2mc.I7/k/cwUliMv6.')
ON CONFLICT (username) DO UPDATE
SET nama = EXCLUDED.nama,
    role = EXCLUDED.role,
    password = EXCLUDED.password;

-- 15. VIEW: Rekapitulasi Operasional Shift Lengkap (Bebas Duplikasi Omzet)
CREATE OR REPLACE VIEW public.v_rekap_shift_operasional AS
WITH dt_agg AS (
    SELECT 
        id_transaksi,
        COALESCE(SUM(berat), 0) AS berat,
        COALESCE(SUM(jumlah_koin), 0) AS jumlah_koin
    FROM public.detail_transaksi
    GROUP BY id_transaksi
),
t_agg AS (
    SELECT 
        t.id_shift_operasional,
        COUNT(DISTINCT t.id_transaksi) AS jumlah_transaksi,
        COALESCE(SUM(t.total_bayar), 0) AS total_omzet,
        COALESCE(SUM(dt.berat), 0)::NUMERIC(10,2) AS total_berat_kg,
        COALESCE(SUM(dt.jumlah_koin), 0)::INTEGER AS total_koin_mesin
    FROM public.transaksi t
    LEFT JOIN dt_agg dt ON dt.id_transaksi = t.id_transaksi
    GROUP BY t.id_shift_operasional
)
SELECT 
    so.id_shift_operasional,
    so.mulai_pada,
    so.ditutup_pada,
    s.nama_shift,
    p.nama AS nama_kasir,
    COALESCE(t_agg.jumlah_transaksi, 0)::INTEGER AS jumlah_transaksi,
    COALESCE(t_agg.total_berat_kg, 0)::NUMERIC(10,2) AS total_berat_kg,
    COALESCE(t_agg.total_koin_mesin, 0)::INTEGER AS total_koin_mesin,
    COALESCE(t_agg.total_omzet, 0)::BIGINT AS total_omzet,
    so.total_sistem,
    so.total_fisik,
    so.selisih,
    so.status
FROM public.shift_operasional so
JOIN public.shift s ON s.id_shift = so.id_shift
JOIN public.pengguna p ON p.id_pengguna = so.id_kasir
LEFT JOIN t_agg ON t_agg.id_shift_operasional = so.id_shift_operasional;

-- 16. TRIGGER & FUNCTION: Otomatisasi Generate Kode Kartu Member Pelanggan
CREATE OR REPLACE FUNCTION public.fn_auto_kode_kartu_pelanggan()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.kode_kartu IS NULL OR NEW.kode_kartu = '' THEN
        NEW.kode_kartu := 'KC-P-' || LPAD(NEW.id_pelanggan::TEXT, 4, '0');
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_generate_kode_kartu ON public.pelanggan;
CREATE TRIGGER trg_generate_kode_kartu
BEFORE INSERT ON public.pelanggan
FOR EACH ROW
EXECUTE FUNCTION public.fn_auto_kode_kartu_pelanggan();

-- 17. STORED PROCEDURE / FUNCTION: Prosedur Penutupan Shift Kasir
CREATE OR REPLACE FUNCTION public.sp_tutup_shift_kasir(
    p_id_shift_operasional BIGINT,
    p_total_fisik BIGINT,
    p_catatan TEXT DEFAULT NULL
)
RETURNS TABLE (
    id_shift_operasional BIGINT,
    total_sistem BIGINT,
    total_fisik BIGINT,
    selisih BIGINT,
    status VARCHAR
) AS $$
DECLARE
    v_total_sistem BIGINT;
    v_selisih BIGINT;
    v_id_jadwal BIGINT;
BEGIN
    -- Hitung total kas cash masuk pada shift tersebut
    SELECT COALESCE(SUM(jumlah_dibayar - uang_kembalian), 0)
    INTO v_total_sistem
    FROM public.transaksi
    WHERE id_shift_operasional = p_id_shift_operasional
      AND jenis_pembayaran = 'cash';

    -- Hitung selisih kas fisik vs sistem
    v_selisih := p_total_fisik - v_total_sistem;

    -- Update shift operasional menjadi ditutup
    UPDATE public.shift_operasional
    SET ditutup_pada = CURRENT_TIMESTAMP,
        total_sistem = v_total_sistem,
        total_fisik  = p_total_fisik,
        selisih      = v_selisih,
        catatan      = p_catatan,
        status       = 'ditutup'
    WHERE shift_operasional.id_shift_operasional = p_id_shift_operasional
      AND shift_operasional.status = 'aktif'
    RETURNING shift_operasional.id_jadwal INTO v_id_jadwal;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Shift operasional tidak ditemukan atau sudah ditutup.';
    END IF;

    -- Update status jadwal shift jika terhubung dengan jadwal
    IF v_id_jadwal IS NOT NULL THEN
        UPDATE public.jadwal_shift
        SET status = 'selesai'
        WHERE id_jadwal = v_id_jadwal;
    END IF;

    -- Return ringkasan
    RETURN QUERY
    SELECT 
        p_id_shift_operasional,
        v_total_sistem,
        p_total_fisik,
        v_selisih,
        'ditutup'::VARCHAR;
END;
$$ LANGUAGE plpgsql;

COMMIT;

-- ============================================================================
-- VERIFIKASI POST-BUILD (Jalankan terpisah bila diperlukan)
-- ============================================================================
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
  AND table_name IN ('pengguna','pelanggan','layanan','shift','jadwal_shift',
                     'shift_operasional','transaksi','detail_transaksi')
ORDER BY table_name;
