<?php
// Output buffering to prevent headers already sent
if (ob_get_level() === 0) ob_start();
// Kino Coin Laundry - State & Data Store
// Aligned 100% with Figma Page 2 Design Overhaul

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Reset or init database mock in session
if (!isset($_SESSION['kino_initialized'])) {
    // 1. Master Layanan (Self Service Token, Drop Off Tunai, Additional Tunai)
    $_SESSION['services'] = [
        // Self Service (Token)
        [
            'id' => 1,
            'kategori' => 'SELF_SERVICE',
            'kategori_label' => 'Self Service (Token)',
            'nama' => 'Cuci',
            'deskripsi' => '1 Coin Cuci (Rp 7.000)',
            'tarif' => 7000,
            'koin_cuci_req' => 1,
            'koin_kering_req' => 0,
            'satuan' => 'putaran',
            'durasi' => '45 menit',
            'status' => 'Aktif',
            'icon' => ''
        ],
        [
            'id' => 2,
            'kategori' => 'SELF_SERVICE',
            'kategori_label' => 'Self Service (Token)',
            'nama' => 'Kering',
            'deskripsi' => '1 Coin Kering (Rp 10.000)',
            'tarif' => 10000,
            'koin_cuci_req' => 0,
            'koin_kering_req' => 1,
            'satuan' => 'putaran',
            'durasi' => '45 menit',
            'status' => 'Aktif',
            'icon' => ''
        ],
        [
            'id' => 3,
            'kategori' => 'SELF_SERVICE',
            'kategori_label' => 'Self Service (Token)',
            'nama' => 'Cuci Kering',
            'deskripsi' => 'Cuci Kering — Rp 16.000 / 7kg (Hemat Rp 1.000)',
            'tarif' => 16000,
            'koin_cuci_req' => 1,
            'koin_kering_req' => 1,
            'satuan' => 'putaran',
            'durasi' => '90 menit',
            'status' => 'Aktif',
            'icon' => ''
        ],
        [
            'id' => 4,
            'kategori' => 'SELF_SERVICE',
            'kategori_label' => 'Self Service (Token)',
            'nama' => 'Extra Spin',
            'deskripsi' => 'Peras tambahan kecepatan tinggi',
            'tarif' => 4000,
            'koin_cuci_req' => 0,
            'koin_kering_req' => 0,
            'satuan' => 'putaran',
            'durasi' => '15 menit',
            'status' => 'Aktif',
            'icon' => ''
        ],
        [
            'id' => 5,
            'kategori' => 'SELF_SERVICE',
            'kategori_label' => 'Self Service (Token)',
            'nama' => 'Extra Dry',
            'deskripsi' => 'Kering ekstra / 20 menit',
            'tarif' => 5000,
            'koin_cuci_req' => 0,
            'koin_kering_req' => 1,
            'satuan' => '20 menit',
            'durasi' => '20 menit',
            'status' => 'Aktif',
            'icon' => ''
        ],
        // Drop Off (Tunai)
        [
            'id' => 6,
            'kategori' => 'DROP_OFF',
            'kategori_label' => 'Drop Off (Tunai)',
            'nama' => 'Cuci Kering (Drop Off)',
            'deskripsi' => 'Cuci Kering — Rp 20.000 / 7kg',
            'tarif' => 20000,
            'koin_cuci_req' => 0,
            'koin_kering_req' => 0,
            'satuan' => '7 kg',
            'durasi' => '1 hari',
            'status' => 'Aktif',
            'icon' => ''
        ],
        [
            'id' => 7,
            'kategori' => 'DROP_OFF',
            'kategori_label' => 'Drop Off (Tunai)',
            'nama' => 'Lipat',
            'deskripsi' => 'Jasa lipat rapi — Rp 1.500 / kg',
            'tarif' => 1500,
            'koin_cuci_req' => 0,
            'koin_kering_req' => 0,
            'satuan' => 'kg',
            'durasi' => '1 hari',
            'status' => 'Aktif',
            'icon' => ''
        ],
        // Additional (Tunai)
        [
            'id' => 8,
            'kategori' => 'ADDITIONAL',
            'kategori_label' => 'Additional (Tunai)',
            'nama' => 'Sabun Deterjen',
            'deskripsi' => 'Deterjen cair konsentrat — Rp 1.500 / Pcs',
            'tarif' => 1500,
            'koin_cuci_req' => 0,
            'koin_kering_req' => 0,
            'satuan' => 'pcs',
            'durasi' => 'Langsung',
            'status' => 'Aktif',
            'icon' => ''
        ],
        [
            'id' => 9,
            'kategori' => 'ADDITIONAL',
            'kategori_label' => 'Additional (Tunai)',
            'nama' => 'Softener Pewangi',
            'deskripsi' => 'Pewangi & pelembut — Rp 1.500 / Pcs',
            'tarif' => 1500,
            'koin_cuci_req' => 0,
            'koin_kering_req' => 0,
            'satuan' => 'pcs',
            'durasi' => 'Langsung',
            'status' => 'Aktif',
            'icon' => ''
        ],
        [
            'id' => 10,
            'kategori' => 'ADDITIONAL',
            'kategori_label' => 'Additional (Tunai)',
            'nama' => 'Kresek Laundry',
            'deskripsi' => 'Kantong kresek tebal — Rp 2.000 / Pcs',
            'tarif' => 2000,
            'koin_cuci_req' => 0,
            'koin_kering_req' => 0,
            'satuan' => 'pcs',
            'durasi' => 'Langsung',
            'status' => 'Aktif',
            'icon' => ''
        ]
    ];

    // 2. Data Pelanggan & Member
    $_SESSION['members'] = [
        [
            'id' => 1,
            'nama' => 'Budi Santoso',
            'wa' => '08123456789',
            'kode_kartu' => 'KC-P-0001',
            'is_member' => true,
            'tx_count' => 4,
            'coin_cuci' => 3,
            'coin_kering' => 1,
            'password' => 'password123'
        ],
        [
            'id' => 2,
            'nama' => 'Sari Dewi',
            'wa' => '08129876543',
            'kode_kartu' => 'KC-P-0002',
            'is_member' => true,
            'tx_count' => 2,
            'coin_cuci' => 2,
            'coin_kering' => 1,
            'password' => 'password123'
        ],
        [
            'id' => 3,
            'nama' => 'Andi Pratama',
            'wa' => '08131112222',
            'kode_kartu' => 'KC-P-0003',
            'is_member' => true,
            'tx_count' => 3,
            'coin_cuci' => 1,
            'coin_kering' => 2,
            'password' => 'password123'
        ],
        [
            'id' => 4,
            'nama' => 'Rina Marlina',
            'wa' => '08155556666',
            'kode_kartu' => 'KC-P-0004',
            'is_member' => false, // Non-member
            'tx_count' => 1,
            'coin_cuci' => 0,
            'coin_kering' => 0,
            'password' => 'password123'
        ],
        [
            'id' => 5,
            'nama' => 'Dewi Lestari',
            'wa' => '08177778888',
            'kode_kartu' => 'KC-P-0005',
            'is_member' => true,
            'tx_count' => 0,
            'coin_cuci' => 0,
            'coin_kering' => 0,
            'password' => 'password123'
        ]
    ];

    // 3. Shift Kerja
    $_SESSION['shifts'] = [
        [
            'id' => 1,
            'nama' => 'Shift Pagi',
            'mulai' => '08:00',
            'selesai' => '14:00',
            'jam_tampil' => '08:00 — 14:00',
            'staff' => 'Sarah, Daniel'
        ],
        [
            'id' => 2,
            'nama' => 'Shift Siang',
            'mulai' => '14:00',
            'selesai' => '20:00',
            'jam_tampil' => '14:00 — 20:00',
            'staff' => 'Daniel, Nizar'
        ],
        [
            'id' => 3,
            'nama' => 'Shift Malam',
            'mulai' => '20:00',
            'selesai' => '02:00',
            'jam_tampil' => '20:00 — 02:00',
            'staff' => 'Nizar, Adittya'
        ],
        [
            'id' => 4,
            'nama' => 'Shift Midnight',
            'mulai' => '00:00',
            'selesai' => '08:00',
            'jam_tampil' => '00:00 — 08:00',
            'staff' => 'Adittya'
        ]
    ];

    // 4. Staf & Manajemen Pengguna
    $_SESSION['users'] = [
        [
            'id' => 1,
            'nama' => 'Andrey Shevchenko',
            'role' => 'Pemilik',
            'email' => 'andrey@kino.com',
            'username' => 'pemilik',
            'password' => 'password123'
        ],
        [
            'id' => 2,
            'nama' => 'Daniel Afifi',
            'role' => 'Kasir',
            'email' => 'daniel@kino.com',
            'username' => 'daniel',
            'password' => '123456'
        ],
        [
            'id' => 3,
            'nama' => 'Sarah Geiza',
            'role' => 'Kasir',
            'email' => 'sarah@kino.com',
            'username' => 'sarah',
            'password' => '123456'
        ],
        [
            'id' => 4,
            'nama' => 'Nizar Maulana',
            'role' => 'Kasir',
            'email' => 'nizar@kino.com',
            'username' => 'nizar',
            'password' => '123456'
        ],
        [
            'id' => 5,
            'nama' => 'Adittya',
            'role' => 'Kasir',
            'email' => 'adittya@kino.com',
            'username' => 'adittya',
            'password' => '123456'
        ]
    ];

    // 5. Presensi Kasir
    $_SESSION['presensi'] = [
        [
            'id' => 1,
            'kasir' => 'Daniel',
            'shift' => 'Shift Siang',
            'tanggal' => '31 Okt 2026',
            'filter_date' => date('Y-m-d'),
            'jam_masuk' => '13:55',
            'jam_keluar' => null,
            'status' => 'Berjalan'
        ],
        [
            'id' => 2,
            'kasir' => 'Sarah',
            'shift' => 'Shift Pagi',
            'tanggal' => '31 Okt 2026',
            'filter_date' => date('Y-m-d'),
            'jam_masuk' => '07:58',
            'jam_keluar' => '14:05',
            'status' => 'Selesai'
        ],
        [
            'id' => 3,
            'kasir' => 'Nizar',
            'shift' => 'Shift Malam',
            'tanggal' => '30 Okt 2026',
            'filter_date' => date('Y-m-d', strtotime('-1 day')),
            'jam_masuk' => '19:58',
            'jam_keluar' => '02:03',
            'status' => 'Selesai'
        ]
    ];

    // 6. Transaksi
    $_SESSION['transactions'] = [
        [
            'id' => 'INV-2026-001',
            'pelanggan' => 'Budi Santoso',
            'wa' => '08123456789',
            'kode_kartu' => 'KC-P-0001',
            'layanan' => 'Cuci Kering (Self)',
            'layanan_tipe' => 'Self Service',
            'kategori' => 'SELF_SERVICE',
            'kuantitas' => '1 putaran',
            'qty_num' => 1,
            'total_biaya' => 16000,
            'metode' => 'Token',
            'koin_cuci' => 1,
            'koin_kering' => 1,
            'total_bayar' => 17000,
            'status' => 'Lunas',
            'kasir' => 'Daniel',
            'shift' => 'Shift Siang',
            'waktu' => '14:30 WIB',
            'tanggal' => '31 Okt 2026',
            'filter_date' => date('Y-m-d')
        ],
        [
            'id' => 'INV-2026-002',
            'pelanggan' => 'Sari Dewi',
            'wa' => '08129876543',
            'kode_kartu' => 'KC-P-0002',
            'layanan' => 'Cuci + Kering (Self)',
            'layanan_tipe' => 'Self Service',
            'kategori' => 'SELF_SERVICE',
            'kuantitas' => '2 putaran',
            'qty_num' => 2,
            'total_biaya' => 17000,
            'metode' => 'Token',
            'koin_cuci' => 1,
            'koin_kering' => 1,
            'total_bayar' => 17000,
            'status' => 'Lunas',
            'kasir' => 'Daniel',
            'shift' => 'Shift Siang',
            'waktu' => '14:52 WIB',
            'tanggal' => '31 Okt 2026',
            'filter_date' => date('Y-m-d')
        ],
        [
            'id' => 'INV-2026-003',
            'pelanggan' => 'Andi Pratama',
            'wa' => '08131112222',
            'kode_kartu' => 'KC-P-0003',
            'layanan' => 'Cuci Kering (Drop Off)',
            'layanan_tipe' => 'Drop Off',
            'kategori' => 'DROP_OFF',
            'kuantitas' => '7 kg',
            'qty_num' => 1,
            'total_biaya' => 20000,
            'metode' => 'Tunai',
            'koin_cuci' => 0,
            'koin_kering' => 0,
            'total_bayar' => 20000,
            'status' => 'Lunas',
            'kasir' => 'Daniel',
            'shift' => 'Shift Siang',
            'waktu' => '15:10 WIB',
            'tanggal' => '31 Okt 2026',
            'filter_date' => date('Y-m-d')
        ],
        [
            'id' => 'INV-2026-004',
            'pelanggan' => 'Rina Marlina',
            'wa' => '08155556666',
            'kode_kartu' => 'KC-P-0004',
            'layanan' => 'Sabun + Softener',
            'layanan_tipe' => 'Drop Off',
            'kategori' => 'ADDITIONAL',
            'kuantitas' => '2 pcs',
            'qty_num' => 2,
            'total_biaya' => 3000,
            'metode' => 'Tunai',
            'koin_cuci' => 0,
            'koin_kering' => 0,
            'total_bayar' => 3000,
            'status' => 'Lunas',
            'kasir' => 'Daniel',
            'shift' => 'Shift Siang',
            'waktu' => '15:35 WIB',
            'tanggal' => '31 Okt 2026',
            'filter_date' => date('Y-m-d')
        ]
    ];

    // 7. Rekapitulasi Shift (untuk Pemilik & Kasir)
    $_SESSION['shifts_recap'] = [
        [
            'id' => 1,
            'kasir' => 'Sarah',
            'shift' => 'Pagi',
            'tanggal' => '31 Okt 2026',
            'trx_count' => 22,
            'total_kg' => 78.0,
            'pendapatan' => 512000,
            'koin_cuci' => 28,
            'koin_kering' => 37,
            'status' => 'Valid',
            'selisih' => 0
        ],
        [
            'id' => 2,
            'kasir' => 'Daniel',
            'shift' => 'Siang',
            'tanggal' => '31 Okt 2026',
            'trx_count' => 18,
            'total_kg' => 62.5,
            'pendapatan' => 425000,
            'koin_cuci' => 24,
            'koin_kering' => 26,
            'status' => 'Valid',
            'selisih' => 0
        ],
        [
            'id' => 3,
            'kasir' => 'Nizar',
            'shift' => 'Malam',
            'tanggal' => '30 Okt 2026',
            'trx_count' => 15,
            'total_kg' => 55.0,
            'pendapatan' => 380000,
            'koin_cuci' => 20,
            'koin_kering' => 25,
            'status' => 'Review',
            'selisih' => -5000
        ]
    ];

    // Status presensi aktif sesi kasir saat ini
    $_SESSION['kasir_checked_in'] = true;
    $_SESSION['kino_initialized'] = true;
}

