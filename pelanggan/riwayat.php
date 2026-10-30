<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/customer_access.php';

try {
    $customer = \Kino\CustomerAccess\currentCustomer();
    if ($customer === null) {
        header('Location: login.php');
        exit;
    }
    $transactions = \Kino\CustomerAccess\customerTransactions((int) $customer['id_pelanggan']);
} catch (Throwable $exception) {
    error_log($exception->__toString());
    http_response_code(500);
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><h1>Riwayat belum dapat ditampilkan</h1></html>';
    exit;
}

$page_title = 'Riwayat Transaksi - Kino Coin Laundry';
$header_role = 'Pelanggan';
$header_user = $customer['nama_pelanggan'];
$header_avatar = mb_strtoupper(mb_substr($customer['nama_pelanggan'], 0, 1));
$header_theme = 'orange';
$header_title = 'Riwayat Transaksi';
$header_subtitle = 'Daftar transaksi cucian Anda di Kino Laundry';
$current_role = 'pelanggan';
$active_tab = 'riwayat';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-title">
        <span>Riwayat <?= htmlspecialchars($customer['nama_pelanggan'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <?php if ($transactions === []): ?>
        <p style="font-size:13px;color:var(--text-muted);text-align:center;padding:20px">
            Belum ada transaksi tersimpan untuk akun ini.
        </p>
    <?php else: ?>
        <?php foreach ($transactions as $transaction): ?>
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

<a href="index.php" class="btn btn-outline">Kembali ke Profil</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
