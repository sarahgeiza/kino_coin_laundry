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
function api_collect_all(string $module, string $action, array $query = [], int $maxPages = 20): array
{
    $collected = [];
    $page = 1;

    do {
        $response = api_call($module, $action, 'GET', array_merge($query, ['page' => $page]));
        if (!api_ok($response)) {
            return $collected;
        }

        $batch = $response['data']['data'] ?? [];
        if (!is_array($batch) || $batch === []) {
            break;
        }

        $collected = array_merge($collected, $batch);
        $total = (int) ($response['data']['total'] ?? count($collected));
        $limit = (int) ($response['data']['limit'] ?? count($batch));

        if (count($collected) >= $total || $limit <= 0) {
            break;
        }

        $page++;
    } while ($page <= $maxPages);

    return $collected;
}

/**
 * Sinkronisasi seluruh data dari backend (controller API).
 * Semua data halaman berasal dari endpoint backend, bukan query langsung.
 */
function sync_live_database(): bool
{
    static $alreadySynced = false;
    if ($alreadySynced) {
        return true;
    }
    $alreadySynced = true;

    try {
        $isOwner = strtolower((string) ($_SESSION['role'] ?? '')) === 'pemilik';
        $isKasir = strtolower((string) ($_SESSION['role'] ?? '')) === 'kasir';
        $isCustomer = !empty($_SESSION['customer_id']);

        // 1. Katalog layanan
        $serviceRows = api_collect_all('layanan', 'list');
        if ($serviceRows !== []) {
            $services = [];
            $seen_names = [];
            foreach ($serviceRows as $row) {
                $nama = (string) $row['nama_layanan'];
                $nama_key = strtolower(trim($nama));
                if (isset($seen_names[$nama_key])) {
                    continue;
                }
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
                    $satuan = 'kg';
                } elseif (stripos($nama, 'sabun') !== false || stripos($nama, 'softener') !== false || stripos($nama, 'kresek') !== false) {
                    $kategori = 'ADDITIONAL';
                    $label = 'Additional (Tunai)';
                    $satuan = 'pcs';
                } elseif (stripos($nama, 'extra dry') !== false) {
                    $satuan = '20 menit';
                }

                $services[] = [
                    'id' => (int) $row['id_layanan'],
                    'kategori' => $kategori,
                    'kategori_label' => $label,
                    'nama' => $nama,
                    'deskripsi' => ($row['durasi'] ?? '') . ($satuan ? " • {$satuan}" : ''),
                    'tarif' => (int) $row['harga'],
                    'koin_cuci_req' => (stripos($nama, 'cuci') !== false ? 1 : 0),
                    'koin_kering_req' => (stripos($nama, 'kering') !== false ? 1 : 0),
                    'satuan' => $satuan,
                    'durasi' => (string) ($row['durasi'] ?? ''),
                    'status' => in_array($row['aktif'], [true, 't', 1, '1'], true) ? 'Aktif' : 'Nonaktif',
                    'icon' => '',
                ];
            }
            if ($services !== []) {
                $_SESSION['services'] = $services;
            }
        }

        // 2. Akun staf (hanya pemilik yang boleh membaca daftar pengguna)
        if ($isOwner) {
            $userRows = api_collect_all('pengguna', 'list');
            if ($userRows !== []) {
                $users = [];
                foreach ($userRows as $row) {
                    $users[] = [
                        'id' => (int) $row['id_pengguna'],
                        'nama' => (string) $row['nama'],
                        'username' => (string) $row['username'],
                        'role' => ucfirst((string) $row['role']),
                        'email' => $row['username'] . '@kino.com',
                        'password' => '',
                    ];
                }
                $_SESSION['users'] = $users;
            }
        }

        // 3. Pelanggan & kartu member
        $memberRows = api_collect_all('pelanggan', 'list', ['limit' => 100]);
        $members = [];
        foreach ($memberRows as $row) {
            $members[] = [
                'id' => (int) $row['id_pelanggan'],
                'nama' => (string) $row['nama_pelanggan'],
                'wa' => (string) $row['no_hp'],
                'kode_kartu' => (string) ($row['kode_kartu'] ?? ''),
                'is_member' => in_array($row['is_member'], [true, 't', 1, '1'], true),
                'tx_count' => (int) ($row['tx_count'] ?? 0),
                'coin_cuci' => (int) ($row['total_koin'] ?? 0),
                'coin_kering' => 0,
                'password' => '',
            ];
        }
        if ($members !== [] || $memberRows === []) {
            $_SESSION['members'] = $members;
        }

        // 4. Master shift + roster hari ini
        $shiftRows = api_collect_all('shift', 'list');
        $rosterRows = api_collect_all('jadwal-shift', 'list', ['tanggal' => date('Y-m-d')]);

        $rosterByShift = [];
        foreach ($rosterRows as $r) {
            $rosterByShift[(int) $r['id_shift']][] = (string) $r['nama_kasir'];
        }

        if ($shiftRows !== []) {
            $shifts = [];
            $seen_shift = [];
            foreach ($shiftRows as $row) {
                $nama_shift = (str_starts_with((string) $row['nama_shift'], 'Shift') ? '' : 'Shift ') . $row['nama_shift'];
                $key = strtolower(trim($nama_shift));
                if (isset($seen_shift[$key])) {
                    continue;
                }
                $seen_shift[$key] = true;

                $m = substr((string) $row['jam_mulai'], 0, 5);
                $s = substr((string) $row['jam_selesai'], 0, 5);
                $roster = $rosterByShift[(int) $row['id_shift']] ?? [];

                $shifts[] = [
                    'id' => (int) $row['id_shift'],
                    'nama' => $nama_shift,
                    'mulai' => $m,
                    'selesai' => $s,
                    'jam_tampil' => "$m — $s",
                    'staff' => implode(', ', $roster),
                    'staff_list' => array_values($roster),
                ];
            }
            if ($shifts !== []) {
                $_SESSION['shifts'] = $shifts;
            }
        }

        // 5. Transaksi
        if ($isOwner || $isKasir) {
            $txRows = api_collect_all('transaksi', 'list', [
                'start' => '2000-01-01',
                'end' => date('Y-m-d'),
                'limit' => 100,
            ]);

            $cardByWa = [];
            foreach ($_SESSION['members'] ?? [] as $m) {
                $cardByWa[(string) $m['wa']] = (string) $m['kode_kartu'];
            }

            $txs = [];
            foreach ($txRows as $row) {
                $layananText = (string) ($row['layanan'] ?? '');
                $isTrue = static fn($v): bool => ($v === true || $v === 't' || $v === '1' || $v === 1);

                if (stripos($layananText, 'drop off') !== false || stripos($layananText, 'lipat') !== false) {
                    $kategori_trx = 'DROP_OFF';
                    $layanan_tipe = 'Drop Off (Tunai)';
                } elseif (stripos($layananText, 'cuci') !== false || stripos($layananText, 'kering') !== false || stripos($layananText, 'extra') !== false) {
                    $kategori_trx = 'SELF_SERVICE';
                    $layanan_tipe = 'Self Service (Token)';
                } else {
                    $kategori_trx = 'ADDITIONAL';
                    $layanan_tipe = 'Additional (Tunai)';
                }

                $berat = (float) ($row['total_berat'] ?? 0);
                $waktu = strtotime((string) $row['tanggal_transaksi']);

                $txs[] = [
                    'id' => (string) ($row['no_invoice'] ?? ''),
                    'pelanggan' => (string) $row['nama_pelanggan'],
                    'wa' => (string) ($row['no_hp'] ?? ''),
                    'kode_kartu' => $cardByWa[(string) ($row['no_hp'] ?? '')] ?? '',
                    'layanan' => $layananText !== '' ? $layananText : 'Cuci Kering',
                    'layanan_tipe' => $layanan_tipe,
                    'kategori' => $kategori_trx,
                    'kuantitas' => $berat > 0
                        ? (rtrim(rtrim(number_format($berat, 1, ',', ''), '0'), ',') . ' kg')
                        : 'item',
                    'qty_num' => 1,
                    'total_biaya' => (int) $row['total_bayar'],
                    'metode' => ucfirst((string) $row['jenis_pembayaran']),
                    'koin_cuci' => (int) ($row['total_koin'] ?? 0),
                    'koin_kering' => 0,
                    'total_bayar' => (int) $row['total_bayar'],
                    'status' => ucfirst((string) ($row['status_pembayaran'] ?: 'lunas')),
                    'kasir' => (string) $row['nama_kasir'],
                    'shift' => 'Shift ' . $row['nama_shift'],
                    'waktu' => date('H:i WIB', $waktu),
                    'tanggal' => date('d M Y', $waktu),
                    'filter_date' => date('Y-m-d', $waktu),
                ];
            }
            $_SESSION['transactions'] = $txs;
        }

        // 6. Rekap shift & presensi
        if ($isOwner || $isKasir) {
            $recapRows = api_collect_all('rekap', 'shift', [
                'start' => '2000-01-01',
                'end' => date('Y-m-d'),
                'limit' => 100,
            ]);

            $recaps = [];
            $presensi = [];
            foreach ($recapRows as $row) {
                $is_active = ($row['status'] === 'aktif' && empty($row['ditutup_pada']));
                $mulai = strtotime((string) $row['mulai_pada']);

                $recaps[] = [
                    'id' => (int) $row['id_shift_operasional'],
                    'kasir' => (string) $row['nama_kasir'],
                    'shift' => 'Shift ' . $row['nama_shift'],
                    'tanggal' => date('d M Y', $mulai),
                    'trx_count' => (int) $row['jumlah_transaksi'],
                    'total_kg' => (float) $row['total_berat'],
                    'pendapatan' => (int) $row['total_omzet'],
                    'koin_cuci' => (int) $row['total_koin'],
                    'koin_kering' => 0,
                    'status' => $is_active ? 'Aktif' : 'Valid',
                    'selisih' => (int) ($row['selisih'] ?? 0),
                ];

                $presensi[] = [
                    'id' => (int) $row['id_shift_operasional'],
                    'kasir' => (string) $row['nama_kasir'],
                    'shift' => 'Shift ' . $row['nama_shift'],
                    'tanggal' => date('d M Y', $mulai),
                    'filter_date' => date('Y-m-d', $mulai),
                    'jam_masuk' => date('H:i', $mulai),
                    'jam_keluar' => $row['ditutup_pada'] ? date('H:i', strtotime((string) $row['ditutup_pada'])) : null,
                    'status' => $is_active ? 'Berjalan' : 'Selesai',
                ];
            }
            $_SESSION['shifts_recap'] = $recaps;
            $_SESSION['presensi'] = $presensi;
        }

        return true;
    } catch (Throwable $e) {
        error_log('API sync error: ' . $e->getMessage());
        return false;
    }
}

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
    $payload = [
        'nama_pelanggan' => $nama,
        'no_hp' => $wa,
    ];
    if ($password !== null && $password !== '') {
        $payload['password'] = $password;
    }

    $response = api_call('pelanggan', 'create', 'POST', [], $payload);

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
function db_active_shift(string $kasir_name = ''): ?array
{
    $response = api_call('shift-operasional', 'current', 'GET');

    if (!api_ok($response)) {
        return null;
    }

    $row = $response['data']['data'] ?? null;
    if (!is_array($row) || empty($row['id_shift_operasional'])) {
        return null;
    }

    // Shift yang melewati jam selesai (mis. lupa ditutup kemarin) ditutup otomatis
    // supaya tidak nyangkut sebagai "Berjalan" dan tidak memblokir presensi baru.
    $mulaiTs = strtotime((string) ($row['mulai_pada'] ?? ''));
    $jamSelesai = (string) ($row['jam_selesai'] ?? '');
    if ($mulaiTs && $jamSelesai !== '') {
        $batas = strtotime(date('Y-m-d', $mulaiTs) . ' ' . substr($jamSelesai, 0, 5));
        if ($batas !== false && $batas <= $mulaiTs) {
            $batas += 86400;
        }
        if ($batas !== false && time() > $batas + 600) {
            api_call('shift-operasional', 'close', 'POST', [], [
                'total_fisik' => 0,
                'catatan' => 'Auto-tutup: shift tidak ditutup sampai jam selesai',
            ]);

            return null;
        }
    }

    return [
        'id_shift_operasional' => (int) $row['id_shift_operasional'],
        'id_shift' => (int) $row['id_shift'],
        'mulai_pada' => (string) ($row['mulai_pada'] ?? ''),
        'nama_shift' => (string) ($row['nama_shift'] ?? ''),
        'jam_mulai' => (string) ($row['jam_mulai'] ?? ''),
        'jam_selesai' => (string) ($row['jam_selesai'] ?? ''),
        'total_transaksi' => (int) ($row['total_transaksi'] ?? 0),
        'total_pendapatan' => (int) ($row['total_pendapatan'] ?? 0),
        'total_cash' => (int) ($row['total_cash'] ?? 0),
        'total_qris' => (int) ($row['total_qris'] ?? 0),
        'total_berat' => (float) ($row['total_berat'] ?? 0),
    ];
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
function db_close_shift(string $kasir_name, int $total_fisik = 0, int $selisih = 0, string $catatan = ''): bool
{
    $payload = ['total_fisik' => $total_fisik];
    if (trim($catatan) !== '') {
        $payload['catatan'] = trim($catatan);
    }

    $response = api_call('shift-operasional', 'close', 'POST', [], $payload);

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
 * Cache roster shift hari ini (diambil dari endpoint jadwal-shift).
 */
function db_shift_roster_cache(): array
{
    if (!isset($GLOBALS['kino_shift_roster_snapshot'])) {
        $response = api_call('jadwal-shift', 'list', 'GET', ['tanggal' => date('Y-m-d')]);
        $GLOBALS['kino_shift_roster_snapshot'] = [
            'rows' => api_ok($response) ? ($response['data']['data'] ?? []) : [],
            'configured' => (bool) ($response['data']['configured'] ?? false),
        ];
    }

    return $GLOBALS['kino_shift_roster_snapshot'];
}

function db_shift_roster_flush(): void
{
    unset($GLOBALS['kino_shift_roster_snapshot']);
}

/**
 * Kunci roster ternormalisasi: buang prefix "Shift" dan spasi, lowercase.
 */
function shift_roster_key(string $name): string
{
    $n = strtolower(trim($name));
    $n = preg_replace('/^shift\s+/i', '', $n);

    return preg_replace('/\s+/', ' ', $n);
}

/**
 * Backend sudah memetakan ke id kanonik saat menyimpan roster.
 */
function db_shift_canonical_id(int $shift_id): int
{
    return $shift_id;
}

/**
 * Simpan roster kasir untuk sebuah shift lewat endpoint backend.
 */
function db_save_shift_roster(int $shift_id, array $kasir_ids): bool
{
    $response = api_call('jadwal-shift', 'save', 'POST', [], [
        'tanggal' => date('Y-m-d'),
        'id_shift' => $shift_id,
        'kasir_ids' => array_values(array_map('intval', $kasir_ids)),
    ]);

    db_shift_roster_flush();

    if (!api_ok($response)) {
        $GLOBALS['kino_roster_error'] = api_error_message($response);

        return false;
    }

    return true;
}

/**
 * Nama kasir yang dijadwalkan pada satu shift hari ini.
 * null = tidak ada kasir pada shift tersebut.
 */
function db_shift_roster(int $shift_id): ?array
{
    $names = [];

    foreach (db_shift_roster_cache()['rows'] as $row) {
        if ((int) $row['id_shift'] === $shift_id) {
            $names[] = (string) $row['nama_kasir'];
        }
    }

    return $names === [] ? null : $names;
}

/**
 * Apakah Pemilik sudah pernah mengatur roster hari ini?
 * Membedakan "belum diatur" (izinkan presensi) vs "sengaja dikosongkan" (tolak).
 */
function db_roster_configured(): bool
{
    return (bool) db_shift_roster_cache()['configured'];
}


function kasir_shift_assignment(string $kasir_name, array $shift): array
{
    $shift_id = (int) ($shift['id'] ?? 0);
    $shift_label = (string) ($shift['nama'] ?? 'shift ini');
    $roster = db_shift_roster($shift_id);

    // Roster hari ini wajib ada. Tanpa jadwal, kasir tidak boleh presensi
    // supaya tidak ada yang "nebeng" shift orang lain.
    if ($roster === null) {
        if (!db_roster_configured()) {
            return [
                'allowed' => false,
                'reason' => 'Belum ada jadwal shift untuk hari ini. Minta Pemilik mengatur jadwal di menu Master → Shift.',
                'roster' => [],
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Tidak ada kasir yang dijadwalkan pada ' . $shift_label . '. Hubungi Pemilik untuk mengatur jadwal shift.',
            'roster' => [],
        ];
    }

    foreach ($roster as $r) {
        if (stripos($r, $kasir_name) !== false || stripos($kasir_name, $r) !== false) {
            return ['allowed' => true, 'reason' => '', 'roster' => $roster];
        }
    }

    return [
        'allowed' => false,
        'reason' => 'Anda tidak dijadwalkan pada ' . $shift_label . '. Hubungi Pemilik untuk mengatur jadwal shift.',
        'roster' => $roster,
    ];
}

function get_active_kasir_shift(string $kasir_name): array
{
    $all_shifts = $_SESSION['shifts'] ?? [];

    // Pastikan master shift sudah dimuat dari backend sebelum menentukan shift.
    if (empty($all_shifts)) {
        sync_live_database();
        $all_shifts = $_SESSION['shifts'] ?? [];
    }

    if (empty($all_shifts)) {
        return [
            'id' => 0,
            'nama' => 'Shift',
            'mulai' => '00:00',
            'selesai' => '00:00',
            'jam_tampil' => '-',
            'staff' => '',
        ];
    }

    $now = date('H:i');

    // Shift ditentukan murni oleh jam berjalan; roster hanya menentukan
    // siapa yang boleh presensi pada shift tersebut.
    foreach ($all_shifts as $s) {
        $start = (string) ($s['mulai'] ?? '');
        $end = (string) ($s['selesai'] ?? '');
        if ($start === '' || $end === '') {
            continue;
        }

        $isActive = $start <= $end
            ? ($now >= $start && $now < $end)
            : ($now >= $start || $now < $end);

        if ($isActive) {
            return $s;
        }
    }

    // Tidak ada jendela yang cocok (mis. jam shift tidak lengkap): pakai shift pertama.
    return $all_shifts[0];
}

/**
 * Validasi hak akses pengguna & status akun di Supabase
 */
function check_auth_guard(string $role): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sess_role = (string) ($_SESSION['role'] ?? '');

    // Sesi staf diisi oleh endpoint auth.login (user_id + role),
    // sesi pelanggan memakai customer_id.
    $is_staff = in_array($role, ['kasir', 'pemilik'], true);
    $authenticated = $is_staff
        ? (!empty($_SESSION['user_id']) && $sess_role === $role)
        : (!empty($_SESSION['customer_id']) && $sess_role === $role);

    if (!$authenticated) {
        if ($role === 'kasir') {
            header('Location: ../login-kasir.php');
        } elseif ($role === 'pemilik') {
            header('Location: ../login-pemilik.php');
        } else {
            header('Location: login.php');
        }
        exit;
    }
}
