# Kino Coin Laundry - Sistem Informasi Manajemen Operasional (PBL TI Polinema)

Aplikasi manajemen operasional laundry koin mandiri 24 jam berbasis web PHP Native dengan arsitektur modular, responsive mobile/desktop, dan siap integrasi REST API backend.

## Struktur Direktori

```text
C:\PBL\
├── assets/                    <- Aset statis terorganisir
│   ├── css/
│   │   └── style.css          (Desain UI/UX presisi Figma Page 2 + responsive desktop)
│   └── js/
│       ├── api.js             (Modul client API integrasi backend)
│       ├── app.js             (Interaksi UI, kalkulator token, modal, scanner)
│       └── vendor/            (Library pihak ketiga: Alpine, Html5-Qrcode, QRCode)
│           ├── alpine.min.js
│           ├── html5-qrcode.min.js
│           └── qrcode.min.js
├── backend/                   <- Area pengembangan backend PostgreSQL (Sarah Geiza)
│   ├── config/
│   │   └── database.php
│   ├── models/                (Model tabel database)
│   └── test_connection.php   (Skrip uji koneksi basis data)
├── includes/                  <- Template modular reusable
│   ├── data.php               (Data store session & helper shift real-time)
│   ├── header.php             (Header & status navigasi)
│   ├── footer.php             (Footer & bottom sheet modal)
│   └── icons.php              (Pustaka vektor SVG outline monoline)
├── admin/                     <- Modul Pemilik / Owner
│   ├── index.php              (Dashboard monitoring omset harian & audit shift P1)
│   ├── laporan.php            (Laporan transaksi, grafik omset 7 hari & export CSV P2)
│   ├── master.php             (Katalog Master Layanan & Jadwal Shift P3, P4)
│   ├── presensi.php           (Log presensi kehadiran kasir real-time P6)
│   └── users.php              (Manajemen akun staf kasir & pemilik P5)
├── kasir/                     <- Modul Kasir / POS Operasional
│   ├── index.php              (Dashboard kasir, modal check-in presensi K1, M1)
│   ├── pelanggan.php          (Daftar member, pencarian, & kartu QR digital K5, M7)
│   ├── rekap.php              (Rekapitulasi operasional shift & tutup shift K6, M4)
│   ├── transaksi.php          (Daftar transaksi shift berjalan & filter K4)
│   └── transaksi-baru.php     (Form transaksi baru, live token calculator K2)
├── pelanggan/                 <- Modul Pelanggan / Konsumen
│   ├── daftar.php             (Pendaftaran akun member mandiri A5)
│   ├── index.php              (Beranda pelanggan, tracking cucian & kartu QR C1, C2)
│   └── login.php              (Login pelanggan nomor WhatsApp & password A4)
├── .gitignore
├── API_CONTRACT.md            <- Panduan kontrak endpoint integrasi Frontend <-> Backend
├── index.php                  (Landing page publik A1)
├── login-kasir.php            (Portal login kasir A2)
├── login-pemilik.php          (Portal login pemilik A3)
├── logout.php                 (Terminasi sesi & redirect)
└── README.md
```

## Cara Menjalankan
Buka terminal pada folder proyek, lalu jalankan server lokal:
```bash
php -S localhost:8000
```
Akses melalui browser di: `http://localhost:8000`
