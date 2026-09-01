# 🏫 School Class System - Dokumentasi API

Sistem Manajemen Klasifikasi & Pemilihan Paket Menu Kelas (School Class System) berbasis **Laravel 11**, **FrankenPHP**, **PostgreSQL**, dan **Docker**.

---

## 📑 Daftar Isi
- [Teknologi & Arsitektur](#-teknologi--arsitektur)
- [Cara Menjalankan (Quick Start)](#-cara-menjalankan-quick-start)
- [Mekanisme Autentikasi & Guard](#-mekanisme-autentikasi--guard)
- [Dokumentasi Lengkap API](#-dokumentasi-lengkap-api)
  - [1. Autentikasi User (Admin / Guru BK)](#1-autentikasi-user-admin--guru-bk)
  - [2. Autentikasi & Registrasi Siswa](#2-autentikasi--registrasi-siswa)
  - [3. Kelas Asal (Kelas X)](#3-kelas-asal-kelas-x)
  - [4. Paket Menu Pilihan](#4-paket-menu-pilihan)
  - [5. Master Mata Pelajaran](#5-master-mata-pelajaran)
  - [6. Kriteria Bobot Menu](#6-kriteria-bobot-menu)
  - [7. Import Leger XLSX, Nilai Mapel & Riwayat (FR-12, FR-13, FR-14)](#7-import-leger-xlsx-nilai-mapel--riwayat-fr-12-fr-13-fr-14)
  - [8. Data Siswa](#8-data-siswa)
  - [9. Periode Penjurusan (FR-9, FR-10, FR-11)](#9-periode-penjurusan-fr-9-fr-10-fr-11)
  - [10. Fitur Khusus Siswa (FR-49, FR-50, FR-51, FR-52)](#10-fitur-khusus-siswa-fr-49-fr-50-fr-51-fr-52)
  - [11. Review & Monitoring Pilihan Siswa - Admin (FR-19, FR-20, FR-21, FR-22, FR-40 s/d FR-44)](#11-review--monitoring-pilihan-siswa---admin-fr-19-fr-20-fr-21-fr-22-fr-40-sd-fr-44)
  - [12. Manajemen & Penentuan Hasil Penjurusan (FR-23 s/d FR-32)](#12-manajemen--penentuan-hasil-penjurusan-fr-23-sd-fr-32)
  - [13. Pengajuan Pertukaran Kelas / Paket (Siswa & Admin)](#13-pengajuan-pertukaran-kelas--paket-siswa--admin)
  - [14. Laporan & Ekspor Data (FR-33 s/d FR-38)](#14-laporan--ekspor-data-fr-33-sd-fr-38)
  - [15. Proyeksi Universitas & Program Studi (FR-39, FR-57)](#15-proyeksi-universitas--program-studi-fr-39-fr-57)
  - [16. Laporan Pesan / Helpdesk (Pengaduan & Feedback)](#16-laporan-pesan--helpdesk-pengaduan--feedback)
  - [17. Sinkronisasi Jam Server (Server Clock API)](#17-sinkronisasi-jam-server-server-clock-api)

---

## 🚀 Teknologi & Arsitektur

* **Framework**: Laravel 11.x (PHP 8.3)
* **Application Server**: FrankenPHP (Caddy Server)
* **Database**: PostgreSQL
* **Queue Worker**: Laravel Database Queue (Asynchronous Job Processing)
* **Containerization**: Docker Compose (App & Queue Worker)
* **Default Port**: `8080` (Base URL: `http://localhost:8080`)

---

## 🛠️ Cara Menjalankan (Quick Start)

### 1. Menjalankan Server via Docker Compose
```bash
docker compose up -d --build
```
Server web akan berjalan di `http://localhost:8080` dan background queue worker berjalan di container `school_class_system_queue`.

### 2. Menjalankan Database Seeder (Admin & Default Data)
```bash
docker exec school_class_system_app php artisan db:seed
```

### 3. Menjalankan Uji Otomatis (Automated Testing)
```bash
docker exec school_class_system_app php artisan test
```

---

## 🔐 Mekanisme Autentikasi & Guard

Aplikasi ini menggunakan **Multi-Guard Session Authentication**:
1. **Guard `web`**: Digunakan oleh **Admin** & **Guru BK** (`users` table).
2. **Guard `siswa`**: Digunakan oleh **Siswa** (`siswa` table) melalui login NISN & Password.
3. **Middleware `auth.any`**: Mengizinkan akses jika user terautentikasi di **salah satu** guard (`web` maupun `siswa`).

> 💡 **Headers HTTP Wajib untuk Request JSON API:**
> ```http
> Content-Type: application/json
> Accept: application/json
> ```

---

## 📖 Dokumentasi Lengkap API

### 1. Autentikasi User (Admin / Guru BK)

#### A. Login User (Admin / Guru BK)
* **Endpoint**: `POST /login`
* **Access**: Public
* **Payload Request**:
```json
{
  "username": "admin",
  "password": "password"
}
```
* **Response Status**: `200 OK`
```json
{
  "success": true,
  "message": "Berhasil login sebagai Administrator.",
  "data": {
    "user": {
      "id": "ab7841f3-...",
      "username": "admin",
      "role": "admin",
      "is_active": true
    }
  }
}
```
*(Default credentials Seeder: Admin `username: "admin"`, Guru BK `username: "guru_bk"`, Password: `"password"`)*

#### B. Logout User
* **Endpoint**: `POST /logout`
* **Access**: `auth:web`

#### C. Profil User Aktif
* **Endpoint**: `GET /me`
* **Access**: `auth:web`

---

### 2. Autentikasi & Registrasi Siswa

#### A. Cek Status Registrasi NISN (Tahap 1)
* **Endpoint**: `POST /register/siswa/check`
* **Access**: Public
* **Payload Request**:
```json
{
  "nisn": "0012345678"
}
```

#### B. Melengkapi Registrasi Siswa (Tahap 2)
* **Endpoint**: `POST /register/siswa`
* **Access**: Public
* **Payload Request**:
```json
{
  "nisn": "0012345678",
  "jenis_kelamin": "L",
  "tanggal_lahir": "2008-05-20",
  "angkatan": "2024",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### C. Login Siswa
* **Endpoint**: `POST /login/siswa`
* **Access**: Public
* **Payload Request**:
```json
{
  "nisn": "0012345678",
  "password": "password123"
}
```
* **Respon & Status Error**:
  * `200 OK`: Login berhasil (`{"success": true, "message": "Berhasil login sebagai siswa.", "data": ...}`)
  * `403 Forbidden` *(Jika akun belum registrasi password & `is_active: false`)*: `{"success": false, "message": "NISN Anda belum didaftarkan."}`
  * `403 Forbidden` *(Jika akun telah terisi password tapi `is_active: false`)*: `{"success": false, "message": "Akun Anda sedang dinonaktifkan."}`
  * `401 Unauthorized` *(Jika NISN tidak ditemukan)*: `{"success": false, "message": "NISN tidak terdaftar."}`
  * `401 Unauthorized` *(Jika password salah)*: `{"success": false, "message": "Password salah."}`

#### D. Profil Siswa Aktif
* **Endpoint**: `GET /siswa/profile`
* **Access**: `auth:siswa`

#### E. Logout Siswa
* **Endpoint**: `POST /logout/siswa`
* **Access**: `auth:siswa`

---

### 3. Kelas Asal (Kelas X)

#### A. Daftar Kelas X
* **Endpoint**: `GET /kelas-asal`
* **Access**: `auth.any` (Siswa / Admin / Guru BK)
* **Query Parameters** *(opsional)*:
  * `search`: Pencarian nama kelas (misal: `X A`)
  * `is_active`: `true` / `false` / `all`

#### B. Detail Kelas X
* **Endpoint**: `GET /kelas-asal/{id_or_nama}`
* **Access**: `auth.any`

#### C. Tambah Kelas X Baru
* **Endpoint**: `POST /kelas-asal`
* **Access**: `auth:web` (Admin)
* **Payload Request**:
```json
{
  "nama_kelas": "X A",
  "tingkat": "X",
  "kapasitas": 36,
  "is_active": true
}
```

##### ⚠️ Penanganan Konflik Data yang Pernah Di-Soft Delete:
Jika `nama_kelas` pernah dihapus (*soft deleted*), server akan mengembalikan respon **`409 Conflict`**:
```json
{
  "success": false,
  "is_trashed": true,
  "message": "Kelas dengan nama 'X A' pernah dihapus sebelumnya. Apakah Anda ingin memulihkan (restore) atau menimpa dengan data baru (overwrite)?",
  "options": {
    "restore": "Gunakan payload JSON {\"action\": \"restore\"} atau query parameter ?restore=1 untuk memulihkan dan memperbarui data lama.",
    "overwrite": "Gunakan payload JSON {\"action\": \"overwrite\"} atau query parameter ?overwrite=1 untuk menghapus permanen data lama dan membuat data baru."
  }
}
```

* **Pilihan 1: Pulihkan Data Lama & Update (Opsi 1)**
  Kirim payload: `{"nama_kelas": "X A", "kapasitas": 36, "action": "restore"}`
  -> Mengembalikan `200 OK`, `deleted_at` dihapus, dan data diperbarui.

* **Pilihan 2: Menimpa Data Baru / Force Delete & Create (Opsi 3)**
  Kirim payload: `{"nama_kelas": "X A", "kapasitas": 36, "action": "overwrite"}`
  -> Mengembalikan `201 Created`, record lama dihapus permanen, dan record baru dibuat dengan UUID baru.

#### D. Update Kelas X
* **Endpoint**: `PUT /kelas-asal/{id}`
* **Access**: `auth:web` (Admin)

#### E. Hapus Kelas X (Soft Delete)
* **Endpoint**: `DELETE /kelas-asal/{id}`
* **Access**: `auth:web` (Admin)
* *Catatan*: Menggunakan **Soft Deletes** (`deleted_at`). Data kelas yang dihapus tidak akan hilang permanen dari database, tetapi tidak akan muncul pada rute `GET /kelas-asal`.

---

### 4. Paket Menu Pilihan

#### A. Daftar Paket Menu Pilihan
* **Endpoint**: `GET /paket-menu-pilihan`
* **Access**: `auth.any`
* **Query Parameters** *(opsional)*:
  * `rumpun`: Filter rumpun (`eksakta` / `sosial`)
  * `search`: Pencarian nama menu

#### B. Detail Paket Menu Pilihan
* **Endpoint**: `GET /paket-menu-pilihan/{id_or_nama}`
* **Access**: `auth.any`

#### C. Tambah Paket Menu Pilihan
* **Endpoint**: `POST /paket-menu-pilihan`
* **Access**: `auth:web` (Admin)
* **Payload Request**:
```json
{
  "nama_menu": "Menu 1 (P1)",
  "rumpun": "eksakta",
  "kuota_kapasitas": 36
}
```

##### ⚠️ Penanganan Konflik Data yang Pernah Di-Soft Delete:
Jika `nama_menu` pernah dihapus (*soft deleted*), server akan mengembalikan respon **`409 Conflict`**:
```json
{
  "success": false,
  "is_trashed": true,
  "message": "Paket menu pilihan dengan nama 'Menu 1 (P1)' pernah dihapus sebelumnya. Apakah Anda ingin memulihkan (restore) atau menimpa dengan data baru (overwrite)?",
  "options": {
    "restore": "Gunakan payload JSON {\"action\": \"restore\"} atau query parameter ?restore=1 untuk memulihkan dan memperbarui data lama.",
    "overwrite": "Gunakan payload JSON {\"action\": \"overwrite\"} atau query parameter ?overwrite=1 untuk menghapus permanen data lama dan membuat data baru."
  }
}
```

#### D. Update & Hapus Paket Menu (Soft Delete)
* `PUT /paket-menu-pilihan/{identifier}` (`auth:web`)
* `DELETE /paket-menu-pilihan/{identifier}` (`auth:web`)
* *Catatan*: `DELETE` menggunakan **Soft Deletes** (`deleted_at`).

---

### 5. Master Mata Pelajaran

#### A. Daftar Master Mata Pelajaran
* **Endpoint**: `GET /master-mata-pelajaran`
* **Access**: `auth.any`
* **Query Parameters** *(opsional)*:
  * `kelompok_mapel`: `umum`, `pilihan`, `muatan_lokal`
  * `is_tiebreaker_default`: `true` / `false`
  * `search`: Nama/kode mapel

#### B. Detail Master Mata Pelajaran
* **Endpoint**: `GET /master-mata-pelajaran/{id_or_kode}`
* **Access**: `auth.any`

#### C. Tambah Master Mata Pelajaran
* **Endpoint**: `POST /master-mata-pelajaran`
* **Access**: `auth:web` (Admin / Guru BK)
* **Payload Request**:
```json
{
  "kode_mapel": "MAT_W",
  "nama_mapel": "Matematika Wajib",
  "kelompok_mapel": "umum",
  "is_tiebreaker_default": true,
  "is_active": true
}
```

#### D. Update & Hapus Master Mata Pelajaran (Soft Delete)
* `PUT /master-mata-pelajaran/{id}` (`auth:web`)
* `DELETE /master-mata-pelajaran/{id}` (`auth:web`)

---

### 6. Kriteria Bobot Menu

#### A. Daftar Kriteria Bobot Menu
* **Endpoint**: `GET /kriteria-bobot-menu`
* **Access**: `auth.any`
* **Query Parameter**: `paket_menu_pilihan_id={uuid}`

#### B. Simpan Kriteria Bobot Menu (Single / Bulk)
* **Endpoint**: `POST /kriteria-bobot-menu`
* **Access**: `auth:web` (Admin / Guru BK)
* **Payload Request (Bulk)**:
```json
{
  "paket_menu_pilihan_id": "uuid-paket-menu",
  "kriteria": [
    {
      "master_mata_pelajaran_id": "uuid-mapel-matematika",
      "bobot_persen": 40.00
    },
    {
      "master_mata_pelajaran_id": "uuid-mapel-fisika",
      "bobot_persen": 60.00
    }
  ]
}
```

#### C. Update Bobot Persen Kriteria (Single / Bulk Array)
* **Endpoint**: `PUT /kriteria-bobot-menu/{paket_menu_pilihan_id_atau_kriteria_id}`
* **Access**: `auth:web` (Admin)

#### D. Hapus Kriteria Bobot
* `DELETE /kriteria-bobot-menu/{id}` (`auth:web`)

---

### 7. Import Leger XLSX, Nilai Mapel & Riwayat (FR-12, FR-13, FR-14)

#### A. Unggah File Leger Excel (FR-12 Asynchronous / Queue Job)
* **Endpoint**: `POST /leger/import`
* **Access**: `auth:web` (Khusus Admin)
* **Content-Type**: `multipart/form-data`
* **Form Fields**:
  * `file`: File Excel (`.xlsx` / `.xls`)
  * `kelas_asal_id`: UUID / nama kelas (misal: `X A`)
  * `angkatan`: String angkatan (misal: `2024/2025`)
  * `sync`: *(Opsional)* `1` jika ingin diproses secara langsung tanpa queue.

* **Response Status**: `202 Accepted` (Default Asynchronous)
```json
{
  "success": true,
  "message": "File XLSX Leger berhasil diterima dan sedang diproses di background queue (Asynchronous).",
  "status": "queued",
  "file_name": "leger_3386d278-636c-4980-8570-c70545d9107e.xlsx",
  "file_url": "http://localhost:8080/leger/download/leger_3386d278-636c-4980-8570-c70545d9107e.xlsx",
  "kelas": "X A",
  "angkatan": "2024/2025"
}
```

#### B. Riwayat Unggah Leger Excel
* **Endpoint**: `GET /leger/history`
* **Access**: `auth.any`

#### C. Unduh Berkas Leger XLSX
* **Endpoint**: `GET /leger/download/{filename}`
* **Access**: `auth.any`

#### D. Impor Nilai Siswa per Mata Pelajaran Tertentu (FR-13)
* **Endpoint**: `POST /nilai-siswa/import-mapel`
* **Access**: `auth:web` (Admin only)
* **Payload Request**:
```json
{
  "mapel_id": "uuid-master-mapel",
  "tahun_ajaran": "2024/2025",
  "semester": "Genap",
  "rows": [
    { "nisn": "0012345678", "nilai": 88.5 },
    { "nisn": "0012345679", "nilai": 91.0 }
  ]
}
```
* **Response Status**: `202 Accepted` (Background Queue)

#### E. Daftar & Koreksi Nilai Siswa (FR-14)
* **Daftar Nilai**: `GET /nilai-siswa` (`auth:web`)
  * Query: `nisn`, `kelas_asal_id`, `mapel_id`, `tahun_ajaran`, `semester`, `per_page`
* **Koreksi Nilai**: `PUT /nilai-siswa/{detail_nilai_id}` (`auth:web`)
  * Payload: `{"nilai_angka": 87.5}`
  * Server otomatis menghitung ulang predikat dan rata-rata leger siswa.

---

### 8. Data Siswa

Seluruh endpoint di bawah memerlukan `auth:web` dan hanya ditujukan untuk admin.

#### A. Daftar Siswa
* **Endpoint**: `GET /siswa`
* Mengembalikan daftar siswa aktif beserta relasi kelas asal. Siswa dengan `deleted_at` terisi tidak disertakan.

#### B. Detail Siswa
* **Endpoint**: `GET /siswa/{id}`

#### C. Tambah Siswa
* **Endpoint**: `POST /siswa`
* **Payload Request**:
```json
{
  "nisn": "0012345678",
  "nis": "1234567890",
  "nama_lengkap": "Budi Santoso",
  "kelas_asal_id": "uuid-kelas-asal",
  "jenis_kelamin": "L",
  "tanggal_lahir": "2008-05-20",
  "angkatan": "2024"
}
```

#### D. Ubah & Hapus Siswa
* `PUT /siswa/{id}` (`auth:web`) - Update data dan status `is_active`
* `DELETE /siswa/{id}` (`auth:web`) - Soft Deletes data siswa

---

### 9. Periode Penjurusan (FR-9, FR-10, FR-11)

#### A. Daftar Periode
* **Endpoint**: `GET /periode-penjurusan`
* **Access**: `auth.any`

#### B. Detail Periode
* **Endpoint**: `GET /periode-penjurusan/{id}`
* **Access**: `auth.any`

#### C. Buat Periode
* **Endpoint**: `POST /periode-penjurusan`
* **Access**: `auth:web` (Admin)

#### D. Ubah Periode, Jadwal Pengisian Minat & Jadwal Pertukaran
* **Endpoint**: `PUT /periode-penjurusan/{id}`
* **Access**: `auth:web` (Admin)

##### Pengaturan Pilihan & Jadwal Pengisian Minat (FR-9):
| Field | Tipe | Keterangan |
|---|---|---|
| `max_pilihan_siswa` | `integer` (nullable) | Batas maksimum paket prioritas yang dipilih siswa (default: `3`, min: 1) |
| `tanggal_buka` | `datetime` | Tanggal mulai pengisian minat oleh siswa |
| `tanggal_tutup` | `datetime` | Tanggal berakhir pengisian minat; wajib setelah `tanggal_buka` |
| `tanggal_pengumuman` | `datetime` (nullable) | Jadwal tanggal/waktu pembukaan pengumuman hasil penjurusan otomatis untuk siswa; wajib setelah `tanggal_buka` |

##### Jadwal Pengajuan Pertukaran Kelas/Paket (FR-10 & FR-11):
| Field | Tipe | Keterangan |
|---|---|---|
| `tanggal_mulai_pertukaran` | `datetime` (nullable) | Tanggal mulai pengajuan pertukaran kelas/paket oleh siswa |
| `tanggal_selesai_pertukaran` | `datetime` (nullable) | Tanggal berakhir pengajuan pertukaran; wajib setelah `tanggal_mulai_pertukaran` |

* **Payload Request**:
```json
{
  "nama_periode": "Penjurusan 2026/2027",
  "tahun_ajaran": "2026/2027",
  "gelombang": "Utama",
  "max_pilihan_siswa": 3,
  "tanggal_buka": "2026-08-11 08:00:00",
  "tanggal_tutup": "2026-08-20 23:59:59",
  "tanggal_pengumuman": "2026-08-24 10:00:00",
  "tanggal_mulai_pertukaran": "2026-08-25 08:00:00",
  "tanggal_selesai_pertukaran": "2026-08-30 23:59:59"
}
```

---

### 10. Fitur Khusus Siswa (FR-49, FR-50, FR-51, FR-52)

Endpoint khusus untuk siswa yang sudah terautentikasi (guard `siswa`). Semua endpoint di bawah menggunakan middleware `auth:siswa`.

#### A. Siswa Melihat Nilai Mata Pelajaran (FR-49)
* **Endpoint**: `GET /siswa/nilai`
* **Access**: `auth:siswa`
* **Deskripsi**: Mengambil daftar nilai mata pelajaran milik siswa yang sedang login.

#### B. Siswa Melihat Daftar Paket Aktif Periode Berjalan (FR-50)
* **Endpoint**: `GET /siswa/paket-menu-aktif`
* **Access**: `auth:siswa`

#### C. Siswa Melihat Detail Paket Lengkap (FR-51)
* **Endpoint**: `GET /siswa/paket-menu-aktif/{identifier}`
* **Access**: `auth:siswa`

#### D. Siswa Memilih 3 Paket Prioritas (FR-52)
* **Endpoint**: `POST /siswa/pendaftaran-pilihan`
* **Access**: `auth:siswa`
* **Payload Request**:
```json
{
  "pilihan": [
    "uuid-paket-prioritas-1",
    "uuid-paket-prioritas-2",
    "uuid-paket-prioritas-3"
  ]
}
```

#### E. Siswa Melihat Pilihan yang Telah Dikirimkan
* **Endpoint**: `GET /siswa/pendaftaran-pilihan`
* **Access**: `auth:siswa`

---

### 11. Review & Monitoring Pilihan Siswa - Admin (FR-19, FR-20, FR-21, FR-22, FR-40 s/d FR-44)

Modul bagi admin/guru BK untuk memantau status pengisian minat siswa, memeriksa pilihan, mengunduh dokumen wali, dan menyetujui/menolak pengajuan.

#### A. Daftar Seluruh Pengajuan Pilihan Siswa (FR-19, FR-40)
* **Endpoint**: `GET /admin/pendaftaran-pilihan`
* **Access**: `auth:web`
* **Query Parameters**:
  * `status`: Filter status (`menunggu`, `disetujui`, `ditolak`)
  * `search`: Pencarian nama siswa atau NISN
  * `per_page`: Jumlah data per halaman

#### B. Detail Satu Pengajuan Pilihan (FR-41)
* **Endpoint**: `GET /admin/pendaftaran-pilihan/{id}`
* **Access**: `auth:web`

#### C. Setujui Pengajuan Pilihan Siswa (FR-42)
* **Endpoint**: `PUT /admin/pendaftaran-pilihan/{id}/approve`
* **Access**: `auth:web`

#### D. Tolak Pengajuan Pilihan Siswa (FR-43)
* **Endpoint**: `PUT /admin/pendaftaran-pilihan/{id}/reject`
* **Access**: `auth:web`
* **Payload Request**:
```json
{
  "catatan_penolakan": "Dokumen surat pernyataan orang tua belum lengkap."
}
```

#### E. Unduh Dokumen Wali Siswa (FR-44)
* **Endpoint**: `GET /admin/pendaftaran-pilihan/{id}/dokumen`
* **Access**: `auth:web`

#### F. Rekap Siswa Sudah & Belum Mengisi Pilihan (FR-20)
* **Endpoint**: `GET /admin/siswa/status-pilihan`
* **Access**: `auth:web`
* **Query Parameters**:
  * `periode_id` (Wajib): UUID periode pendaftaran
  * `kelas_asal_id` (Opsional): Filter kelas X
  * `search` (Opsional): Nama / NISN

* **Response Status**: `200 OK`
```json
{
  "success": true,
  "meta": {
    "total_siswa": 36,
    "total_sudah": 28,
    "total_belum": 8,
    "periode_id": "uuid-periode"
  },
  "data": [
    {
      "siswa": { "id": "uuid-siswa", "nisn": "0012345678", "nama_lengkap": "Budi", "kelas_asal": "X A" },
      "has_submitted": true,
      "submission": { "status": "disetujui" },
      "pilihan": [
        { "urutan_pilihan": 1, "paket_menu": { "nama_menu": "Paket SAINS 1" } }
      ]
    }
  ]
}
```

#### G. Urutan Prioritas Pilihan Siswa Tertentu (FR-22)
* **Endpoint**: `GET /admin/siswa/{siswaId}/pilihan`
* **Access**: `auth:web`
* Menampilkan riwayat pendaftaran dan urutan 3 prioritas paket yang dipilih siswa.

---

### 12. Manajemen & Penentuan Hasil Penjurusan (FR-23 s/d FR-32)

Modul utama perhitungan algoritma placement penjurusan, simulasi hasil sementara, intervensi manual (override), penguncian hasil final, hingga publikasi pengumuman ke siswa.

#### A. Menjalankan Proses Kalkulasi Penjurusan (FR-23)
* **Endpoint**: `POST /admin/hasil-penjurusan/process`
* **Access**: `auth:web` (Admin only)
* **Deskripsi**: Menjalankan *PenjurusanPlacementService* untuk menghitung skor pembobotan, perangkingan, dan penempatan paket siswa secara otomatis.
* **Payload Request**:
```json
{
  "periode_id": "uuid-periode-pendaftaran"
}
```
* **Response Status**: `200 OK`
```json
{
  "success": true,
  "message": "Proses penentuan paket kelas berhasil dijalankan.",
  "data": {
    "total_peserta": 120,
    "ditempatkan": 115,
    "belum_ditempatkan": 5,
    "summary_kuota": [
      { "nama_menu": "Paket SAINS 1", "kuota": 36, "terisi": 36 }
    ]
  }
}
```

#### B. Melihat Hasil Penjurusan Siswa (FR-24, FR-25)
* **Endpoint**: `GET /admin/hasil-penjurusan`
* **Access**: `auth:web` (Admin only)
* **Query Parameters**:
  * `periode_id` (Wajib): UUID periode
  * `search`: Nama/NISN siswa
  * `per_page`: Jumlah data per halaman

#### C. Rekap Penempatan Kuota Kelas (FR-26)
* **Endpoint**: `GET /admin/hasil-penjurusan/rekap-kuota`
* **Access**: `auth:web` (Admin only)
* **Query Parameter**: `periode_id={uuid}`
* Menampilkan kapasitas, jumlah terisi, sisa kuota, dan persentase keterisian setiap paket kelas.

#### D. Detail Skor & Hasil Siswa Tertentu (FR-25)
* **Endpoint**: `GET /admin/hasil-penjurusan/siswa/{siswaId}`
* **Access**: `auth:web` (Admin only)

#### E. Override / Ubah Hasil Penjurusan Siswa Manual (FR-27, FR-28)
* **Endpoint**: `PUT /admin/hasil-penjurusan/{hasilId}/override`
* **Access**: `auth:web` (Admin only)
* **Deskripsi**: Admin memindahkan penempatan paket siswa secara manual dengan kewajiban mengisi alasan perubahan.
* **Payload Request**:
```json
{
  "paket_menu_pilihan_id": "uuid-paket-tujuan",
  "catatan_perubahan": "Pelimpahan kompetensi berdasarkan rekomendasi Guru BK dan orang tua."
}
```

#### F. Tetapkan Hasil sebagai Final / Kunci Hasil (FR-29)
* **Endpoint**: `POST /admin/hasil-penjurusan/lock`
* **Access**: `auth:web` (Admin only)
* **Payload Request**: `{"periode_id": "uuid-periode"}`
* *Catatan*: Saat dikunci, override manual tidak dapat dilakukan kecuali kunci dibuka kembali.

#### G. Buka Kunci Hasil Final (FR-30)
* **Endpoint**: `POST /admin/hasil-penjurusan/unlock`
* **Access**: `auth:web` (Admin only)
* **Payload Request**: `{"periode_id": "uuid-periode"}`

#### H. Publikasikan Hasil Penjurusan ke Siswa (FR-31)
* **Endpoint**: `POST /admin/hasil-penjurusan/publish`
* **Access**: `auth:web` (Admin only)
* **Payload Request**: `{"periode_id": "uuid-periode"}`
* Mengubah `status_pengumuman` menjadi `AKTIF`.

#### I. Non-aktifkan Publikasi Hasil Penjurusan (FR-32)
* **Endpoint**: `POST /admin/hasil-penjurusan/unpublish`
* **Access**: `auth:web` (Admin only)
* **Payload Request**: `{"periode_id": "uuid-periode"}`

---

### 13. Pengajuan Pertukaran Kelas / Paket (Siswa & Admin)

Fitur bagi siswa untuk mengajukan permohonan pindah kelas/paket setelah pengumuman, serta bagi admin untuk meninjau berkas persetujuan orang tua/wali.

#### A. [Siswa] Melihat Status Pengajuan Pertukaran Sendiri
* **Endpoint**: `GET /siswa/pertukaran`
* **Access**: `auth:siswa`

#### B. [Siswa] Mengirimkan Pengajuan Pertukaran
* **Endpoint**: `POST /siswa/pertukaran`
* **Access**: `auth:siswa`
* **Content-Type**: `multipart/form-data`
* **Form Fields**:
  * `paket_tujuan_id` (Wajib): UUID paket tujuan (tidak boleh sama dengan paket penempatan saat ini)
  * `alasan` (Wajib): Alasan permohonan pertukaran
  * `dokumen_persetujuan` (Opsional): Berkas PDF/JPG/PNG surat persetujuan wali (maks 2MB)

#### C. [Siswa] Membatalkan Pengajuan Pertukaran
* **Endpoint**: `DELETE /siswa/pertukaran/{id}`
* **Access**: `auth:siswa`
* *Hanya dapat dibatalkan jika status pengajuan masih `menunggu`*.

#### D. [Admin] Daftar Seluruh Pengajuan Pertukaran Siswa
* **Endpoint**: `GET /admin/pertukaran`
* **Access**: `auth:web` (Admin only)
* **Query Parameters**: `status` (`menunggu`, `disetujui`, `ditolak`), `search`, `per_page`

#### E. [Admin] Detail Pengajuan Pertukaran
* **Endpoint**: `GET /admin/pertukaran/{id}`
* **Access**: `auth:web` (Admin only)

#### F. [Admin] Unduh Dokumen Persetujuan Wali
* **Endpoint**: `GET /admin/pertukaran/{id}/dokumen`
* **Access**: `auth:web` (Admin only)

#### G. [Admin] Setujui Pertukaran Kelas
* **Endpoint**: `PUT /admin/pertukaran/{id}/approve`
* **Access**: `auth:web` (Admin only)
* **Payload Request**:
```json
{
  "catatan_admin": "Disetujui setelah konfirmasi dengan wali kelas dan ketersediaan kuota."
}
```
* *Sistem secara otomatis memperbarui record `hasil_seleksi` siswa ke paket tujuan*.

#### H. [Admin] Tolak Pertukaran Kelas
* **Endpoint**: `PUT /admin/pertukaran/{id}/reject`
* **Access**: `auth:web` (Admin only)
* **Payload Request**:
```json
{
  "catatan_admin": "Ditolak karena kuota paket tujuan sudah penuh."
}
```

---

### 14. Laporan & Ekspor Data (FR-33 s/d FR-38)

Modul pelaporan komprehensif dengan dukungan filter dinamis dan ekspor berkas format Spreadsheet (CSV/XLSX) serta Printable PDF.

#### A. Laporan Hasil Penjurusan Siswa (FR-33, FR-34, FR-37)
* **Endpoint**: `GET /admin/laporan/hasil-penjurusan`
* **Access**: `auth:web` (Admin only)
* **Query Parameters**:
  * `periode_id` (Wajib): UUID periode
  * `paket_id`: Filter paket penempatan
  * `kelas_asal_id`: Filter kelas X asal
  * `mekanisme`: Filter jalur (`Pilihan 1`, `Pilihan 2`, `Pilihan 3`, `Pelimpahan Kuota`, `Pelimpahan Kompetensi`)
  * `search`: Nama/NISN
  * `per_page`: Pagination

#### B. Laporan Pilihan Minat Siswa (FR-35, FR-37)
* **Endpoint**: `GET /admin/laporan/minat-siswa`
* **Access**: `auth:web` (Admin only)
* **Query Parameters**: `periode_id`, `paket_id`, `kelas_asal_id`, `status`, `search`, `per_page`

#### C. Analisis Rekap Peminat vs Kuota (FR-36)
* **Endpoint**: `GET /admin/laporan/peminat-vs-kuota`
* **Access**: `auth:web` (Admin only)
* **Query Parameter**: `periode_id={uuid}`
* Menghitung total peminat prioritas 1, prioritas 2, prioritas 3, kuota kapasitas, jumlah terisi, dan sisa kuota setiap paket.

#### D. Ekspor Laporan Hasil Penjurusan (FR-38)
* **Endpoint**: `GET /admin/laporan/export/hasil-penjurusan`
* **Access**: `auth:web` (Admin only)
* **Query Parameters**:
  * `periode_id` (Wajib): UUID periode
  * `format` (Wajib): `xlsx`, `csv`, atau `pdf`
  * Filter pendukung: `paket_id`, `kelas_asal_id`, `mekanisme`

#### E. Ekspor Laporan Minat Siswa (FR-38)
* **Endpoint**: `GET /admin/laporan/export/minat-siswa`
* **Access**: `auth:web` (Admin only)
* **Query Parameters**:
  * `periode_id` (Wajib): UUID periode
  * `format` (Wajib): `xlsx`, `csv`, atau `pdf`
  * Filter pendukung: `paket_id`, `kelas_asal_id`, `status`

---

### 15. Proyeksi Universitas & Program Studi (FR-39, FR-57)

Endpoint untuk mengelola data proyeksi universitas dan program studi. Fitur ini memungkinkan siswa melihat informasi universitas dan program studi, serta admin untuk mengelola data tersebut.

> [!IMPORTANT]
> - **Read access**: `auth.any` (Siswa / Admin / Guru BK)
> - **Write access**: `auth:web` dengan role `admin` saja (dicek di controller)
> - FR-39: Proyeksi Universitas
> - FR-57: Program Studi

#### A. Daftar Proyeksi Universitas (FR-39)
* **Endpoint**: `GET /proyeksi-universitas`
* **Access**: `auth.any`
* **Query Parameters**: `search`, `per_page`, `is_active`

#### B. Detail Proyeksi Universitas dengan Program Studi (FR-57)
* **Endpoint**: `GET /proyeksi-universitas/{id}`
* **Access**: `auth.any`

#### C. Tambah Proyeksi Universitas (FR-39)
* **Endpoint**: `POST /proyeksi-universitas`
* **Access**: `auth:web` (Admin only)
* **Payload Request**:
```json
{
  "nama_universitas": "Universitas Gadjah Mada",
  "singkatan": "UGM",
  "akreditasi": "Unggul",
  "lokasi_kota": "Sleman",
  "lokasi_provinsi": "D.I. Yogyakarta",
  "website": "https://ugm.ac.id",
  "deskripsi": "Perguruan tinggi negeri berkelas dunia di Yogyakarta",
  "tahun_data": 2026,
  "is_active": true
}
```

#### D. Ubah & Hapus Proyeksi Universitas
* `PUT /proyeksi-universitas/{id}` (`auth:web` - Admin only)
* `DELETE /proyeksi-universitas/{id}` (`auth:web` - Admin only)

#### E. Daftar Program Studi (FR-57)
* **Endpoint**: `GET /program-studi`
* **Access**: `auth.any`
* **Query Parameters**: `search`, `per_page`, `proyeksi_universitas_id`, `jenjang`, `kelompok_saintek_soshum`, `is_active`

#### F. Detail Program Studi (FR-57)
* **Endpoint**: `GET /program-studi/{id}`
* **Access**: `auth.any`

#### G. Tambah, Ubah & Hapus Program Studi (FR-57)
* `POST /program-studi` (`auth:web` - Admin only)
* `PUT /program-studi/{id}` (`auth:web` - Admin only)
* `DELETE /program-studi/{id}` (`auth:web` - Admin only)

---

### 16. Laporan Pesan / Helpdesk (Pengaduan & Feedback)

Modul helpdesk bagi siswa atau publik untuk mengirimkan aduan/pertanyaan seputar sistem penjurusan, dan bagi tim Admin / Guru BK untuk meresponsnya.

#### A. Mengirimkan Laporan Pesan Baru
* **Endpoint**: `POST /laporan-pesan`
* **Access**: Public / Siswa / Admin
* **Payload Request**:
```json
{
  "judul": "Kendala Pemilihan Paket Menu SAINS",
  "kategori": "teknis",
  "pesan": "Saya mengalami kendala saat memilih paket 3 pada periode berjalan.",
  "nisn": "0012345678",
  "nama": "Budi Santoso",
  "kelas": "X A"
}
```
*(Jika login sebagai siswa/admin, field identitas otomatis terisi dari session)*.

#### B. Siswa Melihat Daftar & Detail Pengaduan Sendiri
* `GET /siswa/laporan-pesan` (`auth:siswa`)
* `GET /siswa/laporan-pesan/{id}` (`auth:siswa`)

#### C. Admin / Guru BK Mengelola Seluruh Pengaduan
* **Daftar Laporan**: `GET /laporan-pesan` (`auth:web`)
* **Detail Laporan**: `GET /laporan-pesan/{id}` (`auth:web`)
* **Update Status & Tanggapan**: `PUT /laporan-pesan/{id}/status` (`auth:web`)
  * Payload:
  ```json
  {
    "status": "selesai",
    "catatan_penanganan": "Kendala telah diperbaiki oleh tim IT, silakan coba submit ulang."
  }
  ```
* **Hapus Laporan**: `DELETE /laporan-pesan/{id}` (`auth:web`)

---

### 17. Sinkronisasi Jam Server (Server Clock API)

Endpoint publik untuk sinkronisasi waktu antara client (web frontend / mobile Flutter / timer countdown) dengan server secara real-time.

#### A. Ambil Waktu Jam Server Saat Ini
* **Endpoint**: `GET /server-clock` (atau alias `GET /server-time`)
* **Access**: Public (Tanpa autentikasi)
* **Response**:
```json
{
  "success": true,
  "message": "Waktu server berhasil diambil.",
  "data": {
    "timestamp": 1788221600,
    "timestamp_ms": 1788221600000,
    "datetime": "2026-09-01 08:13:20",
    "iso8601": "2026-09-01T08:13:20+00:00",
    "timezone": "UTC",
    "offset_seconds": 0
  }
}
```
