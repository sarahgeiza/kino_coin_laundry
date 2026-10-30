<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/customer_access.php';

$token = $_GET['token'] ?? null;
$customerId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

try {
    if (is_string($token) && $customerId !== false) {
        $customer = \Kino\CustomerAccess\authenticateMember((int) $customerId, $token);
        if ($customer === null) {
            http_response_code(403);
            echo '<!doctype html><html lang="id"><meta charset="utf-8"><h1>QR member tidak valid</h1>'
                . '<p>QR mungkin sudah kedaluwarsa. Minta kasir membuat QR baru.</p></html>';
            exit;
        }

        session_regenerate_id(true);
        unset($_SESSION['user_id'], $_SESSION['user_role']);
        $_SESSION['customer_id'] = (int) $customer['id_pelanggan'];
        header('Location: index.php');
        exit;
    }

    $customer = \Kino\CustomerAccess\currentCustomer();
    if ($customer === null) {
        header('Location: login.php');
        exit;
    }
    $transactions = \Kino\CustomerAccess\customerTransactions((int) $customer['id_pelanggan']);
} catch (Throwable $exception) {
    error_log($exception->__toString());
    http_response_code(500);
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><h1>Halaman pelanggan tidak tersedia</h1>'
        . '<p>Periksa konfigurasi database dan coba kembali.</p></html>';
    exit;
}

$page_title = 'Akun Pelanggan - Kino Coin Laundry';
$header_role = 'Pelanggan';
$header_user = $customer['nama_pelanggan'];
$header_avatar = mb_strtoupper(mb_substr($customer['nama_pelanggan'], 0, 1));
$header_theme = 'orange';
$header_title = 'Selamat datang, ' . $customer['nama_pelanggan'];
$header_subtitle = 'Akun member Kino Coin Laundry';
$current_role = 'pelanggan';
$active_tab = 'home';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-title">Profil Member</div>
    <p><strong>Nama:</strong> <?= htmlspecialchars($customer['nama_pelanggan'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>No. HP:</strong> <?= htmlspecialchars($customer['no_hp'], ENT_QUOTES, 'UTF-8') ?></p>
</div>

<div class="card">
    <div class="card-title">Transaksi Terbaru</div>
    <?php if ($transactions === []): ?>
        <p style="font-size:13px;color:var(--text-muted)">Belum ada transaksi tersimpan untuk akun ini.</p>
    <?php else: ?>
        <?php foreach (array_slice($transactions, 0, 5) as $transaction): ?>
            <div class="transaction-item">
                <div class="trx-header">
                    <div>
                        <span class="trx-id">INV-<?= (int) $transaction['id_transaksi'] ?></span>
                        <div class="trx-customer">
                            <?= htmlspecialchars($transaction['nama_layanan'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($transaction['berat'] !== null): ?>
                                (<?= htmlspecialchars((string) $transaction['berat'], ENT_QUOTES, 'UTF-8') ?> kg)
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="trx-footer">
                    <div>
                        <div class="trx-amount">Rp <?= number_format((int) $transaction['total_bayar'], 0, ',', '.') ?></div>
                        <div class="trx-meta"><?= htmlspecialchars($transaction['tanggal_transaksi'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
