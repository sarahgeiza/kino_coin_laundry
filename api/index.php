<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/validation.php';

try {
    require_once __DIR__ . '/../backend/config/database.php';

    require_once __DIR__ . '/../backend/middleware/session.php';

    require_once __DIR__ . '/../backend/models/BaseModel.php';
    require_once __DIR__ . '/../backend/models/Pengguna.php';
    require_once __DIR__ . '/../backend/models/Pelanggan.php';
    require_once __DIR__ . '/../backend/models/Layanan.php';
    require_once __DIR__ . '/../backend/models/Shift.php';
    require_once __DIR__ . '/../backend/models/Transaksi.php';
    require_once __DIR__ . '/../backend/models/DetailTransaksi.php';

    $module = (string) ($_GET['module'] ?? '');
    $action = (string) ($_GET['action'] ?? 'list');

    // Pemeriksaan koneksi API
    if ($module === 'health' && $action === 'list') {
        method('GET');
        respond([
            'status' => 'ok',
            'app' => 'Kino Coin Laundry API'
        ]);
    }

    // Endpoint autentikasi tidak membutuhkan login terlebih dahulu
    require_once __DIR__ . '/../backend/controllers/AuthController.php';

    // Endpoint khusus pelanggan
    require_once __DIR__ . '/../backend/controllers/RiwayatController.php';
    require_once __DIR__ . '/../backend/controllers/PelangganSelfController.php';

    // Endpoint lainnya membutuhkan autentikasi
    $user = actor();
    $role = strtolower(trim((string) ($user['role'] ?? '')));

    // Pelanggan hanya boleh mengakses fitur miliknya
    if (
        $role === 'pelanggan' &&
        !in_array($module, ['pelanggan-self', 'riwayat'], true)
    ) {
        respond([
            'error' => 'Pelanggan tidak memiliki akses ke resource ini'
        ], 403);
    }

    // Pengelolaan data tertentu hanya untuk pemilik
    if (
        in_array($module, ['pengguna', 'layanan', 'shift'], true) &&
        in_array(
            $action,
            ['create', 'update', 'delete', 'password', 'toggle'],
            true
        )
    ) {
        owner();
    }

    // Pengelolaan data pelanggan hanya untuk pemilik
    if ($module === 'pelanggan' && $role !== 'pemilik') {
        respond([
            'error' => 'Akses hanya untuk pemilik'
        ], 403);
    }

    // Controller lainnya
    require_once __DIR__ . '/../backend/controllers/PenggunaController.php';
    require_once __DIR__ . '/../backend/controllers/PelangganController.php';
    require_once __DIR__ . '/../backend/controllers/LayananController.php';
    require_once __DIR__ . '/../backend/controllers/ShiftController.php';
    require_once __DIR__ . '/../backend/controllers/ShiftOperasionalController.php';
    require_once __DIR__ . '/../backend/controllers/TransaksiController.php';
    require_once __DIR__ . '/../backend/controllers/DetailTransaksiController.php';
    require_once __DIR__ . '/../backend/controllers/DashboardRekapController.php';

    respond([
        'error' => 'Endpoint tidak ditemukan'
    ], 404);

} catch (Throwable $e) {
    error_log($e->getMessage());

    respond([
        'error' => 'Terjadi kesalahan server'
    ], 500);
}
