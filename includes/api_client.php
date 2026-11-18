<?php
/**
 * Klien API backend (in-process).
 *
 * Frontend memanggil controller backend yang sama dengan yang dipakai
 * api/index.php, tanpa HTTP request tambahan. Tujuannya: aturan bisnis
 * (validasi, harga, hak akses, transaksi database) hanya ada di backend.
 *
 * Catatan: pemanggilan lewat HTTP tidak dipakai karena `php -S` bawaan
 * hanya punya satu worker — request bersarang akan deadlock.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/models/BaseModel.php';
require_once __DIR__ . '/../backend/models/Layanan.php';
require_once __DIR__ . '/../backend/models/Pengguna.php';
require_once __DIR__ . '/../backend/models/Pelanggan.php';
require_once __DIR__ . '/../backend/models/Shift.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function api_controller_file(string $module): ?string
{
    $map = [
        'transaksi'         => 'TransaksiController.php',
        'detail-transaksi'  => 'DetailTransaksiController.php',
        'pelanggan'         => 'PelangganController.php',
        'pengguna'          => 'PenggunaController.php',
        'layanan'           => 'LayananController.php',
        'shift'             => 'ShiftController.php',
        'shift-operasional' => 'ShiftOperasionalController.php',
        'dashboard'         => 'DashboardRekapController.php',
        'rekap'             => 'DashboardRekapController.php',
        'riwayat'           => 'RiwayatController.php',
    ];
    $file = $map[$module] ?? null;

    return $file ? __DIR__ . '/../backend/controllers/' . $file : null;
}

/**
 * @return array{status:int, data:array}
 */
function api_call(string $module, string $action, string $method = 'GET', array $query = [], ?array $body = null): array
{
    $file = api_controller_file($module);
    if (!$file || !is_file($file)) {
        return ['status' => 404, 'data' => ['error' => 'Endpoint tidak ditemukan']];
    }

    $prevGet    = $_GET;
    $prevMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $prevToken  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $prevInproc = $GLOBALS['kino_api_inproc'] ?? null;
    $prevBody   = $GLOBALS['kino_api_body'] ?? null;

    $_GET = array_merge($query, ['module' => $module, 'action' => $action]);
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['HTTP_X_CSRF_TOKEN'] = (string) ($_SESSION['csrf'] ?? '');

    $GLOBALS['kino_api_inproc'] = true;
    $GLOBALS['kino_api_body']   = $body ?? [];

    $status = 200;
    $payload = [];

    try {
        (static function (string $controllerFile, string $module, string $action): void {
            include $controllerFile;
        })($file, $module, $action);
    } catch (ApiResponse $e) {
        $status  = $e->status;
        $payload = $e->payload;
    } catch (Throwable $e) {
        $status  = 500;
        $payload = ['error' => $e->getMessage()];
    } finally {
        $GLOBALS['kino_api_inproc'] = $prevInproc;
        $GLOBALS['kino_api_body']   = $prevBody;
        $_GET = $prevGet;
        $_SERVER['REQUEST_METHOD'] = $prevMethod;
        if ($prevToken === null) {
            unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        } else {
            $_SERVER['HTTP_X_CSRF_TOKEN'] = $prevToken;
        }
    }

    return ['status' => $status, 'data' => $payload];
}

function api_ok(array $response): bool
{
    return $response['status'] >= 200 && $response['status'] < 300;
}

function api_error_message(array $response): string
{
    return (string) ($response['data']['error'] ?? 'Terjadi kesalahan pada backend');
}
