# Kontrak API Integrasi Frontend <-> Backend (Kino Coin Laundry)

Dokumentasi resmi arsitektur komunikasi data antara **Frontend (Nizar Maulana Ramzy)** dan **Backend (Sarah Geiza)**.

- **Router Utama Backend**: `api/index.php?module={module}&action={action}`
- **Format Header**: `Content-Type: application/json`
- **Request POST yang dilindungi CSRF**: kirim token sesi pada header `X-CSRF-Token`.
- **Format Sukses**:
  ```json
  {
    "data": { ... }
  }
  ```
- **Format Gagal**:
  ```json
  {
    "error": "Deskripsi pesan error / validasi"
  }
  ```

---

## 1. Modul Autentikasi (`?module=auth`)

### A. Login Pelanggan (`action=login`) - Method: `POST`
* Kirim nomor WhatsApp pada `username` dan password yang dibuat saat pendaftaran.
  Akun pelanggan yang belum memiliki password dapat diaktifkan melalui
  `action=register` dengan nama dan nomor yang sama seperti catatan transaksi.

### B. Daftar / Aktifkan Akun Pelanggan (`action=register`) - Method: `POST`
* **Request Body**:
  ```json
  {
    "nama_pelanggan": "Nama sesuai transaksi",
    "no_hp": "081377889900",
    "password": "password-baru"
  }
  ```
* Password minimal 8 karakter. Untuk record pelanggan dari transaksi lama yang
  belum memiliki password, nama dan nomor harus cocok dengan data database;
  akun lama akan diaktifkan tanpa membuat duplikat pelanggan.

### C. Login Staf (`action=login`) - Method: `POST`
* **Request Body**:
  ```json
  {
    "username": "daniel",
    "password": "password123"
  }
  ```
* **Response Sukses (200)**:
  ```json
  {
    "message": "Login berhasil",
    "user": {
      "id_pengguna": 2,
      "nama": "Daniel Afifi",
      "role": "kasir"
    },
    "csrf_token": "a1b2c3d4..."
  }
  ```

### D. Cek Sesi (`action=me`) - Method: `GET`
Mengembalikan data pengguna yang sedang login beserta CSRF token aktif.

---

## 2. Modul Pelanggan (`?module=pelanggan`)

### A. Cari Pelanggan (`action=list`) - Method: `GET`
* **Query Params**: `q` (nama, nomor WhatsApp, atau kode kartu QR)
* **Response (200)**: Array data pelanggan.

### B. Tambah Pelanggan Baru (`action=create`) - Method: `POST`
* **Request Body**:
  ```json
  {
    "nama_pelanggan": "Zaki Ramadhan",
    "no_hp": "081377889900"
  }
  ```

---

## 3. Modul Layanan (`?module=layanan`)

### Daftar Layanan (`action=list`) - Method: `GET`
* Mengambil seluruh katalog tarif (Cuci Koin Rp 7.000, Kering Koin Rp 10.000, Setrika, dll).

---

## 4. Modul Shift (`?module=shift`)

### Daftar Shift Kerja (`action=list`) - Method: `GET`
* Mengambil jadwal empat shift: Pagi (06:00–12:00), Siang (12:00–18:00),
  Malam (18:00–00:00), dan Midnight (00:00–06:00).

### Operasional Shift (`?module=shift-operasional`)
* `action=current` (`GET`) mengembalikan shift kasir yang aktif.
* `action=open` (`POST`) membuka shift yang sedang berjalan; body berisi `id_shift`.
  Jika ada jadwal pada hari tersebut, kasir harus memiliki jadwal sendiri yang
  sedang berlangsung. Fallback ke shift yang sedang berjalan hanya berlaku jika
  belum ada jadwal yang dibuat untuk hari itu.
* `action=close` (`POST`) menutup shift aktif; body berisi `total_fisik` dan dapat
  menyertakan `catatan`. Penutupan sebelum waktu selesai diperbolehkan, tetapi
  wajib menyertakan alasan. Backend menyimpan kas cash menurut sistem, kas fisik,
  dan selisihnya; QRIS tidak dihitung sebagai uang fisik.
* Ketiga action memerlukan sesi kasir; `open` dan `close` memerlukan token CSRF.

---

## 5. Modul Transaksi (`?module=transaksi`)

### A. Buat Transaksi Baru (`action=create`) - Method: `POST`
* **Batasan Kapasitas Mesin**: Maksimal **7.5 kg** per detail beban mesin.
* **Request Body**:
  ```json
  {
    "no_hp": "081377889900",
    "nama_pelanggan": "Zaki Ramadhan",
    "jenis_pembayaran": "cash",
    "jumlah_dibayar": 7000,
    "detail_transaksi": [
      {
        "id_layanan": 1,
        "berat": 6.5
      }
    ]
  }
  ```
* `jumlah_dibayar` wajib dan harus minimal sama dengan total tagihan; pembayaran
  kurang ditolak (HTTP 422), sehingga transaksi baru selalu lunas. Cash boleh
  lebih dan backend menghitung kembalian. QRIS harus sama persis dengan total.
* Cuci, Kering, Cuci Kering, Extra Spin, Extra Dry, dan Cuci Kering Drop Off
  dihitung per proses 7,5 kg dengan pembulatan ke atas. Drop Off tidak
  menghasilkan koin mesin. Lipat memakai `berat` dan dihitung per kg; Sabun,
  Softener, dan Kresek memakai `jumlah` pcs. Parfum gratis sudah termasuk dan
  bukan item berbayar terpisah. Harga selalu diambil dari tabel `layanan`.
  Kolom jumlah detail tersedia setelah migration
  `database/05_jumlah_layanan_detail.sql` dijalankan.
* `id_shift` pada request lama tidak lagi digunakan; transaksi mengikuti shift
  operasional aktif yang tersimpan untuk kasir.
* **Response (201)**:
  ```json
  {
    "message": "Transaksi berhasil disimpan",
    "data": {
      "id_transaksi": 105,
      "no_invoice": "INV-20261104-AB12CD34",
      "total_bayar": 7000,
      "jenis_pembayaran": "cash",
      "jumlah_dibayar": 7000,
      "sisa_bayar": 0,
      "uang_kembalian": 0,
      "status_pembayaran": "lunas",
      "total_koin_mesin": 1
    }
  }
  ```

### B. Riwayat Transaksi (`action=list`) - Method: `GET`
* **Query Params**: `page` (default 1).

---

## 6. Modul Dashboard & Rekapitulasi (`?module=dashboard` / `?module=rekap`)

### Ringkasan Omset & Metrik (`action=index`) - Method: `GET`
* **Query Params**: `start` (YYYY-MM-DD), `end` (YYYY-MM-DD), `kasir`, `shift`.
* **Response (200)**:
  ```json
  {
    "data": {
      "jumlah_transaksi": "25",
      "pendapatan": "520000",
      "total_berat": "112.5",
      "total_koin": "55"
    }
  }
  ```

---

## 7. Modul Tracking Pelanggan (`?module=riwayat`)

### Lacak Cucian Pelanggan (`action=tracking`) - Method: `POST`
* **Request Body**:
  ```json
  {
    "no_hp": "08123456789"
  }
  ```
* **Response**: Data riwayat cucian pelanggan berdasarkan nomor WhatsApp.
