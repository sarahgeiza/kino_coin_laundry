<?php
// Output buffering to prevent headers already sent
if (ob_get_level() === 0) ob_start();

// Zona waktu operasional toko (WIB). Tanpa ini, PHP memakai UTC server sehingga
// transaksi dini hari WIB bisa tercatat pada tanggal yang salah.
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/api_client.php';

/**
 * Supabase berjalan pada zona UTC, sedangkan jam operasional laundry memakai WIB.
 * Tanpa ini, CURRENT_TIME/CURRENT_DATE di query shift meleset 7 jam.
 */
function db_apply_timezone(): void
{
    static $applied = false;
    if ($applied) {
        return;
    }
    $conn = getConnection();
    @pg_query($conn, "SET TIME ZONE 'Asia/Jakarta'");
    $applied = true;
}

// Inisialisasi token CSRF jika belum ada di sesi
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/**
 * Sinkronisasi data real-time langsung dari Supabase PostgreSQL.
 * Mengambil katalog layanan, data member, transaksi, pengguna, dan shift riil.
 */
function sync_live_database(): bool
{
    try {
        db_apply_timezone();
        $conn = getConnection();
        if (!$conn) {
            return false;
        }

        // 1. Katalog Layanan Riil dari Supabase
        $res_l = @pg_query($conn, "SELECT * FROM layanan ORDER BY id_layanan");
        if ($res_l) {
            $services = [];
            $seen_names = [];
            while ($row = pg_fetch_assoc($res_l)) {
                $nama = $row['nama_layanan'];
                $nama_key = strtolower(trim($nama));
                if (isset($seen_names[$nama_key])) continue;
                $seen_names[$nama_key] = true;

                $kategori = 'SELF_SERVICE';
                $label = 'Self Service (Token)';
                $satuan = 'putaran';

                if (stripos($nama, 'Drop Off') !== false) {
                    $kategori = 'DROP_OFF';
                    $label = 'Drop Off (Tunai)';
                    $satuan = '7.5 kg';
                } elseif (stripos($nama, 'Lipat') !== false) {
                    $kategori = 'DROP_OFF';
                    $label = 'Drop Off (Tunai)';
                    $satuan = 'kg'; // Jasa lipat per kilogram (kg)
                } elseif (in_array($nama_key, ['sabun', 'softener', 'kresek']) || stripos($nama, 'sabun') !== false || stripos($nama, 'softener') !== false || stripos($nama, 'kresek') !== false) {
                    $kategori = 'ADDITIONAL';
                    $label = 'Additional (Tunai)';
                    $satuan = 'pcs';
                } elseif (stripos($nama, 'extra dry') !== false) {
                    $satuan = '20 menit';
                } elseif (!empty($row['berat_standar']) && floatval($row['berat_standar']) > 0 && $kategori !== 'SELF_SERVICE') {
                    $satuan = $row['berat_standar'] . ' kg';
                }

                $services[] = [
                    'id' => (int) $row['id_layanan'],
                    'kategori' => $kategori,
                    'kategori_label' => $label,
                    'nama' => $nama,
                    'deskripsi' => $row['durasi'] . ($satuan ? " • {$satuan}" : ""),
                    'tarif' => (int) $row['harga'],
                    'koin_cuci_req' => (stripos($nama, 'cuci') !== false ? 1 : 0),
                    'koin_kering_req' => (stripos($nama, 'kering') !== false ? 1 : 0),
                    'satuan' => $satuan,
                    'durasi' => $row['durasi'],
                    'status' => in_array($row['aktif'], ['t', true, 1, '1']) ? 'Aktif' : 'Nonaktif',
                    'icon' => ''
                ];
            }
            if (!empty($services)) {
                $_SESSION['services'] = $services;
            }
        }

        // 2. Akun Staf & Pemilik Riil dari Supabase
        $res_u = @pg_query($conn, "SELECT id_pengguna, nama, username, role, password FROM pengguna ORDER BY id_pengguna");
        if ($res_u) {
            $users = [];
            while ($row = pg_fetch_assoc($res_u)) {
                $users[] = [
                    'id' => (int) $row['id_pengguna'],
                    'nama' => $row['nama'],
                    'username' => $row['username'],
                    'role' => ucfirst($row['role']),
                    'email' => $row['username'] . '@kino.com',
                    'password' => $row['password']
                ];
            }
            if (!empty($users)) {
                $_SESSION['users'] = $users;
            }
        }

        // 3. Pelanggan & Kartu Member Riil dari Supabase
        $res_p = @pg_query($conn, "
            SELECT p.*,
                   COUNT(t.id_transaksi) as tx_count,
                   COALESCE(SUM(d.jumlah_koin), 0) as total_koin
            FROM pelanggan p
            LEFT JOIN transaksi t ON t.id_pelanggan = p.id_pelanggan
            LEFT JOIN detail_transaksi d ON d.id_transaksi = t.id_transaksi
            GROUP BY p.id_pelanggan
            ORDER BY p.id_pelanggan
        ");
        if ($res_p) {
            $members = [];
            while ($row = pg_fetch_assoc($res_p)) {
                $members[] = [
                    'id' => (int) $row['id_pelanggan'],
                    'nama' => $row['nama_pelanggan'],
                    'wa' => $row['no_hp'],
                    'kode_kartu' => $row['kode_kartu'] ?: ('KC-P-' . str_pad((string)$row['id_pelanggan'], 4, '0', STR_PAD_LEFT)),
                    'is_member' => !empty($row['password_hash']),
                    'tx_count' => (int) $row['tx_count'],
                    'coin_cuci' => (int) $row['total_koin'],
                    'coin_kering' => 0,
                    'password' => $row['password_hash']
                ];
            }
            $_SESSION['members'] = $members;
        }

        // 4. Master Shift Operasional Toko
        $res_sh = @pg_query($conn, "SELECT * FROM shift ORDER BY id_shift");
        if ($res_sh) {
            $shifts = [];
            $seen_shift = [];
            while ($row = pg_fetch_assoc($res_sh)) {
                $nama_shift = (str_starts_with($row['nama_shift'], 'Shift') ? '' : 'Shift ') . $row['nama_shift'];
                $key = strtolower(trim($nama_shift));
                if (isset($seen_shift[$key])) continue; // hindari baris shift duplikat
                $seen_shift[$key] = true;

                $m = substr($row['jam_mulai'], 0, 5);
                $s = substr($row['jam_selesai'], 0, 5);

                // Roster kasir: prioritas dari tabel jadwal_shift, fallback ke roster sesi
                $roster = db_shift_roster((int) $row['id_shift']);
                if ($roster === null) {
                    $roster = $_SESSION['shift_staff'][shift_roster_key($nama_shift)] ?? [];
                }

                $shifts[] = [
                    'id' => (int) $row['id_shift'],
                    'nama' => $nama_shift,
                    'mulai' => $m,
                    'selesai' => $s,
                    'jam_tampil' => "$m — $s",
                    'staff' => implode(', ', $roster),
                    'staff_list' => array_values($roster)
                ];
            }
            if (!empty($shifts)) {
                $_SESSION['shifts'] = $shifts;
            }
        }

        // 5. Transaksi Riil & Detail Item dari Supabase
        $sql_t = "
            SELECT t.*, 
                   p.nama_pelanggan, p.no_hp, p.kode_kartu,
                   u.nama as nama_kasir,
                   s.nama_shift,
                   STRING_AGG(l.nama_layanan, ', ') as layanan_names,
                   COALESCE(SUM(d.jumlah_koin), 0) as total_koin,
                   COALESCE(SUM(d.berat), 0) as total_berat,
                   COALESCE(SUM(d.jumlah), 0) as total_putaran,
                   COUNT(d.id_detail) as qty_count,
                   BOOL_OR(l.nama_layanan ILIKE '%drop off%' OR l.nama_layanan ILIKE '%lipat%') AS has_dropoff,
                   BOOL_OR(l.nama_layanan ILIKE '%cuci%' OR l.nama_layanan ILIKE '%kering%' OR l.nama_layanan ILIKE '%extra%') AS has_machine,
                   BOOL_OR(l.nama_layanan ILIKE '%sabun%' OR l.nama_layanan ILIKE '%softener%' OR l.nama_layanan ILIKE '%kresek%') AS has_retail
            FROM transaksi t
            JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
            JOIN pengguna u ON u.id_pengguna = t.id_kasir
            JOIN shift s ON s.id_shift = t.id_shift
            LEFT JOIN detail_transaksi d ON d.id_transaksi = t.id_transaksi
            LEFT JOIN layanan l ON l.id_layanan = d.id_layanan
            GROUP BY t.id_transaksi, p.nama_pelanggan, p.no_hp, p.kode_kartu, u.nama, s.nama_shift
            ORDER BY t.id_transaksi DESC
        ";
        $res_t = @pg_query($conn, $sql_t);
        if ($res_t) {
            $txs = [];
            while ($row = pg_fetch_assoc($res_t)) {
                // Kategori transaksi diturunkan dari layanan pada detail transaksi
                $is_true = fn($v) => ($v === true || $v === 't' || $v === '1' || $v === 1);
                if ($is_true($row['has_dropoff'])) {
                    $kategori_trx = 'DROP_OFF';
                    $layanan_tipe = 'Drop Off (Tunai)';
                } elseif ($is_true($row['has_machine'])) {
                    $kategori_trx = 'SELF_SERVICE';
                    $layanan_tipe = 'Self Service (Token)';
                } else {
                    $kategori_trx = 'ADDITIONAL';
                    $layanan_tipe = 'Additional (Tunai)';
                }

                $txs[] = [
                    'id' => $row['no_invoice'] ?: ('INV-' . str_pad((string)$row['id_transaksi'], 3, '0', STR_PAD_LEFT)),
                    'pelanggan' => $row['nama_pelanggan'],
                    'wa' => $row['no_hp'],
                    'kode_kartu' => $row['kode_kartu'] ?: ('KC-P-' . str_pad((string)$row['id_pelanggan'], 4, '0', STR_PAD_LEFT)),
                    'layanan' => $row['layanan_names'] ?: 'Cuci Kering',
                    'layanan_tipe' => $layanan_tipe,
                    'kategori' => $kategori_trx,
                    'kuantitas' => ($row['total_berat'] > 0
                        ? (rtrim(rtrim(number_format((float) $row['total_berat'], 1, ',', ''), '0'), ',') . ' kg'
                            . ((int) $row['total_putaran'] > 1 ? ' · ' . (int) $row['total_putaran'] . ' putaran' : ''))
                        : $row['qty_count'] . ' item'),
                    'qty_num' => (int) $row['qty_count'],
                    'total_biaya' => (int) $row['total_bayar'],
                    'metode' => ucfirst($row['jenis_pembayaran']),
                    'koin_cuci' => (int) $row['total_koin'],
                    'koin_kering' => 0,
                    'total_bayar' => (int) $row['total_bayar'],
                    'status' => ucfirst($row['status_pembayaran'] ?: 'Lunas'),
                    'kasir' => $row['nama_kasir'],
                    'shift' => 'Shift ' . $row['nama_shift'],
                    'waktu' => date('H:i WIB', strtotime($row['tanggal_transaksi'])),
                    'tanggal' => date('d M Y', strtotime($row['tanggal_transaksi'])),
                    'filter_date' => date('Y-m-d', strtotime($row['tanggal_transaksi']))
                ];
            }
            $_SESSION['transactions'] = $txs;
        }

        // 6. Rekapitulasi Shift & Presensi Staf Kasir
        $res_recap = @pg_query($conn, "SELECT * FROM v_rekap_shift_operasional ORDER BY id_shift_operasional DESC");
        if ($res_recap) {
            $recaps = [];
            $presensi = [];
            while ($row = pg_fetch_assoc($res_recap)) {
                $is_active = ($row['status'] === 'aktif' && empty($row['ditutup_pada']));
                $recaps[] = [
                    'id' => (int) $row['id_shift_operasional'],
                    'kasir' => $row['nama_kasir'],
                    'shift' => 'Shift ' . $row['nama_shift'],
                    'tanggal' => date('d M Y', strtotime($row['mulai_pada'])),
                    'trx_count' => (int) $row['jumlah_transaksi'],
                    'total_kg' => (float) $row['total_berat_kg'],
                    'pendapatan' => (int) $row['total_omzet'],
                    'koin_cuci' => (int) $row['total_koin_mesin'],
                    'koin_kering' => 0,
                    'status' => $is_active ? 'Aktif' : 'Valid',
                    'selisih' => (int) ($row['selisih'] ?? 0)
                ];
                $presensi[] = [
                    'id' => (int) $row['id_shift_operasional'],
                    'kasir' => $row['nama_kasir'],
                    'shift' => 'Shift ' . $row['nama_shift'],
                    'tanggal' => date('d M Y', strtotime($row['mulai_pada'])),
                    'filter_date' => date('Y-m-d', strtotime($row['mulai_pada'])),
                    'jam_masuk' => date('H:i', strtotime($row['mulai_pada'])),
                    'jam_keluar' => $row['ditutup_pada'] ? date('H:i', strtotime($row['ditutup_pada'])) : null,
                    'status' => $is_active ? 'Berjalan' : 'Selesai'
                ];
            }
            $_SESSION['shifts_recap'] = $recaps;
            $_SESSION['presensi'] = $presensi;
        }

        return true;
    } catch (Throwable $e) {
        error_log('Supabase sync error: ' . $e->getMessage());
        return false;
    }
}

// Jalankan sinkronisasi database setiap halaman dimuat
sync_live_database();

/**
 * Menyimpan transaksi baru langsung ke Supabase PostgreSQL
 */
function db_save_transaction(array $data): ?string
{
    if (empty($_SESSION['user_id'])) {
        $GLOBALS['kino_last_db_error'] = 'Sesi login tidak valid. Silakan login ulang.';
        return null;
    }

    // Susun detail sesuai kontrak backend: layanan berbasis berat kirim 'berat',
    // layanan satuan (sabun/softener/kresek) kirim 'jumlah'.
    $details = [];
    $mode = $data['pricing_mode'] ?? 'unit';

    if ($mode === 'unit') {
        $details[] = [
            'id_layanan' => (int) ($data['service_id'] ?? 1),
            'jumlah' => max(1, (int) ($data['qty'] ?? 1)),
        ];
    } else {
        $details[] = [
            'id_layanan' => (int) ($data['service_id'] ?? 1),
            'berat' => (float) ($data['berat'] ?? 7.5),
        ];
    }

    foreach (($data['addons'] ?? []) as $addon) {
        $details[] = [
            'id_layanan' => (int) $addon['id'],
            'jumlah' => max(1, (int) $addon['qty']),
        ];
    }

    $response = api_call('transaksi', 'create', 'POST', [], [
        'nama_pelanggan' => (string) ($data['nama'] ?? ''),
        'no_hp' => (string) ($data['wa'] ?? ''),
        'detail_transaksi' => $details,
        'jenis_pembayaran' => 'cash',
        'jumlah_dibayar' => (int) ($data['total_bayar'] ?? 0),
    ]);

    if (!api_ok($response)) {
        $GLOBALS['kino_last_db_error'] = api_error_message($response);
        return null;
    }

    sync_live_database();

    return (string) ($response['data']['data']['no_invoice'] ?? '');
}

/**
 * Mendaftarkan pelanggan baru langsung ke Supabase PostgreSQL
 */
function db_save_member(string $nama, string $wa, ?string $password = null): ?array
{
    $response = api_call('pelanggan', 'create', 'POST', [], [
        'nama_pelanggan' => $nama,
        'no_hp' => $wa,
    ]);

    if (!api_ok($response)) {
        $GLOBALS['kino_last_db_error'] = api_error_message($response);
        return null;
    }

    sync_live_database();

    // kode_kartu dibuat oleh trigger database, jadi ambil dari data yang baru disinkronkan
    $member = null;
    foreach ($_SESSION['members'] ?? [] as $m) {
        if ((string) ($m['wa'] ?? '') === $wa) {
            $member = $m;
            break;
        }
    }

    return [
        'id' => (int) ($member['id'] ?? 0),
        'nama' => (string) ($member['nama'] ?? $nama),
        'wa' => (string) ($member['wa'] ?? $wa),
        'kode_kartu' => (string) ($member['kode_kartu'] ?? ''),
    ];
}

/**
 * Mulai shift kasir & simpan presensi ke Supabase
 */
/**
 * Ambil shift operasional yang sedang AKTIF milik kasir tertentu.
 * Sumber kebenaran tunggal: tabel shift_operasional di Supabase.
 */
function db_active_shift(string $kasir_name): ?array
{
    try {
        $conn = getConnection();
        if (!$conn) return null;

        $u_res = @pg_query_params(
            $conn,
            "SELECT id_pengguna FROM pengguna WHERE nama ILIKE $1 OR username ILIKE $1 LIMIT 1",
            ["%$kasir_name%"]
        );
        if (!$u_res || pg_num_rows($u_res) === 0) return null;
        $id_kasir = (int) pg_fetch_result($u_res, 0, 'id_pengguna');

        $res = @pg_query_params($conn, "
            SELECT so.id_shift_operasional, so.id_shift, so.mulai_pada,
                   s.nama_shift, s.jam_mulai, s.jam_selesai
            FROM shift_operasional so
            JOIN shift s ON s.id_shift = so.id_shift
            WHERE so.id_kasir = $1 AND so.status = 'aktif'
            ORDER BY so.id_shift_operasional DESC
            LIMIT 1
        ", [$id_kasir]);

        if (!$res || pg_num_rows($res) === 0) return null;
        return pg_fetch_assoc($res);
    } catch (Throwable $e) {
        return null;
    }
}

function db_start_shift(string $kasir_name, int $shift_id = 2): bool
{
    $response = api_call('shift-operasional', 'open', 'POST', [], [
        'id_shift' => $shift_id,
    ]);

    if (!api_ok($response)) {
        $GLOBALS['shift_start_error'] = api_error_message($response);
        return false;
    }

    sync_live_database();

    return true;
}

/**
 * Tutup shift kasir & audit selisih kas fisik di Supabase.
 * Hanya menutup shift milik kasir yang bersangkutan.
 * Tidak ada fallback lintas-kasir: kasir tidak bisa menutup shift orang lain
 * atau "menutup" shift yang tidak pernah ia buka.
 */
function db_close_shift(string $kasir_name, int $total_fisik = 0, int $selisih = 0): bool
{
    $response = api_call('shift-operasional', 'close', 'POST', [], [
        'total_fisik' => $total_fisik,
    ]);

    if (!api_ok($response)) {
        $GLOBALS['shift_close_error'] = api_error_message($response);
        return false;
    }

    sync_live_database();

    return true;
}

function format_rupiah(int $angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Mencari shift aktif kasir berdasarkan penugasan staf dan jam berjalan
 */
/**
 * Simpan roster kasir untuk sebuah shift ke tabel jadwal_shift.
 * Aman dipanggil walau tabel/binding belum tersedia (return false).
 */
/**
 * Cache roster per-request (bisa di-flush setelah mutasi).
 */
/**
 * Kunci roster ternormalisasi: buang prefix "Shift" dan spasi, lowercase.
 * Dipakai agar "Shift Pagi", "Pagi", " shift  pagi " menunjuk roster yang sama.
 */
function shift_roster_key(string $name): string
{
    $n = strtolower(trim($name));
    $n = preg_replace('/^shift\s+/i', '', $n);
    return preg_replace('/\s+/', ' ', $n);
}

function db_shift_roster_cache(): array
{
    if (!isset($GLOBALS['kino_shift_roster_cache'])) $GLOBALS['kino_shift_roster_cache'] = [];
    return $GLOBALS['kino_shift_roster_cache'];
}
function db_shift_roster_flush(): void
{
    $GLOBALS['kino_shift_roster_cache'] = [];
}

/**
 * Tabel shift bisa punya baris duplikat (nama sama, id berbeda) akibat seeding berulang.
 * Semua operasi roster dipusatkan ke id terkecil dengan nama yang sama,
 * supaya sunting roster dari baris mana pun tetap konsisten.
 */
function db_shift_canonical_id(int $shift_id): int
{
    try {
        $conn = getConnection();
        if (!$conn) return $shift_id;

        $res = @pg_query_params($conn, "
            SELECT MIN(s2.id_shift) AS id
            FROM shift s1
            JOIN shift s2 ON LOWER(TRIM(s2.nama_shift)) = LOWER(TRIM(s1.nama_shift))
            WHERE s1.id_shift = $1
        ", [$shift_id]);

        if ($res && pg_num_rows($res) > 0) {
            $id = (int) pg_fetch_result($res, 0, 'id');
            if ($id > 0) return $id;
        }
        return $shift_id;
    } catch (Throwable $e) {
        return $shift_id;
    }
}

function db_save_shift_roster(int $shift_id, array $kasir_ids): bool
{
    try {
        $conn = getConnection();
        if (!$conn) return false;

        $chk = @pg_query($conn, "SELECT to_regclass('public.jadwal_shift') AS t");
        if (!$chk || pg_fetch_result($chk, 0, 't') === null) return false;

        // dibuat_oleh wajib terisi: pakai user aktif, fallback pemilik pertama
        $owner_id = (int) ($_SESSION['user_id'] ?? 0);
        if ($owner_id <= 0) {
            $ow = @pg_query($conn, "SELECT id_pengguna FROM pengguna WHERE role = 'pemilik' ORDER BY id_pengguna LIMIT 1");
            $owner_id = ($ow && pg_num_rows($ow)) ? (int) pg_fetch_result($ow, 0, 'id_pengguna') : 0;
        }
        if ($owner_id <= 0) return false;

        $ids = array_values(array_filter(array_map('intval', $kasir_ids)));

        // Semua baris shift dengan nama sama diperlakukan sebagai satu shift
        $shift_id = db_shift_canonical_id($shift_id);

        @pg_query($conn, "BEGIN");

        // 1. Lepas kasir yang tidak lagi dipilih -> tandai 'dibatalkan' (BUKAN dihapus),
        //    supaya sistem tetap tahu roster hari ini sudah pernah diatur.
        if (empty($ids)) {
            @pg_query_params($conn,
                "UPDATE jadwal_shift SET status = 'dibatalkan'
                 WHERE id_shift = $1 AND tanggal = CURRENT_DATE",
                [$shift_id]
            );
        } else {
            $placeholders = implode(',', array_map('intval', $ids));
            @pg_query($conn,
                "UPDATE jadwal_shift SET status = 'dibatalkan'
                 WHERE id_shift = " . (int) $shift_id . "
                   AND tanggal = CURRENT_DATE
                   AND id_kasir NOT IN (" . $placeholders . ")"
            );
        }

        // 2. Pasang / pindahkan kasir terpilih ke shift ini.
        //    UNIQUE(id_kasir, tanggal): satu kasir hanya satu shift per hari.
        //    Status valid: dijadwalkan | dimulai | selesai | dibatalkan.
        foreach ($ids as $kid) {
            @pg_query_params($conn,
                "INSERT INTO jadwal_shift (id_shift, id_kasir, tanggal, status, dibuat_oleh)
                 VALUES ($1, $2, CURRENT_DATE, 'dijadwalkan', $3)
                 ON CONFLICT (id_kasir, tanggal)
                 DO UPDATE SET id_shift = EXCLUDED.id_shift,
                               status = 'dijadwalkan',
                               dibuat_oleh = EXCLUDED.dibuat_oleh",
                [$shift_id, $kid, $owner_id]
            );
        }

        @pg_query($conn, "COMMIT");
        db_shift_roster_flush();
        return true;
    } catch (Throwable $e) {
        if (isset($conn) && $conn) @pg_query($conn, "ROLLBACK");
        return false;
    }
}

/**
 * Roster kasir untuk sebuah shift.
 * Sumber utama: tabel jadwal_shift (butuh binding shift<->kasir di database).
 * Return null jika tabel/binding belum tersedia atau belum ada baris,
 * supaya pemanggil bisa fallback ke roster sesi.
 */
function db_shift_roster(int $shift_id): ?array
{
    $shift_id = db_shift_canonical_id($shift_id);

    $cache = db_shift_roster_cache();
    if (array_key_exists($shift_id, $cache)) return $cache[$shift_id];

    try {
        $conn = getConnection();
        if (!$conn) { $GLOBALS['kino_shift_roster_cache'][$shift_id] = null; return null; }

        // Tabel jadwal_shift opsional: kalau belum ada, jangan bikin fatal error
        $chk = @pg_query($conn, "SELECT to_regclass('public.jadwal_shift') AS t");
        if (!$chk || pg_fetch_result($chk, 0, 't') === null) {
            $GLOBALS['kino_shift_roster_cache'][$shift_id] = null;
            return null;
        }

        $res = @pg_query_params($conn, "
            SELECT p.nama
            FROM jadwal_shift j
            JOIN pengguna p ON p.id_pengguna = j.id_kasir
            WHERE j.id_shift = $1
              AND j.tanggal = CURRENT_DATE
              AND COALESCE(j.status, 'dijadwalkan') <> 'dibatalkan'
            ORDER BY p.nama
        ", [$shift_id]);

        if (!$res || pg_num_rows($res) === 0) {
            $GLOBALS['kino_shift_roster_cache'][$shift_id] = null;
            return null;
        }

        $names = [];
        while ($r = pg_fetch_assoc($res)) $names[] = $r['nama'];
        $GLOBALS['kino_shift_roster_cache'][$shift_id] = $names;
        return $names;
    } catch (Throwable $e) {
        $GLOBALS['kino_shift_roster_cache'][$shift_id] = null;
        return null;
    }
}

/**
 * Cek apakah kasir boleh presensi pada shift tertentu.
 * Return ['allowed' => bool, 'reason' => string, 'roster' => array]
 */
/**
 * Apakah Pemilik sudah pernah mengatur roster shift untuk hari ini?
 * Dipakai membedakan "belum diatur" (izinkan) vs "sengaja dikosongkan" (tolak).
 */
function db_roster_configured(): bool
{
    try {
        $conn = getConnection();
        if (!$conn) return false;

        $chk = @pg_query($conn, "SELECT to_regclass('public.jadwal_shift') AS t");
        if (!$chk || pg_fetch_result($chk, 0, 't') === null) return false;

        // Roster dianggap "sudah diatur" bila ada baris apa pun hari ini,
        // termasuk yang berstatus 'dibatalkan' (kasir sengaja dilepas Pemilik).
        $res = @pg_query($conn, "SELECT 1 FROM jadwal_shift WHERE tanggal = CURRENT_DATE LIMIT 1");
        return ($res && pg_num_rows($res) > 0);
    } catch (Throwable $e) {
        return false;
    }
}

function kasir_shift_assignment(string $kasir_name, array $shift): array
{
    $shift_id = (int) ($shift['id'] ?? 0);
    $roster = db_shift_roster($shift_id);

    if ($roster === null) {
        $key = shift_roster_key($shift['nama'] ?? '');
        if (array_key_exists($key, $_SESSION['shift_staff'] ?? [])) {
            $roster = (array) $_SESSION['shift_staff'][$key];
        }
    }

    if ($roster === null) {
        // Roster sudah diatur Pemilik untuk hari ini, tapi shift ini tidak punya kasir
        if (db_roster_configured()) {
            return [
                'allowed' => false,
                'reason' => 'Tidak ada kasir yang dijadwalkan pada ' . ($shift['nama'] ?? 'shift ini') . '. Hubungi Pemilik untuk mengatur jadwal shift.',
                'roster' => []
            ];
        }
        // Belum ada pengaturan roster sama sekali -> izinkan (sistem belum dikonfigurasi)
        return ['allowed' => true, 'reason' => '', 'roster' => []];
    }

    if (empty($roster)) {
        return [
            'allowed' => false,
            'reason' => 'Tidak ada kasir yang dijadwalkan pada ' . ($shift['nama'] ?? 'shift ini') . '. Hubungi Pemilik untuk mengatur jadwal shift.',
            'roster' => []
        ];
    }

    foreach ($roster as $r) {
        if (stripos($r, $kasir_name) !== false || stripos($kasir_name, $r) !== false) {
            return ['allowed' => true, 'reason' => '', 'roster' => $roster];
        }
    }

    return [
        'allowed' => false,
        'reason' => 'Anda tidak dijadwalkan pada ' . ($shift['nama'] ?? 'shift ini') . '. Hubungi Pemilik untuk mengatur jadwal shift.',
        'roster' => $roster
    ];
}

function get_active_kasir_shift(string $kasir_name): array
{
    $all_shifts = $_SESSION['shifts'] ?? [];
    if (empty($all_shifts)) {
        return [
            'id' => 2,
            'nama' => 'Shift Siang',
            'mulai' => '12:00',
            'selesai' => '18:00',
            'jam_tampil' => '12:00 — 18:00',
            'staff' => 'Sarah, Ramzy'
        ];
    }

    $now_time = date('H:i');
    $candidate_by_name = null;

    foreach (array_reverse($all_shifts) as $s) {
        $staff_list = array_map('trim', explode(',', $s['staff'] ?? ''));
        $matched_name = false;
        foreach ($staff_list as $st) {
            if (stripos($st, $kasir_name) !== false || stripos($kasir_name, $st) !== false) {
                $matched_name = true;
                break;
            }
        }

        if ($matched_name) {
            if (!$candidate_by_name) {
                $candidate_by_name = $s;
            }
            $start = $s['mulai'];
            $end = $s['selesai'];
            if ($start && $end) {
                if ($start <= $end) {
                    if ($now_time >= $start && $now_time <= $end) {
                        return $s;
                    }
                } else {
                    if ($now_time >= $start || $now_time <= $end) {
                        return $s;
                    }
                }
            }
        }
    }

    if ($candidate_by_name) {
        return $candidate_by_name;
    }

    return $all_shifts[1] ?? $all_shifts[0];
}

/**
 * Validasi hak akses pengguna & status akun di Supabase
 */
function check_auth_guard(string $role): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $username = $_SESSION['username'] ?? '';
    $sess_role = $_SESSION['role'] ?? '';

    if (empty($username) || $sess_role !== $role) {
        if ($role === 'kasir') {
            header('Location: ../login-kasir.php');
            exit;
        } elseif ($role === 'pemilik') {
            header('Location: ../login-pemilik.php');
            exit;
        } elseif ($role === 'pelanggan') {
            header('Location: login.php');
            exit;
        }
    }

    // Verifikasi keaslian akun di tabel pengguna / pelanggan
    $users = $_SESSION['users'] ?? [];
    if ($role === 'kasir' || $role === 'pemilik') {
        $db_role = ($role === 'kasir') ? 'Kasir' : 'Pemilik';
        $user_valid = false;
        foreach ($users as $u) {
            if (($u['role'] ?? '') === $db_role) {
                $first_name = explode(' ', trim($u['nama']))[0];
                if (strcasecmp($u['nama'], $username) === 0 ||
                    strcasecmp($u['username'], $username) === 0 ||
                    strcasecmp($first_name, $username) === 0) {
                    $user_valid = true;
                    break;
                }
            }
        }
        if (!$user_valid) {
            $_SESSION['username'] = null;
            $_SESSION['role'] = null;
            $_SESSION['user_id'] = null;
            $redirect = ($role === 'kasir') ? '../login-kasir.php' : '../login-pemilik.php';
            header("Location: $redirect");
            exit;
        }
    } elseif ($role === 'pelanggan') {
        $members = $_SESSION['members'] ?? [];
        $member_valid = false;
        foreach ($members as $m) {
            if (strcasecmp($m['nama'], $username) === 0 || ($m['wa'] ?? '') === $username) {
                $member_valid = true;
                break;
            }
        }
        if (!$member_valid) {
            $_SESSION['username'] = null;
            $_SESSION['role'] = null;
            $_SESSION['customer_id'] = null;
            header('Location: login.php');
            exit;
        }
    }
}
