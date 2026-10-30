<?php

declare(strict_types=1);
namespace LaundryMvc\Controllers;

use LaundryMvc\Models\Pelanggan;
use LaundryMvc\Models\Pesanan;
use function LaundryMvc\Config\normalisasiHp;
use const LaundryMvc\Config\LAYANAN;
use const LaundryMvc\Config\STATUS_LIST;

final class PelangganController
{
    public function __construct(
        private Pelanggan $pelanggan,
        private Pesanan $pesanan
    ) {
    }

    public function beranda(): array
    {
        $summary = $this->pesanan->todaySummary();

        return [
            'totalPelanggan' => $this->pelanggan->count(),
            'pesananHariIni' => (int) $summary['jumlah'],
            'omzetHariIni' => (int) $summary['omzet'],
            'sedangProses' => $this->pesanan->countInProgress(),
            'terbaru' => $this->pesanan->latest(),
        ];
    }

    public function detail(string $code): ?array
    {
        $customer = $this->pelanggan->findByCode($code);
        if ($customer === null) {
            return null;
        }

        return [
            'pelanggan' => $customer,
            'riwayat' => $this->pesanan->history((int) $customer['id_pelanggan']),
            'ringkasan' => $this->pesanan->customerSummary((int) $customer['id_pelanggan']),
        ];
    }

    public function tambah(array $input): array
    {
        $nameInput = $input['nama_pelanggan'] ?? '';
        $phoneInput = $input['no_hp'] ?? '';
        $name = is_string($nameInput) ? trim($nameInput) : '';
        $rawPhone = is_string($phoneInput) ? $phoneInput : '';
        $phone = normalisasiHp($rawPhone);
        $errors = [];

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors[] = 'Nama harus 2 sampai 100 karakter.';
        }
        if (!preg_match('/^08[0-9]{8,12}$/', $phone)) {
            $errors[] = 'No HP tidak valid. Contoh: 081234567890';
        }

        if ($errors !== []) {
            return [
                'errors' => $errors,
                'old' => ['nama_pelanggan' => $name, 'no_hp' => $rawPhone],
            ];
        }

        $existing = $this->pelanggan->findByPhone($phone);
        if ($existing !== null) {
            return ['pelanggan' => $existing, 'existing' => true];
        }

        $customer = $this->pelanggan->create($name, $phone);
        if ($customer === null) {
            $customer = $this->pelanggan->findByPhone($phone);
            if ($customer === null) {
                throw new \RuntimeException('Pelanggan tidak dapat disimpan.');
            }

            return ['pelanggan' => $customer, 'existing' => true];
        }

        return ['pelanggan' => $customer, 'existing' => false];
    }

    public function simpanPesanan(array $input): array
    {
        $customerId = filter_var(
            $input['id_pelanggan'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $serviceInput = $input['layanan'] ?? '';
        $service = is_string($serviceInput) ? $serviceInput : '';
        
        $quantity = filter_var(
            $input['jumlah'] ?? null,
            FILTER_VALIDATE_INT
        );
        
        // Penambahan tangkap input berat_kg
        $beratKg = filter_var(
            $input['berat_kg'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $noteInput = $input['catatan'] ?? '';
        $note = is_string($noteInput) ? trim($noteInput) : '';

        // Penambahan validasi berat_kg tidak boleh kosong/nol/negatif
        if ($customerId === false || $quantity === false || $beratKg === false || $beratKg <= 0) {
            return ['errors' => ['Pelanggan, jumlah, atau berat pesanan tidak valid.']];
        }

        $customer = $this->pelanggan->findById((int) $customerId);
        if ($customer === null) {
            return ['errors' => ['Pelanggan tidak ditemukan.']];
        }

        // Penambahan validasi batas maksimal mesin 7.5 kg
        if ($beratKg > 7.5) {
            return [
                'errors' => ['Peringatan: Berat cucian (' . $beratKg . ' kg) melebihi batas maksimal mesin 7.5 kg!'],
                'pelanggan' => $customer,
            ];
        }

        if (!isset(LAYANAN[$service]) || $quantity < 1 || $quantity > 50) {
            return [
                'errors' => ['Layanan atau jumlah tidak valid.'],
                'pelanggan' => $customer,
            ];
        }
        if (mb_strlen($note) > 255) {
            return [
                'errors' => ['Catatan maksimal 255 karakter.'],
                'pelanggan' => $customer,
            ];
        }

        $total = LAYANAN[$service]['harga'] * $quantity;
        
        // Penambahan $beratKg ke fungsi create
        $this->pesanan->create(
            (int) $customerId,
            $service,
            $beratKg,
            $quantity,
            $total,
            mb_substr($note, 0, 255)
        );

        return [
            'kode' => $customer['kode_pelanggan'],
            'total' => $total,
        ];
    }

    public function ubahStatus(array $input): array
    {
        $orderId = filter_var(
            $input['id_pesanan'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $statusInput = $input['status'] ?? '';
        $codeInput = $input['kode_pelanggan'] ?? '';
        $status = is_string($statusInput) ? $statusInput : '';
        $code = is_string($codeInput) ? trim($codeInput) : '';

        if ($orderId === false || !in_array($status, STATUS_LIST, true)) {
            return ['error' => 'ID pesanan atau status tidak valid.'];
        }

        $customerId = null;
        if ($code !== '') {
            $customer = $this->pelanggan->findByCode($code);
            if ($customer === null) {
                return ['error' => 'Pelanggan tidak ditemukan.'];
            }
            $customerId = (int) $customer['id_pelanggan'];
        }

        if (!$this->pesanan->updateStatus((int) $orderId, $status, $customerId)) {
            return ['error' => 'Pesanan tidak ditemukan atau status tidak berubah.'];
        }

        return ['status' => $status, 'kode' => $code];
    }
}