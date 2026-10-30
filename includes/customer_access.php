<?php

declare(strict_types=1);

namespace Kino\CustomerAccess;

use RuntimeException;

require_once __DIR__ . '/../backend/config/database.php';

final class CustomerInputException extends RuntimeException
{
}

function applicationKey(): string
{
    $key = getenv('APP_KEY');
    if ($key === false || strlen($key) < 32) {
        throw new RuntimeException('APP_KEY belum diatur atau panjangnya kurang dari 32 karakter.');
    }

    return $key;
}

function normalizePhone(string $phone): string
{
    $phone = preg_replace('/\D/', '', $phone) ?? '';
    if (str_starts_with($phone, '62')) {
        $phone = '0' . substr($phone, 2);
    }

    return $phone;
}

function memberToken(int $customerId): string
{
    return hash_hmac(
        'sha256',
        'kino-laundry-customer:' . $customerId,
        applicationKey()
    );
}

function registerOrFind(string $name, string $phone): array
{
    $name = trim($name);
    $phone = normalizePhone($phone);

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        throw new CustomerInputException('Nama harus 2 sampai 100 karakter.');
    }
    if (!preg_match('/^08[0-9]{8,12}$/', $phone)) {
        throw new CustomerInputException('Nomor HP tidak valid. Contoh: 081234567890.');
    }

    $conn = \getConnection();
    if (pg_query($conn, 'BEGIN') === false) {
        throw new RuntimeException('Gagal memulai penyimpanan data pelanggan.');
    }

    try {
        $result = pg_query_params(
            $conn,
            'SELECT id_pelanggan, nama_pelanggan, no_hp
             FROM pelanggan
             WHERE no_hp = $1
             FOR UPDATE',
            [$phone]
        );
        if ($result === false) {
            throw new RuntimeException('Gagal mencari pelanggan: ' . pg_last_error($conn));
        }

        $customer = pg_fetch_assoc($result);
        $isNew = $customer === false;

        if ($isNew) {
            $result = pg_query_params(
                $conn,
                'INSERT INTO pelanggan (nama_pelanggan, no_hp)
                 VALUES ($1, $2)
                 RETURNING id_pelanggan, nama_pelanggan, no_hp',
                [$name, $phone]
            );
            if ($result === false) {
                throw new RuntimeException('Gagal menyimpan pelanggan: ' . pg_last_error($conn));
            }
            $customer = pg_fetch_assoc($result);
        }

        if (!is_array($customer)) {
            throw new RuntimeException('Data pelanggan tidak tersedia setelah disimpan.');
        }

        $customerId = (int) $customer['id_pelanggan'];
        $token = memberToken($customerId);
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $result = pg_query_params(
            $conn,
            'INSERT INTO akses_pelanggan (id_pelanggan, kode_hash, berlaku_sampai)
             VALUES ($1, $2, CURRENT_TIMESTAMP + INTERVAL \'5 years\')
             ON CONFLICT (id_pelanggan) DO UPDATE
             SET kode_hash = EXCLUDED.kode_hash,
                 berlaku_sampai = EXCLUDED.berlaku_sampai',
            [$customerId, $tokenHash]
        );
        if ($result === false) {
            throw new RuntimeException('Gagal menyiapkan QR member: ' . pg_last_error($conn));
        }

        if (pg_query($conn, 'COMMIT') === false) {
            throw new RuntimeException('Gagal menyelesaikan pendaftaran pelanggan.');
        }

        return [
            'id_pelanggan' => $customerId,
            'nama_pelanggan' => $customer['nama_pelanggan'],
            'no_hp' => $customer['no_hp'],
            'token' => $token,
            'is_new' => $isNew,
        ];
    } catch (\Throwable $exception) {
        pg_query($conn, 'ROLLBACK');
        throw $exception;
    }
}

function memberUrl(int $customerId, string $token): string
{
    $configuredBase = getenv('APP_BASE_URL');
    if ($configuredBase !== false && trim($configuredBase) !== '') {
        $parts = parse_url(trim($configuredBase));
        if (
            $parts === false
            || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            throw new RuntimeException('APP_BASE_URL harus berupa URL aplikasi http:// atau https:// yang valid.');
        }
        $base = rtrim(trim($configuredBase), '/');
    } else {
        $host = (string) ($_SERVER['SERVER_NAME'] ?? 'localhost');
        if (!preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
            $host = 'localhost';
        }

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $port = (int) ($_SERVER['SERVER_PORT'] ?? ($https ? 443 : 80));
        $portSuffix = in_array($port, [80, 443], true) ? '' : ':' . $port;
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/kasir/transaksi-baru.php'));
        $basePath = dirname(dirname($script));
        $basePath = $basePath === '/' || $basePath === '.' ? '' : '/' . trim($basePath, '/');
        $base = $scheme . '://' . $host . $portSuffix . $basePath;
    }

    return $base . '/pelanggan/index.php?id=' . $customerId . '&token=' . rawurlencode($token);
}

function customerById(int $customerId): ?array
{
    $result = pg_query_params(
        \getConnection(),
        'SELECT p.id_pelanggan, p.nama_pelanggan, p.no_hp
         FROM pelanggan p
         JOIN akses_pelanggan a USING (id_pelanggan)
         WHERE p.id_pelanggan = $1
           AND a.berlaku_sampai > CURRENT_TIMESTAMP',
        [$customerId]
    );
    if ($result === false) {
        throw new RuntimeException('Gagal membaca profil pelanggan.');
    }

    $customer = pg_fetch_assoc($result);

    return $customer === false ? null : $customer;
}

function authenticateMember(int $customerId, string $token): ?array
{
    if ($customerId < 1 || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    $result = pg_query_params(
        \getConnection(),
        'SELECT p.id_pelanggan, p.nama_pelanggan, p.no_hp, a.kode_hash
         FROM pelanggan p
         JOIN akses_pelanggan a USING (id_pelanggan)
         WHERE p.id_pelanggan = $1
           AND a.berlaku_sampai > CURRENT_TIMESTAMP',
        [$customerId]
    );
    if ($result === false) {
        throw new RuntimeException('Gagal memvalidasi QR member.');
    }

    $customer = pg_fetch_assoc($result);
    if ($customer === false || !password_verify($token, $customer['kode_hash'])) {
        return null;
    }

    unset($customer['kode_hash']);

    return $customer;
}

function currentCustomer(): ?array
{
    $customerId = filter_var(
        $_SESSION['customer_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($customerId === false) {
        return null;
    }

    return customerById((int) $customerId);
}

function customerTransactions(int $customerId): array
{
    $result = pg_query_params(
        \getConnection(),
        'SELECT t.id_transaksi, t.tanggal_transaksi, t.jenis_pembayaran,
                t.total_bayar, d.berat, l.nama_layanan
         FROM transaksi t
         JOIN detail_transaksi d USING (id_transaksi)
         JOIN layanan l USING (id_layanan)
         WHERE t.id_pelanggan = $1
         ORDER BY t.id_transaksi DESC',
        [$customerId]
    );
    if ($result === false) {
        throw new RuntimeException('Gagal membaca riwayat transaksi pelanggan.');
    }

    return pg_fetch_all($result) ?: [];
}