// Helpers
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper untuk mendeteksi shift aktif kasir secara dinamis dan real-time
function get_active_kasir_shift($kasir_name = null) {
    if (!$kasir_name) {
        $kasir_name = $_SESSION['username'] ?? 'Daniel';
    }
    
    $all_shifts = $_SESSION['shifts'] ?? [];
    $shifts_reversed = array_reverse($all_shifts); // shift terbaru / custom diutamakan
    $candidate_by_name = null;
    $now_time = date('H:i');

    // 1. Cek shift yang cocok dengan nama kasir DAN jam saat ini
    foreach ($shifts_reversed as $s) {
        $staff_list = array_map('trim', explode(',', $s['staff'] ?? ''));
        foreach ($staff_list as $st) {
            if (strcasecmp($st, $kasir_name) === 0) {
                if (!$candidate_by_name) {
                    $candidate_by_name = $s;
                }
                $start = $s['mulai'];
                $end = $s['selesai'];
                if ($start < $end) {
                    if ($now_time >= $start && $now_time <= $end) {
                        return $s;
                    }
                } else {
                    if ($now_time >= $start || $now_time <= $end) {
                        return $s;
                    }
                }
            }
        }
    }

    // 2. Jika kasir ditugaskan di suatu shift, gunakan penugasan terbarunya
    if ($candidate_by_name) {
        return $candidate_by_name;
    }

    return $all_shifts[1] ?? ($all_shifts[0] ?? [
        'id' => 2,
        'nama' => 'Shift Siang',
        'mulai' => '14:00',
        'selesai' => '20:00',
        'jam_tampil' => '14:00 — 20:00',
        'staff' => 'Daniel, Nizar'
    ]);
}
