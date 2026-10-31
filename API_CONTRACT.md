# Kontrak API Integrasi Frontend <-> Backend (Kino Coin Laundry)

Dokumentasi ini disiapkan oleh **Frontend Developer (Nizar Maulana Ramzy)** untuk **Backend Developer (Sarah Geiza)** sebagai panduan endpoint REST API yang dipanggil oleh antarmuka web.

- **Lokasi Controller Backend**: `backend/controllers/*.php`
- **Format Header**: `Content-Type: application/json`
- **Format Standar Response**:
  ```json
  {
    "success": true,
    "message": "Operasi berhasil",
    "data": { ... }
  }
  ```
  Jika gagal:
  ```json
  {
    "success": false,
    "error": "Pesan error validasi / kredensial salah"
  }
  ```

---

## 1. Modul Autentikasi (`backend/controllers/auth.php`)

### A. Login (`?action=login`) - Method: `POST`
* **Request Body**:
  ```json
  {
    "role": "kasir",          // "kasir" | "pemilik" | "pelanggan"
    "username": "daniel",     // username staf / nomor WhatsApp pelanggan
    "password": "password123"
  }
  ```
* **Response Success**:
  ```json
  {
    "success": true,
    "data": {
      "id": 2,
      "nama": "Daniel Afifi",
      "role": "Kasir",
      "token": "bearer-token-or-session"
    }
  }
  ```

### B. Register Pelanggan Mandiri (`?action=register`) - Method: `POST`
* **Request Body**:
  ```json
  {
    "nama": "Adit",
    "wa": "08123456789",
    "password": "password123"
  }
  ```
* **Catatan Backend**: Otomatis generate string `kode_kartu` (format: `KC-P-XXXX`) dan simpan ke tabel `pelanggan`.

---

## 2. Modul Pelanggan & Kartu Member QR (`backend/controllers/pelanggan.php`)

### A. Cari Pelanggan / Autocomplete (`?action=search&q={query}`) - Method: `GET`
* **Response Success**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "nama": "Budi Santoso",
        "wa": "08123456789",
        "kode_kartu": "KC-P-0001",
        "is_member": true
      }
    ]
  }
  ```

### B. Get Data via Scan QR (`?action=get_by_code&kode={kode_kartu}`) - Method: `GET`
* Digunakan saat kamera kasir memindai QR code pelanggan (misal: `KC-P-0001`).
* **Response Success**: Return object detail pelanggan sesuai `kode_kartu`.

### C. Tambah Pelanggan Baru oleh Kasir (`?action=create`) - Method: `POST`
* **Request Body**:
  ```json
  {
    "nama": "Dewi Lestari",
    "wa": "08155556666",
    "is_member": true
  }
  ```

---

## 3. Modul Transaksi (`backend/controllers/transaksi.php`)

### A. Simpan Transaksi Baru (`?action=create`) - Method: `POST`
* **Request Body**:
  ```json
  {
    "nama_pelanggan": "Budi Santoso",
    "wa_pelanggan": "08123456789",
    "service_id": 3,
    "qty": 1,
    "metode_pembayaran": "TOKEN",   // "TOKEN" (Self Service) | "TUNAI" (Drop Off/Retail)
    "koin_cuci": 1,                 // jumlah Coin Cuci (7k)
    "koin_kering": 1,               // jumlah Coin Kering (10k)
    "total_biaya": 16000,
    "total_bayar": 17000,
    "id_kasir": 2,
    "id_shift": 2
  }
  ```

### B. List Transaksi (`?action=list&filter={filter}`) - Method: `GET`
* Filter: `Semua` | `Self Service` | `Drop Off` | `pelanggan_id={id}`.

---

## 4. Modul Presensi & Shift (`backend/controllers/shift.php` & `presensi.php`)

### A. Mulai Shift / Check-in (`presensi.php?action=checkin`) - Method: `POST`
* **Request Body**:
  ```json
  {
    "id_kasir": 2,
    "id_shift": 2
  }
  ```

### B. Tutup Shift Kasir (`shift.php?action=tutup`) - Method: `POST`
* **Request Body**: Rekap total pendapatan, koin fisik, dan selisih kas rekonsiliasi.

---

## 5. Modul Master Layanan (`backend/controllers/layanan.php`)
* `?action=list`: Mengembalikan daftar paket layanan dengan kolom `kategori` (`SELF_SERVICE`, `DROP_OFF`, `ADDITIONAL`).

---

## Cara Mengaktifkan di Frontend:
Buka file `js/api.js`, ubah baris:
```javascript
USE_BACKEND_API: false,
```
menjadi:
```javascript
USE_BACKEND_API: true,
```
