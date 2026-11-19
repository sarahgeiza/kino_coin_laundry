# Permintaan Endpoint API — dari Frontend

**Dari:** Ramzy (Frontend)
**Untuk:** Sarah (Backend)
**Tanggal:** 18 November 2026
**Branch acuan:** `check`

---

## 0. Konteks

Frontend sekarang **sudah memakai controller backend** untuk semua operasi tulis:

| Aksi frontend | Controller yang dipakai |
|---|---|
| Simpan transaksi | `TransaksiController` → `create` |
| Tambah pelanggan / member | `PelangganController` → `create` |
| Buka shift (presensi) | `ShiftOperasionalController` → `open` |
| Tutup shift | `ShiftOperasionalController` → `close` |
| Tambah / edit / hapus staf | `PenggunaController` → `create` / `update` / `delete` |

Yang **belum** bisa lewat API adalah operasi baca, karena endpoint-nya belum ada atau belum cukup. Dokumen ini berisi 4 permintaan endpoint + 1 laporan bug.

Kalau keempatnya tersedia, seluruh halaman bisa 100% lewat API tanpa query langsung ke database.

**Konvensi yang sudah dipakai** (mohon dipertahankan):
- Sukses: `{"data": ...}` — boleh array atau objek.
- Gagal: `{"error": "pesan"}` + status 4xx/5xx.
- Semua `POST` wajib header `X-CSRF-Token` (sudah dicek oleh `csrf()`).
- Role dicek lewat `actor()` / `owner()`.

---

## 1. `GET transaksi.list` — tambah filter tanggal, limit, dan ringkasan

**Prioritas: P1** — dipakai halaman **Laporan (Pemilik)** dan **Riwayat Transaksi (Kasir)**.

**Kondisi sekarang:** `limit` dikunci 20, tidak ada filter tanggal, tidak ada total agregat. Akibatnya grafik 7 hari, filter "Custom", dan export CSV masih query langsung ke database.

**Request**

```
GET /api/index.php?module=transaksi&action=list
    &start=2026-11-12
    &end=2026-11-18
    &page=1
    &limit=50
    &kasir=3          (opsional, hanya pemilik)
    &shift=2          (opsional)
    &summary=1        (opsional)
```

| Parameter | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| `start` | date `YYYY-MM-DD` | tidak | awal bulan berjalan | batas bawah `tanggal_transaksi` |
| `end` | date `YYYY-MM-DD` | tidak | hari ini | batas atas, inklusif |
| `page` | int | tidak | 1 | halaman |
| `limit` | int | tidak | 20 | maksimal 100 |
| `kasir` | int | tidak | – | filter `id_kasir`, hanya untuk role `pemilik` |
| `shift` | int | tidak | – | filter `id_shift` |
| `summary` | 0/1 | tidak | 0 | kalau 1, sertakan objek `summary` |

**Aturan akses:** role `kasir` tetap otomatis dibatasi ke shift aktif miliknya (perilaku sekarang dipertahankan). Role `pemilik` melihat semua.

**Contoh response 200**

```json
{
  "data": [
    {
      "id_transaksi": 11,
      "no_invoice": "INV-20261118-B5490E82",
      "tanggal_transaksi": "2026-11-18 16:37:42+07",
      "nama_pelanggan": "rafa",
      "no_hp": "08123456789",
      "nama_kasir": "Sarah",
      "nama_shift": "Malam",
      "total_bayar": 15000,
      "jenis_pembayaran": "cash",
      "status_pembayaran": "lunas",
      "status_pengerjaan": "antri",
      "total_berat": 7.5,
      "total_koin": 2,
      "layanan": "Cuci Kering"
    }
  ],
  "page": 1,
  "limit": 50,
  "total": 8,
  "summary": {
    "total_transaksi": 8,
    "total_omzet": 132000,
    "total_berat": 47.5,
    "total_koin": 14
  }
}
```

**Error yang diharapkan**

| Status | Kondisi | Body |
|---|---|---|
| 422 | format tanggal salah | `{"error":"Tanggal harus YYYY-MM-DD"}` |
| 422 | `start > end` | `{"error":"Tanggal awal melebihi tanggal akhir"}` |
| 403 | kasir minta data kasir lain | `{"error":"Akses ditolak"}` |

---

## 2. `GET pelanggan.list` — tambah agregat transaksi & koin

**Prioritas: P2** — dipakai halaman **Pelanggan (Kasir)** dan **kartu member**.

**Kondisi sekarang:** `LIMIT 100` tanpa paginasi, dan tidak mengembalikan `tx_count` / `total_koin`. Kartu member menampilkan dua angka itu, jadi frontend masih query sendiri.

**Request**

