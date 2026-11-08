<?php
// Output buffering to prevent headers already sent
if (ob_get_level() === 0) ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/config/database.php';

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
        $conn = getConnection();
        if (!$conn) {
            return false;
        }

        // 1. Katalog Layanan Riil dari Supabase
        $res_l = @pg_query($conn, "SELECT * FROM layanan ORDER BY id_layanan");
        if ($res_l) {
            $services = [];
            while ($row = pg_fetch_assoc($res_l)) {
                $nama = $row['nama_layanan'];
                $kategori = 'SELF_SERVICE';
                $label = 'Self Service (Token)';
                if (stripos($nama, 'Drop Off') !== false || stripos($nama, 'Lipat') !== false) {
                    $kategori = 'DROP_OFF';
                    $label = 'Drop Off (Tunai)';
                } elseif (in_array(strtolower($nama), ['sabun', 'softener', 'kresek'])) {
                    $kategori = 'ADDITIONAL';
                    $label = 'Additional (Tunai)';
                }

                $services[] = [
                    'id' => (int) $row['id_layanan'],
                    'kategori' => $kategori,
                    'kategori_label' => $label,
                    'nama' => $nama,
                    'deskripsi' => $row['durasi'] . ($row['berat_standar'] ? " • {$row['berat_standar']} kg" : ""),
                    'tarif' => (int) $row['harga'],
                    'koin_cuci_req' => (stripos($nama, 'cuci') !== false ? 1 : 0),
                    'koin_kering_req' => (stripos($nama, 'kering') !== false ? 1 : 0),
                    'satuan' => $row['berat_standar'] ? '7.5 kg' : ($kategori === 'ADDITIONAL' ? 'pcs' : 'putaran'),
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
            while ($row = pg_fetch_assoc($res_sh)) {
                $m = substr($row['jam_mulai'], 0, 5);
                $s = substr($row['jam_selesai'], 0, 5);
                $shifts[] = [
                    'id' => (int) $row['id_shift'],
                    'nama' => (str_starts_with($row['nama_shift'], 'Shift') ? '' : 'Shift ') . $row['nama_shift'],
                    'mulai' => $m,
                    'selesai' => $s,
                    'jam_tampil' => "$m — $s",
                    'staff' => 'Sarah, Ramzy'
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
                   COUNT(d.id_detail) as qty_count
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
                $txs[] = [
                    'id' => $row['no_invoice'] ?: ('INV-' . str_pad((string)$row['id_transaksi'], 3, '0', STR_PAD_LEFT)),
                    'pelanggan' => $row['nama_pelanggan'],
                    'wa' => $row['no_hp'],
                    'kode_kartu' => $row['kode_kartu'] ?: ('KC-P-' . str_pad((string)$row['id_pelanggan'], 4, '0', STR_PAD_LEFT)),
                    'layanan' => $row['layanan_names'] ?: 'Cuci Kering',
                    'layanan_tipe' => 'Layanan Mesin',
                    'kategori' => 'SELF_SERVICE',
                    'kuantitas' => ($row['total_berat'] > 0 ? $row['total_berat'] . ' kg' : $row['qty_count'] . ' item'),
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
    try {
        $conn = getConnection();
        if (!$conn) return null;

        @pg_query($conn, "BEGIN");

        // 1. Cari atau buat pelanggan di DB
        $wa = $data['wa'];
        $nama = $data['nama'];
        $p_res = @pg_query_params($conn, "SELECT id_pelanggan, kode_kartu FROM pelanggan WHERE no_hp = $1", [$wa]);
        $p_row = $p_res ? pg_fetch_assoc($p_res) : null;
        if ($p_row) {
            $id_pelanggan = (int)$p_row['id_pelanggan'];
            $kode_kartu = $p_row['kode_kartu'];
        } else {
            $ins_p = @pg_query_params($conn, "
                INSERT INTO pelanggan (nama_pelanggan, no_hp)
                VALUES ($1, $2)
                RETURNING id_pelanggan
            ", [$nama, $wa]);
            if (!$ins_p) {
                @pg_query($conn, "ROLLBACK");
                return null;
            }
            $id_pelanggan = (int)pg_fetch_result($ins_p, 0, 'id_pelanggan');
            $kode_kartu = 'KC-P-' . str_pad((string)$id_pelanggan, 4, '0', STR_PAD_LEFT);
            @pg_query_params($conn, "UPDATE pelanggan SET kode_kartu = $1 WHERE id_pelanggan = $2", [$kode_kartu, $id_pelanggan]);
        }

        // 2. Resolve kasir & shift_operasional aktif
        $kasir_name = $_SESSION['username'] ?? 'Sarah';
        $u_res = @pg_query_params($conn, "SELECT id_pengguna FROM pengguna WHERE nama ILIKE $1 OR username ILIKE $1", ["%$kasir_name%"]);
        $id_kasir = ($u_res && pg_num_rows($u_res) > 0) ? (int)pg_fetch_result($u_res, 0, 'id_pengguna') : 3;

        $so_res = @pg_query_params($conn, "
            SELECT id_shift_operasional, id_shift 
            FROM shift_operasional 
            WHERE id_kasir = $1 AND status = 'aktif' 
            ORDER BY id_shift_operasional DESC LIMIT 1
        ", [$id_kasir]);
        $id_so = null;
        $id_shift = 2; // Default siang
        if ($so_res && pg_num_rows($so_res) > 0) {
            $id_so = (int)pg_fetch_result($so_res, 0, 'id_shift_operasional');
            $id_shift = (int)pg_fetch_result($so_res, 0, 'id_shift');
        }

        // 3. Insert transaksi
        $no_invoice = 'INV-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $total_bayar = (int)$data['total_bayar'];
        $metode = strtolower($data['metode'] ?? 'cash');
        if (!in_array($metode, ['cash', 'qris'])) $metode = 'cash';

        $ins_t = @pg_query_params($conn, "
            INSERT INTO transaksi (
                id_kasir, tanggal_transaksi, id_pelanggan, id_shift, id_shift_operasional,
                jenis_pembayaran, total_bayar, jumlah_dibayar, sisa_bayar, uang_kembalian,
                status_pembayaran, status_pengerjaan, no_invoice
            ) VALUES (
                $1, NOW(), $2, $3, $4,
                $5, $6, $7, 0, 0,
                'lunas', 'antri', $8
            ) RETURNING id_transaksi
        ", [
            $id_kasir, $id_pelanggan, $id_shift, $id_so,
            $metode, $total_bayar, $total_bayar, $no_invoice
        ]);

        if (!$ins_t) {
            @pg_query($conn, "ROLLBACK");
            return null;
        }
        $id_transaksi = (int)pg_fetch_result($ins_t, 0, 'id_transaksi');

        // 4. Insert detail transaksi
        $id_layanan = (int)($data['service_id'] ?? 1);
        $qty = (int)($data['qty'] ?? 1);
        $koin_total = (int)(($data['koin_cuci'] ?? 0) + ($data['koin_kering'] ?? 0));
        $harga_satuan = (int)($data['tarif'] ?? $total_bayar);

        $ins_d = @pg_query_params($conn, "
            INSERT INTO detail_transaksi (
                id_transaksi, id_layanan, berat, jumlah, jumlah_koin, harga_satuan, subtotal
            ) VALUES (
                $1, $2, $3, $4, $5, $6, $7
            )
        ", [
            $id_transaksi, $id_layanan, 7.5, $qty, $koin_total, $harga_satuan, $total_bayar
        ]);

        if (!$ins_d) {
            @pg_query($conn, "ROLLBACK");
            return null;
        }

        @pg_query($conn, "COMMIT");

        // Sync ulang sesi
        sync_live_database();
        return $no_invoice;
    } catch (Throwable $e) {
        if (isset($conn) && $conn) @pg_query($conn, "ROLLBACK");
        return null;
    }
}

/**
 * Mendaftarkan pelanggan baru langsung ke Supabase PostgreSQL
 */
function db_save_member(string $nama, string $wa, ?string $password = null): ?array
{
    try {
        $conn = getConnection();
        if (!$conn) return null;

        $hash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
        $res = @pg_query_params($conn, "
            INSERT INTO pelanggan (nama_pelanggan, no_hp, password_hash)
            VALUES ($1, $2, $3)
            RETURNING id_pelanggan
        ", [$nama, $wa, $hash]);

        if (!$res) return null;
        $id = (int)pg_fetch_result($res, 0, 'id_pelanggan');
        $card = 'KC-P-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        @pg_query_params($conn, "UPDATE pelanggan SET kode_kartu = $1 WHERE id_pelanggan = $2", [$card, $id]);

        sync_live_database();
        return ['id' => $id, 'nama' => $nama, 'wa' => $wa, 'kode_kartu' => $card];
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Mulai shift kasir & simpan presensi ke Supabase
 */
function db_start_shift(string $kasir_name, int $shift_id = 2): bool
{
    try {
        $conn = getConnection();
        if (!$conn) return false;

        $u_res = @pg_query_params($conn, "SELECT id_pengguna FROM pengguna WHERE nama ILIKE $1 OR username ILIKE $1", ["%$kasir_name%"]);
        $id_kasir = ($u_res && pg_num_rows($u_res) > 0) ? (int)pg_fetch_result($u_res, 0, 'id_pengguna') : 3;

        // Tutup shift aktif sebelumnya jika ada yang tertinggal (status valid: 'ditutup')
        @pg_query_params($conn, "UPDATE shift_operasional SET status = 'ditutup', ditutup_pada = NOW() WHERE id_kasir = $1 AND status = 'aktif'", [$id_kasir]);

        $res = @pg_query_params($conn, "
            INSERT INTO shift_operasional (id_kasir, id_shift, mulai_pada, total_sistem, status)
            VALUES ($1, $2, NOW(), 0, 'aktif')
            RETURNING id_shift_operasional
        ", [$id_kasir, $shift_id]);

        sync_live_database();
        return $res !== false;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Tutup shift kasir & audit selisih kas fisik di Supabase
 * Mematuhi check constraint PostgreSQL: status IN ('aktif', 'ditutup')
 */
function db_close_shift(string $kasir_name, int $total_fisik = 0, int $selisih = 0): bool
{
    try {
        $conn = getConnection();
        if (!$conn) return false;

        $u_res = @pg_query_params($conn, "SELECT id_pengguna FROM pengguna WHERE nama ILIKE $1 OR username ILIKE $1", ["%$kasir_name%"]);
        $id_kasir = ($u_res && pg_num_rows($u_res) > 0) ? (int)pg_fetch_result($u_res, 0, 'id_pengguna') : null;

        $affected = 0;
        // 1. Coba tutup shift aktif milik kasir yang sedang login
        if ($id_kasir) {
            $res = @pg_query_params($conn, "
                UPDATE shift_operasional 
                SET status = 'ditutup', ditutup_pada = NOW(), total_fisik = $2, selisih = $3 
                WHERE id_kasir = $1 AND status = 'aktif'
            ", [$id_kasir, $total_fisik, $selisih]);
            $affected = $res ? pg_affected_rows($res) : 0;
        }

        // 2. Jika kasir ini bukan pembuka shift tapi ada shift toko yang sedang aktif, tutup shift aktif toko
        if ($affected === 0) {
            $res2 = @pg_query_params($conn, "
                UPDATE shift_operasional 
                SET status = 'ditutup', ditutup_pada = NOW(), total_fisik = $1, selisih = $2 
                WHERE id_shift_operasional = (
                    SELECT id_shift_operasional FROM shift_operasional 
                    WHERE status = 'aktif' 
                    ORDER BY id_shift_operasional DESC LIMIT 1
                )
            ", [$total_fisik, $selisih]);
            $affected = $res2 ? pg_affected_rows($res2) : 0;
        }

        sync_live_database();
        return $affected > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function format_rupiah(int $angka): string
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Mencari shift aktif kasir berdasarkan penugasan staf dan jam berjalan
 */
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
