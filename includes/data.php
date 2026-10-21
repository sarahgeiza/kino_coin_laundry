<?php
// Session-based data store that mimics PostgreSQL tables
// Easily replaced with PDO / pg_query in backend phase
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initial seed data if not present in session
if (!isset($_SESSION['initialized'])) {
    $_SESSION['services'] = [
        ['id' => 1, 'nama' => 'Cuci Kering', 'tarif' => 6000, 'durasi' => '2 hari', 'status' => 'Aktif', 'icon' => 'baskom'],
        ['id' => 2, 'nama' => 'Cuci Setrika', 'tarif' => 8000, 'durasi' => '2 hari', 'status' => 'Aktif', 'icon' => 'baju'],
        ['id' => 3, 'nama' => 'Setrika Saja', 'tarif' => 5000, 'durasi' => '1 hari', 'status' => 'Aktif', 'icon' => 'setrika'],
        ['id' => 4, 'nama' => 'Bed Cover', 'tarif' => 15000, 'durasi' => '3 hari', 'status' => 'Aktif', 'icon' => 'kasur']
    ];

    $_SESSION['transactions'] = [
        [
            'id' => 'INV-2026-001',
            'pelanggan' => 'Budi Santoso',
            'telepon' => '08123456789',
            'layanan_id' => 1,
            'layanan' => 'Cuci Kering',
            'berat' => 3.5,
            'total_biaya' => 21000,
            'koin_500' => 7,
            'koin_1000' => 0,
            'total_koin_nilai' => 3500,
            'sisa_bayar' => 17500,
            'status' => 'Lunas',
            'waktu' => '14:30 WIB',
            'tanggal' => '12 Mei 2026',
            'kasir' => 'Daniel',
            'shift' => 'Siang'
        ],
        [
            'id' => 'INV-2026-002',
            'pelanggan' => 'Sari Dewi',
            'telepon' => '08129876543',
            'layanan_id' => 2,
            'layanan' => 'Cuci Setrika',
            'berat' => 5.0,
            'total_biaya' => 40000,
            'koin_500' => 0,
            'koin_1000' => 5,
            'total_koin_nilai' => 5000,
            'sisa_bayar' => 35000,
            'status' => 'Lunas',
            'waktu' => '14:52 WIB',
            'tanggal' => '12 Mei 2026',
            'kasir' => 'Daniel',
            'shift' => 'Siang'
        ],
        [
            'id' => 'INV-2026-003',
            'pelanggan' => 'Andi Pratama',
            'telepon' => '08571122334',
            'layanan_id' => 3,
            'layanan' => 'Setrika Saja',
            'berat' => 2.0,
            'total_biaya' => 12000,
            'koin_500' => 4,
            'koin_1000' => 1,
            'total_koin_nilai' => 3000,
            'sisa_bayar' => 9000,
            'status' => 'Lunas',
            'waktu' => '15:10 WIB',
            'tanggal' => '12 Mei 2026',
            'kasir' => 'Daniel',
            'shift' => 'Siang'
        ],
        [
            'id' => 'INV-2026-004',
            'pelanggan' => 'Rina Marlina',
            'telepon' => '08987654321',
            'layanan_id' => 4,
            'layanan' => 'Bed Cover',
            'berat' => 4.0,
            'total_biaya' => 60000,
            'koin_500' => 0,
            'koin_1000' => 6,
            'total_koin_nilai' => 6000,
            'sisa_bayar' => 54000,
            'status' => 'Lunas',
            'waktu' => '15:35 WIB',
            'tanggal' => '12 Mei 2026',
            'kasir' => 'Daniel',
            'shift' => 'Siang'
        ]
    ];

    $_SESSION['shifts_recap'] = [
        [
            'id' => 1,
            'tanggal' => '12 Mei 2026',
            'shift' => 'Pagi',
            'kasir' => 'Sarah',
            'trx_count' => 22,
            'total_kg' => 78.0,
            'pendapatan' => 512000,
            'koin_500' => 280,
            'koin_1000' => 372,
            'selisih' => 0,
            'status' => 'Valid'
        ],
        [
            'id' => 2,
            'tanggal' => '12 Mei 2026',
            'shift' => 'Siang',
            'kasir' => 'Daniel',
            'trx_count' => 18,
            'total_kg' => 62.5,
            'pendapatan' => 425000,
            'koin_500' => 320,
            'koin_1000' => 265,
            'selisih' => 0,
            'status' => 'Valid'
        ],
        [
            'id' => 3,
            'tanggal' => '11 Mei 2026',
            'shift' => 'Malam',
            'kasir' => 'Nizar',
            'trx_count' => 15,
            'total_kg' => 55.0,
            'pendapatan' => 380000,
            'koin_500' => 250,
            'koin_1000' => 255,
            'selisih' => -5000,
            'status' => 'Review'
        ]
    ];

    $_SESSION['antrian'] = [
        ['nomor' => '08', 'pelanggan' => 'Budi Santoso', 'estimasi' => '~45 menit', 'status' => 'Proses', 'status_color' => 'orange', 'layanan' => 'Cuci Kering • 3,5 kg'],
        ['nomor' => '09', 'pelanggan' => 'Siti Nurhaliza', 'estimasi' => '~1 jam', 'status' => 'Antri', 'status_color' => 'yellow', 'layanan' => 'Cuci Setrika • 5,0 kg'],
        ['nomor' => '10', 'pelanggan' => 'Ahmad R.', 'estimasi' => '~1,5 jam', 'status' => 'Siap Diambil', 'status_color' => 'green', 'layanan' => 'Setrika Saja • 2,0 kg']
    ];

    $_SESSION['initialized'] = true;
}

// Helpers
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