```
GET /api/index.php?module=pelanggan&action=list
    &q=rafi
    &page=1
    &limit=50
```

| Parameter | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| `q` | string | tidak | – | cari nama / no_hp / kode_kartu |
| `page` | int | tidak | 1 | halaman |
| `limit` | int | tidak | 20 | maksimal 100 |

**Contoh response 200**

```json
{
  "data": [
    {
      "id_pelanggan": 3,
      "nama_pelanggan": "Rafi Firdaus",
      "no_hp": "0801234056",
      "kode_kartu": "KC-P-0003",
      "is_member": true,
      "tx_count": 4,
      "total_koin": 9,
      "terakhir_transaksi": "2026-11-17 15:10:00+07"
    }
  ],
  "page": 1,
  "limit": 50,
  "total": 5
}
```

**Catatan:** `tx_count` = jumlah baris `transaksi` milik pelanggan; `total_koin` = `SUM(detail_transaksi.jumlah_koin)`.

---

## 3. `jadwal-shift` — endpoint roster (paling penting)

**Prioritas: P1** — dipakai halaman **Master → Shift (Pemilik)**.

**Kondisi sekarang:** belum ada endpoint sama sekali. Frontend menulis langsung ke tabel `jadwal_shift`, padahal tabel ini yang menentukan siapa boleh presensi. Ini satu-satunya operasi tulis yang masih bypass controller.

### 3a. `GET jadwal-shift.list`

```
GET /api/index.php?module=jadwal-shift&action=list&tanggal=2026-11-18
```

| Parameter | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| `tanggal` | date | tidak | hari ini | tanggal roster |

```json
{
  "data": [
    {
      "id_jadwal": 28,
      "tanggal": "2026-11-18",
      "id_shift": 3,
      "nama_shift": "Malam",
      "jam_mulai": "18:00",
      "jam_selesai": "00:00",
      "id_kasir": 3,
      "nama_kasir": "Sarah",
      "status": "dijadwalkan"
    }
  ],
  "configured": true
}
```

`configured` = `false` kalau tanggal itu belum punya baris roster sama sekali (frontend memakai ini untuk membedakan "belum diatur" vs "sengaja dikosongkan").

### 3b. `POST jadwal-shift.save` — simpan roster satu shift satu tanggal

```
POST /api/index.php?module=jadwal-shift&action=save
X-CSRF-Token: <token>
Content-Type: application/json
```

