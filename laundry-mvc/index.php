<?php

declare(strict_types=1);

use LaundryMvc\Config;
use LaundryMvc\Controllers\PelangganController;
use LaundryMvc\Models\Pelanggan;
use LaundryMvc\Models\Pesanan;
use function LaundryMvc\Config\flash;
use function LaundryMvc\Config\redirect;

session_start();
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/database/models/Pelanggan.php';
require_once __DIR__ . '/database/models/Pesanan.php';
require_once __DIR__ . '/controllers/PelangganController.php';

try {
    $pdo = Config\database();
} catch (Throwable $exception) {
    error_log($exception->__toString());
    http_response_code(500);
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>Database tidak tersedia</title>'
        . '<h1>Database tidak tersedia</h1><p>Periksa konfigurasi PostgreSQL dan coba lagi.</p></html>';
    exit;
}

$controller = new PelangganController(new Pelanggan($pdo), new Pesanan($pdo));
$pageInput = $_GET['page'] ?? 'home';
$page = is_string($pageInput) ? $pageInput : 'home';
$errors = [];
$old = ['nama_pelanggan' => '', 'no_hp' => ''];
$data = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!Config\csrfIsValid()) {
        flash('error', 'Sesi tidak valid, silakan ulangi.');
        redirect('?page=home');
    }

    $actionInput = $_POST['action'] ?? '';
    $action = is_string($actionInput) ? $actionInput : '';
    try {
        if ($action === 'tambah') {
            $result = $controller->tambah($_POST);
            if (isset($result['errors'])) {
                $page = 'baru';
                $errors = $result['errors'];
                $old = $result['old'];
            } else {
                $customer = $result['pelanggan'];
                flash(
                    $result['existing'] ? 'info' : 'success',
                    $result['existing']
                        ? 'No HP sudah terdaftar. Data pelanggan lama ditampilkan.'
                        : 'Pelanggan berhasil didaftarkan. Ini QR miliknya.'
                );
                redirect('?page=detail&kode=' . urlencode($customer['kode_pelanggan']));
            }
        } elseif ($action === 'order') {
            $result = $controller->simpanPesanan($_POST);
            if (isset($result['errors'])) {
                flash('error', implode(' ', $result['errors']));
                $customer = $result['pelanggan'] ?? null;
                redirect($customer === null
                    ? '?page=scan'
                    : '?page=detail&kode=' . urlencode($customer['kode_pelanggan']));
            }
            flash('success', 'Pesanan tersimpan. Total Rp' . number_format($result['total'], 0, ',', '.') . '.');
            redirect('?page=detail&kode=' . urlencode($result['kode']));
        } elseif ($action === 'status') {
            $result = $controller->ubahStatus($_POST);
            if (isset($result['error'])) {
                flash('error', $result['error']);
            } else {
                flash('success', 'Status pesanan diubah menjadi ' . $result['status'] . '.');
            }
            $code = $result['kode'] ?? '';
            redirect($code !== ''
                ? '?page=detail&kode=' . urlencode($code)
                : '?page=home');
        } else {
            flash('error', 'Aksi tidak dikenali.');
            redirect('?page=home');
        }
    } catch (Throwable $exception) {
        error_log($exception->__toString());
        flash('error', 'Permintaan gagal diproses. Silakan coba kembali.');
        redirect($page === 'baru' ? '?page=baru' : '?page=home');
    }
}

try {
    switch ($page) {
        case 'baru':
            require __DIR__ . '/views/pelanggan_baru.php';
            break;
        case 'scan':
            require __DIR__ . '/views/scan_qr.php';
            break;
        case 'detail':
            $code = $_GET['kode'] ?? '';
            $data = $controller->detail(is_string($code) ? $code : '');
            if ($data === null) {
                flash('error', 'QR atau kode pelanggan tidak dikenali.');
                redirect('?page=scan');
            }
            require __DIR__ . '/views/detail_pelanggan.php';
            break;
        case 'home':
        default:
            $data = $controller->beranda();
            require __DIR__ . '/views/home.php';
            break;
    }
} catch (Throwable $exception) {
    error_log($exception->__toString());
    http_response_code(500);
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>Terjadi kesalahan</title>'
        . '<h1>Halaman tidak dapat ditampilkan</h1><p>Periksa log aplikasi untuk detail kesalahan.</p></html>';
}
