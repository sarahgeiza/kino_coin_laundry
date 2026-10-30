<?php

declare(strict_types=1);

namespace LaundryMvc\Config;

const LAYANAN = [
    'cuci' => ['nama' => 'Cuci', 'harga' => 10000],
    'kering' => ['nama' => 'Kering', 'harga' => 8000],
    'cuci_kering' => ['nama' => 'Cuci + Kering', 'harga' => 17000],
    'sabun' => ['nama' => 'Sabun/Pewangi', 'harga' => 3000],
];

const STATUS_LIST = ['Proses', 'Selesai', 'Diambil'];

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah(int|string $amount): string
{
    return 'Rp' . number_format((int) $amount, 0, ',', '.');
}

function tanggal(string $value): string
{
    return date('d/m/Y H:i', strtotime($value));
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
}

function consumeFlash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($message) ? $message : null;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function csrfIsValid(): bool
{
    return isset($_SESSION['csrf'], $_POST['csrf'])
        && is_string($_SESSION['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function normalisasiHp(string $phone): string
{
    $phone = preg_replace('/\D/', '', $phone) ?? '';
    if (str_starts_with($phone, '62')) {
        $phone = '0' . substr($phone, 2);
    }

    return $phone;
}

function linkWhatsApp(string $phone, string $message): string
{
    return 'https://wa.me/62' . substr($phone, 1) . '?text=' . rawurlencode($message);
}