```json
{
  "tanggal": "2026-11-18",
  "id_shift": 3,
  "kasir_ids": [3, 4]
}
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `tanggal` | date | ya | tanggal roster |
| `id_shift` | int | ya | shift yang diatur |
| `kasir_ids` | int[] | ya | boleh kosong `[]` = tidak ada kasir di shift ini |

**Perilaku yang diminta:**
1. Kasir yang **tidak** ada di `kasir_ids` → `status = 'dibatalkan'` (jangan `DELETE`), supaya sistem tetap tahu tanggal itu sudah pernah diatur.
2. Kasir yang ada di `kasir_ids` → `status = 'dijadwalkan'`, `dibuat_oleh = actor id`.
3. `kasir_ids: []` berarti shift sengaja dikosongkan → semua kasir ditolak presensi di shift itu.
4. Kalau `id_shift` duplikat (tabel `shift` punya baris kembar), pakai **id terkecil** untuk nama shift yang sama.

```json
{
  "message": "Roster shift disimpan",
  "data": {
    "tanggal": "2026-11-18",
    "id_shift": 3,
    "kasir": [
      {"id_kasir": 3, "nama_kasir": "Sarah", "status": "dijadwalkan"},
      {"id_kasir": 4, "nama_kasir": "Ramzy", "status": "dibatalkan"}
    ]
  }
}
```

### 3c. `POST jadwal-shift.cancel`

```json
{ "id_jadwal": 28 }
```

Response: `{"message":"Jadwal dibatalkan"}`.

**Error**

| Status | Kondisi | Body |
|---|---|---|
| 403 | bukan pemilik | `{"error":"Akses hanya untuk pemilik"}` |
| 422 | `id_shift` tidak ada | `{"error":"Shift tidak ditemukan"}` |
| 422 | kasir tidak valid | `{"error":"Kasir tidak ditemukan"}` |
| 409 | kasir sudah di shift lain di tanggal sama | `{"error":"Kasir sudah dijadwalkan di shift lain pada tanggal ini"}` |

---

## 4. `GET rekap.shift` — rekap per shift operasional

**Prioritas: P3** — dipakai **Rekap Shift (Kasir)** dan **Dashboard (Pemilik)**.

**Kondisi sekarang:** data diambil dari VIEW `v_rekap_shift_operasional`, tidak ada endpoint-nya.

```
GET /api/index.php?module=rekap&action=shift&start=2026-11-01&end=2026-11-18&limit=50
```

```json
{
  "data": [
    {
      "id_shift_operasional": 29,
      "tanggal": "2026-11-18",
      "nama_kasir": "Sarah",
      "nama_shift": "Siang",
      "mulai_pada": "2026-11-18 16:35:15+07",
      "ditutup_pada": "2026-11-18 17:02:23+07",
      "status": "ditutup",
      "jumlah_transaksi": 1,
      "total_berat": 7.5,
      "total_koin": 2,
      "total_omzet": 15000,
      "total_sistem": 15000,
      "total_fisik": 15000,
      "selisih": 0
    }
  ],
  "total": 7
}
```

---

## 5. Laporan bug: `PelangganController::create` tidak mengembalikan data

**Prioritas: P1 (bug fungsional)**

Query di `backend/controllers/PelangganController.php`:

```sql
WITH inserted AS (
    INSERT INTO pelanggan(nama_pelanggan, no_hp) VALUES (?, ?) RETURNING id_pelanggan
)
UPDATE pelanggan p
SET kode_kartu = CONCAT('KC-P-', LPAD(p.id_pelanggan::text, 4, '0'))
FROM inserted i
WHERE p.id_pelanggan = i.id_pelanggan
RETURNING p.id_pelanggan, p.nama_pelanggan, p.no_hp, p.kode_kartu, ...
```

**Masalah:** data-modifying CTE tidak terlihat oleh `UPDATE` pada statement yang sama. Baris yang baru di-INSERT belum ada di snapshot yang dipakai `UPDATE`, jadi `UPDATE` tidak mengenai satu baris pun dan `RETURNING` kosong.

**Akibat:** response jadi `{"data": null}` walau pelanggan benar-benar tersimpan (kode kartu tetap terisi karena ada trigger `trg_generate_kode_kartu`). Frontend tidak mendapat `id_pelanggan` maupun `kode_kartu` dari response, jadi harus membaca ulang dari hasil sinkronisasi.

**Saran perbaikan** (pilih salah satu):
1. Pisahkan jadi dua statement: `INSERT ... RETURNING` lalu `UPDATE ... WHERE id_pelanggan = ?`.
2. Hapus `UPDATE` sepenuhnya dan andalkan trigger `fn_auto_kode_kartu_pelanggan()`, lalu `RETURNING` dari `INSERT`.

Contoh opsi 2:

```sql
INSERT INTO pelanggan (nama_pelanggan, no_hp)
VALUES (?, ?)
RETURNING id_pelanggan, nama_pelanggan, no_hp, kode_kartu,
          (password_hash IS NOT NULL) AS is_member
```

---

## 6. Ringkasan prioritas

| # | Endpoint | Prioritas | Membuka |
|---|---|---|---|
| 3 | `jadwal-shift` list/save/cancel | P1 | Master → Shift 100% lewat API |
| 1 | `transaksi.list` + filter tanggal + summary | P1 | Laporan, export CSV, grafik 7 hari |
| 5 | Fix bug `pelanggan.create` | P1 | Response pelanggan baru valid |
| 2 | `pelanggan.list` + agregat | P2 | Kartu member, daftar pelanggan |
| 4 | `rekap.shift` | P3 | Rekap shift & dashboard pemilik |

Setelah #1–#5 selesai, frontend tidak perlu query langsung ke database lagi.

---

## 7. Kontrak yang jangan diubah

Endpoint berikut sudah dipakai frontend dan sudah diuji. Kalau ada perubahan bentuk request/response, tolong kabari dulu:

| Endpoint | Status | Dipakai untuk |
|---|---|---|
| `transaksi.create` | **sudah dipakai** | simpan transaksi (kirim `detail_transaksi[]`, `jumlah_dibayar`) |
| `pelanggan.create` | **sudah dipakai** | tambah member dari form kasir |
| `shift-operasional.open` / `close` | **sudah dipakai** | presensi & tutup shift |
| `pengguna.create` / `update` / `delete` | **sudah dipakai** | manajemen staf |
| `layanan.list` | belum dipakai | katalog layanan (menunggu endpoint lain beres) |
| `shift.list` | belum dipakai | master shift |
| `dashboard.*` / `rekap.*` | belum dipakai | dashboard & laporan |

**Catatan tambahan:** koneksi database berjalan pada zona UTC. Frontend sudah menjalankan `SET TIME ZONE 'Asia/Jakarta'` per koneksi supaya `CURRENT_TIME` / `CURRENT_DATE` di logika shift sesuai WIB. Kalau backend mau memindahkan pengaturan ini ke `config/database.php`, silakan — asal jangan dihapus.
