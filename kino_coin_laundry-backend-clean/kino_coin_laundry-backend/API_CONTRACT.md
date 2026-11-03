# Kontrak API Integrasi Frontend <-> Backend (Kino Coin Laundry)

Dokumentasi resmi arsitektur komunikasi data antara **Frontend (Nizar Maulana Ramzy)** dan **Backend (Sarah Geiza)**.

- **Router Utama Backend**: `api/index.php?module={module}&action={action}`
- **Format Header**: `Content-Type: application/json`
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

### A. Login Staf (`action=login`) - Method: `POST`
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

### B. Cek Sesi (`action=me`) - Method: `GET`
Mengembalikan data pengguna yang sedang login beserta CSRF token aktif.

---

## 2. Modul Pelanggan (`?module=pelanggan`)

### A. Cari Pelanggan (`action=list`) - Method: `GET`
* **Query Params**: `q` (nama atau no_hp)
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
* Mengambil master jadwal shift operasional toko (Pagi, Siang, Malam).

---

## 5. Modul Transaksi (`?module=transaksi`)

### A. Buat Transaksi Baru (`action=create`) - Method: `POST`
* **Batasan Kapasitas Mesin**: Maksimal **7.5 kg** per detail beban mesin.
* **Request Body**:
  ```json
  {
    "no_hp": "081377889900",
    "nama_pelanggan": "Zaki Ramadhan",
    "id_shift": 2,
    "jenis_pembayaran": "cash",
    "detail_transaksi": [
      {
        "id_layanan": 1,
        "jumlah_koin": 1,
        "berat": 6.5
      }
    ]
  }
  ```
* **Response (201)**:
  ```json
  {
    "id_transaksi": 105,
    "total_bayar": 7000
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
