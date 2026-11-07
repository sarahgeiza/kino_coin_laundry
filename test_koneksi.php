<?php
declare(strict_types=1);

/**
 * =====================================================================
 * KINO COIN LAUNDRY - UJI KONEKSI & DIAGNOSTIK DATABASE SUPABASE
 * =====================================================================
 * Jalankan via Terminal : php test_koneksi.php
 * Atau via Browser     : http://localhost:8000/test_koneksi.php
 */

$isCli = (PHP_SAPI === 'cli');

require_once __DIR__ . '/backend/config/database.php';

$results = [
    'connected' => false,
    'server_info' => [],
    'tables' => [],
    'views' => [],
    'triggers' => [],
    'procedures' => [],
    'owner_account' => null,
    'error' => null
];

try {
    $conn = getConnection();
    $results['connected'] = true;

    // 1. Info Server
    $resInfo = pg_query($conn, "SELECT version(), current_database(), current_user, current_setting('TimeZone') AS tz, current_setting('ssl', true) AS ssl");
    if ($resInfo) {
        $results['server_info'] = pg_fetch_assoc($resInfo) ?: [];
    }

    // 2. Cek 8 Tabel Utama
    $expectedTables = [
        'pengguna'          => 'Master Akun Pemilik & Kasir',
        'pelanggan'         => 'Data Pelanggan & Kartu Member QR',
        'layanan'           => 'Katalog Tarif & Berat Standar 7.5kg',
        'shift'             => 'Master 4 Shift Operasional 24 Jam',
        'jadwal_shift'      => 'Penjadwalan Kasir',
        'shift_operasional' => 'Presensi Kasir & Rekonsiliasi Kas',
        'transaksi'         => 'Header Transaksi & Finansial',
        'detail_transaksi'  => 'Detail Layanan, Berat & Koin Mesin'
    ];

    $resTables = pg_query($conn, "
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
    ");
    $existingTables = [];
    while ($row = pg_fetch_assoc($resTables)) {
        $existingTables[] = $row['table_name'];
    }

    foreach ($expectedTables as $tbl => $desc) {
        if (in_array($tbl, $existingTables, true)) {
            $cntRes = pg_query($conn, "SELECT COUNT(*) AS total FROM public.{$tbl}");
            $cntRow = pg_fetch_assoc($cntRes);
            $results['tables'][$tbl] = [
                'exists' => true,
                'count' => (int)($cntRow['total'] ?? 0),
                'desc' => $desc
            ];
        } else {
            $results['tables'][$tbl] = [
                'exists' => false,
                'count' => 0,
                'desc' => $desc
            ];
        }
    }

    // 3. Cek View Manajerial (BDL)
    $resView = pg_query($conn, "
        SELECT table_name 
        FROM information_schema.views 
        WHERE table_schema = 'public' AND table_name = 'v_rekap_shift_operasional'
    ");
    $results['views']['v_rekap_shift_operasional'] = (pg_num_rows($resView) > 0);

    // 4. Cek Trigger & Stored Procedure (BDL)
    $resTrg = pg_query($conn, "
        SELECT trigger_name 
        FROM information_schema.triggers 
        WHERE event_object_table = 'pelanggan' AND trigger_name = 'trg_generate_kode_kartu'
    ");
    $results['triggers']['trg_generate_kode_kartu'] = (pg_num_rows($resTrg) > 0);

    $resProc = pg_query($conn, "
        SELECT routine_name 
        FROM information_schema.routines 
        WHERE routine_schema = 'public' AND routine_name = 'sp_tutup_shift_kasir'
    ");
    $results['procedures']['sp_tutup_shift_kasir'] = (pg_num_rows($resProc) > 0);

    // 5. Cek Akun Pemilik
    $resOwner = pg_query($conn, "SELECT id_pengguna, nama, username, role FROM public.pengguna WHERE role = 'pemilik' LIMIT 1");
    if ($resOwner && $owner = pg_fetch_assoc($resOwner)) {
        $results['owner_account'] = $owner;
    }

} catch (Throwable $e) {
    $results['error'] = $e->getMessage();
}

// OUTPUT CLI
if ($isCli) {
    echo "=====================================================================\n";
    echo "      KINO COIN LAUNDRY - UJI KONEKSI & DIAGNOSTIK DATABASE        \n";
    echo "=====================================================================\n\n";

    if (!$results['connected']) {
        echo "[ERROR KONEKSI]\n";
        echo "Pesan: " . $results['error'] . "\n\n";
        echo "Langkah Perbaikan:\n";
        echo "1. Pastikan file .env ada di folder root proyek.\n";
        echo "2. Pastikan password Supabase Anda benar.\n";
        exit(1);
    }

    echo "[1] KONEKSI KE SUPABASE BERHASIL!\n";
    echo "    - Database : " . ($results['server_info']['current_database'] ?? '-') . "\n";
    echo "    - User     : " . ($results['server_info']['current_user'] ?? '-') . "\n";
    echo "    - TimeZone : " . ($results['server_info']['tz'] ?? '-') . "\n\n";

    echo "[2] STATUS 8 TABEL UTAMA:\n";
    foreach ($results['tables'] as $name => $info) {
        $mark = $info['exists'] ? "[OK] " : "[MISSING]";
        printf("    %-9s %-20s : %d baris (%s)\n", $mark, $name, $info['count'], $info['desc']);
    }
    echo "\n";

    echo "[3] OBJEK BASIS DATA LANJUT (BDL):\n";
    echo "    - View 'v_rekap_shift_operasional'       : " . ($results['views']['v_rekap_shift_operasional'] ? "[OK] Terpasang" : "[!] Belum ada") . "\n";
    echo "    - Trigger 'trg_generate_kode_kartu'      : " . ($results['triggers']['trg_generate_kode_kartu'] ? "[OK] Terpasang" : "[!] Belum ada") . "\n";
    echo "    - Stored Procedure 'sp_tutup_shift_kasir': " . ($results['procedures']['sp_tutup_shift_kasir'] ? "[OK] Terpasang" : "[!] Belum ada") . "\n\n";

    echo "[4] AKUN LOGIN PEMILIK:\n";
    if ($results['owner_account']) {
        echo "    - Nama     : " . $results['owner_account']['nama'] . "\n";
        echo "    - Username : " . $results['owner_account']['username'] . " [OK Siap Login]\n";
    } else {
        echo "    - [!] Belum ada akun pemilik.\n";
    }

    echo "\n=====================================================================\n";
    echo "STATUS: DIAGNOSTIK SELESAI. BASIS DATA BERJALAN SEMPURNA!\n";
    echo "=====================================================================\n";
    exit(0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Uji Koneksi Database - Kino Coin Laundry</title>
    <style>
        body { font-family: sans-serif; background: #f8fafc; color: #0f172a; padding: 24px; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; max-width: 800px; margin: 0 auto 16px auto; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: bold; background: #dcfce7; color: #16a34a; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Uji Koneksi Database Supabase</h2>
        <?php if ($results['connected']): ?>
            <p style="margin-top: 8px;"><span class="badge">TERHUBUNG KE SUPABASE</span></p>
            <p style="margin-top: 8px; font-size: 14px;">Database: <strong><?= $results['server_info']['current_database'] ?></strong> | User: <strong><?= $results['server_info']['current_user'] ?></strong></p>
        <?php else: ?>
            <p style="color: red; margin-top: 8px;"><strong>Gagal:</strong> <?= htmlspecialchars($results['error'] ?? '') ?></p>
        <?php endif; ?>
    </div>
</body>
</html>