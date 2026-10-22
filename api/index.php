<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
ini_set('display_errors', '0');
require __DIR__.'/../backend/config/database.php';
require __DIR__.'/../includes/helpers.php';
require __DIR__.'/../includes/validation.php';
require __DIR__.'/../backend/models/BaseModel.php';
require __DIR__.'/../backend/models/Pengguna.php';
require __DIR__.'/../backend/models/Pelanggan.php';
require __DIR__.'/../backend/models/Layanan.php';
require __DIR__.'/../backend/models/Shift.php';
require __DIR__.'/../backend/models/Transaksi.php';
require __DIR__.'/../backend/models/DetailTransaksi.php';
require __DIR__.'/../backend/middleware/session.php';
try {
    $module = (string)($_GET['module'] ?? '');
    $action = (string)($_GET['action'] ?? 'list');
    if ($module === 'health' && $action === 'list') { method('GET'); respond(['status'=>'ok','app'=>'Kino Coin Laundry API']); }
    require __DIR__.'/../backend/controllers/AuthController.php';
    require __DIR__.'/../backend/controllers/RiwayatController.php';
    $u=actor();
    if (in_array($module,['pengguna','layanan','shift'],true) && in_array($action,['create','update','delete','password'],true)) owner();
    require __DIR__.'/../backend/controllers/PenggunaController.php';
    require __DIR__.'/../backend/controllers/PelangganController.php';
    require __DIR__.'/../backend/controllers/LayananController.php';
    require __DIR__.'/../backend/controllers/ShiftController.php';
    require __DIR__.'/../backend/controllers/TransaksiController.php';
    require __DIR__.'/../backend/controllers/DetailTransaksiController.php';
    require __DIR__.'/../backend/controllers/DashboardRekapController.php';
    respond(['error'=>'Endpoint tidak ditemukan'],404);
} catch (PDOException $e) {
    error_log($e->getMessage());
    if ($e->getCode()==='23505') respond(['error'=>'Data unik sudah digunakan'],409);
    if ($e->getCode()==='23503') respond(['error'=>'Referensi tidak ditemukan atau masih digunakan'],409);
    if ($e->getCode()==='23514') respond(['error'=>'Data melanggar constraint database'],422);
    respond(['error'=>'Terjadi kesalahan database'],500);
} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(['error'=>'Terjadi kesalahan server'],500);
}
