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
  - [7. Import Leger XLSX & Riwayat](#7-import-leger-xlsx--riwayat)
  - [8. Data Siswa](#8-data-siswa)
  - [9. Periode Penjurusan (FR-9, FR-10, FR-11)](#9-periode-penjurusan-fr-9-fr-10-fr-11)
  - [10. Fitur Khusus Siswa (FR-49, FR-50, FR-51)](#10-fitur-khusus-siswa-fr-49-fr-50-fr-51)
  - [11. Proyeksi Universitas & Program Studi (FR-39, FR-57)](#11-proyeksi-universitas--program-studi-fr-39-fr-57)

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
  "angkatan": "2024/2025",
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

* **Pilihan 1: Pulihkan Data Lama & Update (Opsi 1)**
  Kirim payload: `{"nama_menu": "Menu 1 (P1)", "rumpun": "eksakta", "action": "restore"}`
  -> Mengembalikan `200 OK`, `deleted_at` dihapus, dan data diperbarui.

* **Pilihan 2: Menimpa Data Baru / Force Delete & Create (Opsi 3)**
  Kirim payload: `{"nama_menu": "Menu 1 (P1)", "rumpun": "eksakta", "action": "overwrite"}`
  -> Mengembalikan `201 Created`, record lama dihapus permanen, dan record baru dibuat dengan UUID baru.

#### D. Update & Hapus Paket Menu (Soft Delete)
* `PUT /paket-menu-pilihan/{identifier}` (`auth:web`)
* `DELETE /paket-menu-pilihan/{identifier}` (`auth:web`)
* *Catatan*: `DELETE` menggunakan **Soft Deletes** (`deleted_at`). Data paket menu pilihan yang dihapus tidak akan hilang permanen dari database, tetapi tidak akan muncul pada rute `GET /paket-menu-pilihan`.

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

##### ⚠️ Penanganan Konflik Data yang Pernah Di-Soft Delete:
Jika `kode_mapel` pernah dihapus (*soft deleted*), server akan mengembalikan respon **`409 Conflict`**:
```json
{
  "success": false,
  "is_trashed": true,
  "message": "Mata pelajaran dengan kode 'MAT_W' pernah dihapus sebelumnya. Apakah Anda ingin memulihkan (restore) atau menimpa dengan data baru (overwrite)?",
  "options": {
    "restore": "Gunakan payload JSON {\"action\": \"restore\"} atau query parameter ?restore=1 untuk memulihkan dan memperbarui data lama.",
    "overwrite": "Gunakan payload JSON {\"action\": \"overwrite\"} atau query parameter ?overwrite=1 untuk menghapus permanen data lama dan membuat data baru."
  }
}
```

* **Pilihan 1: Pulihkan Data Lama & Update (Opsi 1)**
  Kirim payload: `{"kode_mapel": "MAT_W", "nama_mapel": "Matematika Wajib", "action": "restore"}`
  -> Mengembalikan `200 OK`, `deleted_at` dihapus, dan data diperbarui.

* **Pilihan 2: Menimpa Data Baru / Force Delete & Create (Opsi 3)**
  Kirim payload: `{"kode_mapel": "MAT_W", "nama_mapel": "Matematika Wajib", "action": "overwrite"}`
  -> Mengembalikan `201 Created`, record lama dihapus permanen, dan record baru dibuat dengan UUID baru.

#### D. Update & Hapus Master Mata Pelajaran (Soft Delete)
* `PUT /master-mata-pelajaran/{id}` (`auth:web`)
* `DELETE /master-mata-pelajaran/{id}` (`auth:web`)
* *Catatan*: `DELETE` menggunakan **Soft Deletes** (`deleted_at`). Data mata pelajaran yang dihapus tidak akan hilang permanen dari database, tetapi tidak akan muncul pada rute `GET /master-mata-pelajaran`.

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
* **Payload Request (Bulk Array by Paket Menu ID)**:
```json
[
  {
    "master_mata_pelajaran_id": "2faf0c73-a2de-4ef7-9792-6a530ec49029",
    "bobot_persen": 50.00
  },
  {
    "master_mata_pelajaran_id": "dfbd086a-eeda-4df1-b59a-88fb4073eaa5",
    "bobot_persen": 60.00
  }
]
```

* **Payload Request (Single Item by Kriteria ID)**:
```json
{
  "bobot_persen": 75.00
}
```

#### D. Hapus Kriteria Bobot
* `DELETE /kriteria-bobot-menu/{id}` (`auth:web`)

---

### 7. Import Leger XLSX & Riwayat

#### A. Unggah File Leger Excel (Asynchronous / Queue Job)
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
* **Query Parameters** *(opsional)*:
  * `nama_kelas`: Filter nama kelas (misal: `X A`)
  * `angkatan`: Filter angkatan (misal: `2024/2025`)

* **Response Status**: `200 OK`
```json
{
  "success": true,
  "message": "Berhasil mengambil riwayat unggah file Leger Excel.",
  "total": 1,
  "data": [
    {
      "id": "62cd06a4-77b0-443a-9a37-0126cea4b24d",
      "nama_kelas": "X A",
      "angkatan": "2024/2025",
      "file_name": "leger_3386d278-636c-4980-8570-c70545d9107e.xlsx",
      "file_url": "http://localhost:8080/leger/download/leger_3386d278-636c-4980-8570-c70545d9107e.xlsx",
      "jumlah_siswa": 36,
      "status": "completed"
    }
  ]
}
```

#### C. Unduh Berkas Leger XLSX
* **Endpoint**: `GET /leger/download/{filename}`
* **Access**: `auth.any`
* Memungkinkan pengguna mengunduh berkas `.xlsx` hasil upload langsung dari server.

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
  "angkatan": "2024",
  "is_active": true,
  "password": "password123"
}
```

#### D. Ubah Siswa
* **Endpoint**: `PUT /siswa/{id}`
* Semua field pada endpoint tambah bersifat opsional saat update.

#### E. Hapus Siswa
* **Endpoint**: `DELETE /siswa/{id}`
* Menggunakan **Soft Deletes**. Record tidak dihapus permanen; `deleted_at` diisi dan data tidak lagi tampil pada query normal.

---

### 9. Periode Penjurusan

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

##### Jadwal Pengisian Minat (FR-9):
| Field | Tipe | Keterangan |
|-------|------|------------|
| `tanggal_buka` | `datetime` | Tanggal mulai pengisian minat oleh siswa |
| `tanggal_tutup` | `datetime` | Tanggal berakhir pengisian minat; wajib setelah `tanggal_buka` |

##### Jadwal Pengajuan Pertukaran Kelas/Paket (FR-10 & FR-11):
| Field | Tipe | Keterangan |
|-------|------|------------|
| `tanggal_mulai_pertukaran` | `datetime` (nullable) | Tanggal mulai pengajuan pertukaran kelas/paket oleh siswa |
| `tanggal_selesai_pertukaran` | `datetime` (nullable) | Tanggal berakhir pengajuan pertukaran; wajib setelah `tanggal_mulai_pertukaran` |

> [!IMPORTANT]
> - `tanggal_buka` dan `tanggal_tutup` wajib diisi saat membuat periode baru.
> - `tanggal_mulai_pertukaran` dan `tanggal_selesai_pertukaran` bersifat opsional; jika diisi, `tanggal_selesai_pertukaran` wajib setelah `tanggal_mulai_pertukaran`.

* Field opsional lainnya: `gelombang`, `max_pilihan_siswa`, `status_pengumuman` (`AKTIF`/`NON-AKTIF`), `is_active`.
* Default pembuatan: `gelombang: "Utama"`, `max_pilihan_siswa: 3`, `status_pengumuman: "NON-AKTIF"`, `is_active: true`.

* **Payload Request Lengkap**:
```json
{
  "nama_periode": "Penjurusan 2026/2027",
  "tahun_ajaran": "2026/2027",
  "tanggal_buka": "2026-08-11 08:00:00",
  "tanggal_tutup": "2026-08-20 23:59:59",
  "tanggal_mulai_pertukaran": "2026-08-25 08:00:00",
  "tanggal_selesai_pertukaran": "2026-08-30 23:59:59"
}
```

---

### 10. Fitur Khusus Siswa (FR-49, FR-50, FR-51)

Endpoint khusus untuk siswa yang sudah terautentikasi (guard `siswa`). Semua endpoint di bawah menggunakan middleware `auth:siswa`.

> [!IMPORTANT]
> - Seluruh endpoint di bab ini **wajib login siswa** (`auth:siswa`).
> - Data otomatis difilter berdasarkan **sesi siswa yang sedang login** (tidak bisa melihat data siswa lain).
> - `FR-49`, `FR-50`, dan `FR-51` adalah fitur *student-facing interface*.

---

#### A. Siswa Melihat Nilai Mata Pelajaran (FR-49)

* **Endpoint**: `GET /siswa/nilai`
* **Access**: `auth:siswa`
* **Deskripsi**: Mengambil daftar nilai mata pelajaran milik siswa yang sedang login.

* **Response Status**: `200 OK`
```json
{
  "success": true,
  "message": "Berhasil mengambil daftar nilai siswa.",
  "data": [
    {
      "id": "uuid-detail-nilai",
      "nilai_leger_siswa_id": "uuid-leger",
      "master_mata_pelajaran_id": "uuid-mapel",
      "nilai_angka": 85.5,
      "predikat": "B",
      "mata_pelajaran": {
        "id": "uuid-mapel",
        "kode_mapel": "MAT_W",
        "nama_mapel": "Matematika Wajib"
      },
      "leger": {
        "id": "uuid-leger",
        "siswa_id": "uuid-siswa",
        "tahun_ajaran": "2024/2025",
        "semester": "Genap",
        "rata_keseluruhan": 85.5
      }
    }
  ]
}
```

> [!NOTE]
> - Response **tidak menggunakan paginasi**. Seluruh nilai mata pelajaran milik siswa yang sedang login dikembalikan sekaligus dalam array `data`.
> - Data otomatis difilter berdasarkan `siswa_id` dari session (`Auth::guard('siswa')`), bukan dari input user.

---

#### B. Siswa Melihat Daftar Paket Aktif Periode Berjalan (FR-50)

* **Endpoint**: `GET /siswa/paket-menu-aktif`
* **Access**: `auth:siswa`
* **Deskripsi**: Menampilkan daftar paket menu pilihan yang **aktif** dan **memiliki minimal 1 kriteria bobot**, pada periode pendaftaran yang sedang berjalan.

* **Query Parameters** *(opsional)*:
  * `with_criteria`: `true` (default) / `false`. Jika `false`, field `kriteria_bobot` dihilangkan dari response untuk performa lebih cepat.

* **Kriteria "Periode Berjalan"**:
  * `is_active = true`
  * `status_pengumuman = "AKTIF"`
  * `tanggal_buka <= sekarang`
  * `tanggal_tutup >= sekarang`

* **Response Status**: `200 OK`
```json
{
  "success": true,
  "message": "Berhasil mengambil daftar paket menu aktif.",
  "meta": {
    "active_periods": [
      {
        "id": "uuid-periode",
        "nama_periode": "2024/2025 - Gelombang Utama",
        "tahun_ajaran": "2024/2025",
        "gelombang": "Utama",
        "tanggal_buka": "2024-07-01",
        "tanggal_tutup": "2024-08-31",
        "status_pengumuman": "AKTIF"
      }
    ],
    "total_paket": 2,
    "last_updated": "2024-08-12T10:00:00+00:00"
  },
  "data": [
    {
      "id": "uuid-paket",
      "nama_menu": "Rumpun SAINS",
      "rumpun": "eksakta",
      "kuota_kapasitas": 36,
      "kuota_terisi": 23,
      "kuota_tersisa": 13,
      "is_active": true,
      "kriteria_bobot": [
        {
          "id": "uuid-kriteria",
          "master_mata_pelajaran_id": "uuid-mapel",
          "nama_mapel": "Matematika",
          "kode_mapel": "MAT_W",
          "bobot_persen": 40.0
        }
      ]
    }
  ]
}
```

---

#### C. Siswa Melihat Detail Paket Lengkap (FR-51)

* **Endpoint**: `GET /siswa/paket-menu-aktif/{identifier}`
* **Access**: `auth:siswa`
* **Deskripsi**: Menampilkan detail lengkap satu paket menu pilihan aktif, termasuk daftar kriteria bobot dan informasi periode aktif berjalan.

* **Path Parameter**:
  * `identifier`: UUID `id` atau `nama_menu` dari paket menu.

* **Catatan**:
  * Hanya paket menu dengan `is_active = true` yang dapat diakses. Jika tidak aktif/tidak ditemukan, mengembalikan `404 Not Found`.

* **Response Status**: `200 OK`
```json
{
  "success": true,
  "message": "Berhasil mengambil detail Paket Menu Pilihan.",
  "data": {
    "id": "uuid-paket",
    "nama_menu": "Rumpun SAINS",
    "rumpun": "eksakta",
    "kuota_kapasitas": 36,
    "kuota_terisi": 15,
    "kuota_tersisa": 21,
    "is_active": true,
    "kriteria_bobot": [
      {
        "id": "uuid-kriteria",
        "master_mata_pelajaran_id": "uuid-mapel",
        "nama_mapel": "Matematika",
        "kode_mapel": "MAT_W",
        "kelompok_mapel": "umum",
        "bobot_persen": 40.0
      }
    ],
    "periode_aktif": {
      "id": "uuid-periode",
      "nama_periode": "2024/2025 - Gelombang Utama",
      "tahun_ajaran": "2024/2025",
      "gelombang": "Utama",
      "tanggal_buka": "2024-07-01",
      "tanggal_tutup": "2024-08-31",
      "status_pengumuman": "AKTIF",
      "max_pilihan_siswa": 3
    }
  }
}
```

---

#### D. Siswa Memilih 3 Paket Prioritas (FR-52)

* **Endpoint**: `POST /siswa/pendaftaran-pilihan`
* **Access**: `auth:siswa`
* **Deskripsi**: Mengirimkan 3 paket menu pilihan prioritas siswa (Prioritas 1, Prioritas 2, dan Prioritas 3) pada periode pendaftaran berjalan.

* **Request Body**:
```json
{
  "pilihan": [
    "uuid-paket-prioritas-1",
    "uuid-paket-prioritas-2",
    "uuid-paket-prioritas-3"
  ]
}
```

* **Aturan Validasi**:
  1. Siswa **wajib memilih 3 paket** (sesuai `max_pilihan_siswa` di periode berjalan).
  2. Ketiga paket **tidak boleh duplikat / sama**.
  3. Semua paket yang dipilih harus berstatus **`is_active = true`**.
  4. Halaman pendaftaran **harus dalam rentang `tanggal_buka` s/d `tanggal_tutup`** periode yang berstatus `is_active = true`.
  5. Siswa hanya diperbolehkan **submit 1 kali** untuk setiap periode pendaftaran (`409 Conflict` jika sudah pernah submit).

* **Response Status**: `201 Created`
```json
{
  "success": true,
  "message": "Berhasil menyimpan 3 paket menu pilihan prioritas Anda.",
  "data": {
    "id": "uuid-pendaftaran",
    "siswa_id": "uuid-siswa",
    "periode_pendaftaran_id": "uuid-periode",
    "tanggal_submit": "2026-08-12T18:40:00.000000Z",
    "detail_pendaftaran": [
      {
        "id": "uuid-detail-1",
        "pendaftaran_pilihan_id": "uuid-pendaftaran",
        "paket_menu_pilihan_id": "uuid-paket-prioritas-1",
        "urutan_pilihan": 1,
        "paket_menu_pilihan": {
          "id": "uuid-paket-prioritas-1",
          "nama_menu": "Rumpun SAINS 1",
          "rumpun": "eksakta"
        }
      },
      {
        "id": "uuid-detail-2",
        "pendaftaran_pilihan_id": "uuid-pendaftaran",
        "paket_menu_pilihan_id": "uuid-paket-prioritas-2",
        "urutan_pilihan": 2,
        "paket_menu_pilihan": {
          "id": "uuid-paket-prioritas-2",
          "nama_menu": "Rumpun SOSIAL 1",
          "rumpun": "sosial"
        }
      },
      {
        "id": "uuid-detail-3",
        "pendaftaran_pilihan_id": "uuid-pendaftaran",
        "paket_menu_pilihan_id": "uuid-paket-prioritas-3",
        "urutan_pilihan": 3,
        "paket_menu_pilihan": {
          "id": "uuid-paket-prioritas-3",
          "nama_menu": "Rumpun SAINS 2",
          "rumpun": "eksakta"
        }
      }
    ]
  }
}
```

* **Endpoint Status Pilihan Siswa**: `GET /siswa/pendaftaran-pilihan`
  - Mengambil data pendaftaran pilihan siswa yang sedang login pada periode aktif saat ini.

---

### 11. Proyeksi Universitas & Program Studi (FR-39, FR-57)

Endpoint untuk mengelola data proyeksi universitas dan program studi. Fitur ini memungkinkan siswa melihat informasi universitas dan program studi, serta admin untuk mengelola data tersebut.

> [!IMPORTANT]
> - **Read access**: `auth.any` (Siswa / Admin / Guru BK)
> - **Write access**: `auth:web` dengan role `admin` saja (dicek di controller)
> - FR-39: Proyeksi Universitas
> - FR-57: Program Studi

---

#### A. Daftar Proyeksi Universitas (FR-39)

* **Endpoint**: `GET /proyeksi-universitas`
* **Access**: `auth.any`
* **Deskripsi**: Menampilkan daftar proyeksi universitas dengan fitur pencarian.

* **Query Parameters** *(opsional)*:
  * `search`: Pencarian nama universitas, singkatan, kota, atau provinsi
  * `per_page`: Jumlah data per halaman (default: 15, max: 100)
  * `is_active`: `true` / `false` / `all` (default: `true`)

* **Response Status**: `200 OK`
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-universitas",
        "nama_universitas": "Universitas Indonesia",
        "singkatan": "UI",
        "akreditasi": "A",
        "lokasi_kota": "Depok",
        "lokasi_provinsi": "Jawa Barat",
        "website": "https://ui.ac.id",
        "deskripsi": "Universitas negeri terkemuka di Indonesia",
        "tahun_data": 2024,
        "is_active": true,
        "created_at": "2026-08-19T10:00:00.000000Z",
        "updated_at": "2026-08-19T10:00:00.000000Z"
      }
    ],
    "per_page": 15,
    "total": 50
  }
}
```

---

#### B. Detail Proyeksi Universitas dengan Program Studi (FR-57)

* **Endpoint**: `GET /proyeksi-universitas/{id}`
* **Access**: `auth.any`
* **Deskripsi**: Menampilkan detail universitas beserta daftar program studi aktif.

* **Response Status**: `200 OK`
```json
{
  "success": true,
  "data": {
    "id": "uuid-universitas",
    "nama_universitas": "Universitas Indonesia",
    "singkatan": "UI",
    "akreditasi": "A",
    "lokasi_kota": "Depok",
    "lokasi_provinsi": "Jawa Barat",
    "website": "https://ui.ac.id",
    "deskripsi": "Universitas negeri terkemuka di Indonesia",
    "tahun_data": 2024,
    "is_active": true,
    "program_studis": [
      {
        "id": "uuid-prodi",
        "nama_prodi": "Teknik Informatika",
        "jenjang": "S1",
        "akreditasi_prodi": "A",
        "daya_tampung": 120,
        "peminat_tahun_lalu": 2500,
        "kelompok_saintek_soshum": "Saintek"
      }
    ]
  }
}
```

---

#### C. Tambah Proyeksi Universitas (FR-39)

* **Endpoint**: `POST /proyeksi-universitas`
* **Access**: `auth:web` (Admin only)
* **Payload Request**:
```json
{
  "nama_universitas": "Universitas Indonesia",
  "singkatan": "UI",
  "akreditasi": "A",
  "lokasi_kota": "Depok",
  "lokasi_provinsi": "Jawa Barat",
  "website": "https://ui.ac.id",
  "deskripsi": "Universitas negeri terkemuka di Indonesia",
  "tahun_data": 2024,
  "is_active": true
}
```

* **Response Status**: `201 Created`

---

#### D. Ubah & Hapus Proyeksi Universitas

* `PUT /proyeksi-universitas/{id}` (`auth:web` - Admin only)
* `DELETE /proyeksi-universitas/{id}` (`auth:web` - Admin only)
* *Catatan*: `DELETE` menggunakan **Soft Deletes** (`deleted_at`).

---

#### E. Daftar Program Studi (FR-57)

* **Endpoint**: `GET /program-studi`
* **Access**: `auth.any`
* **Deskripsi**: Menampilkan daftar program studi dengan berbagai filter.

* **Query Parameters** *(opsional)*:
  * `search`: Pencarian nama prodi atau universitas
  * `per_page`: Jumlah data per halaman (default: 15, max: 100)
  * `proyeksi_universitas_id`: Filter berdasarkan UUID universitas
  * `jenjang`: Filter jenjang (`D3`, `D4`, `S1`, `S2`, `S3`, `Profesi`)
  * `kelompok_saintek_soshum`: Filter kelompok (`Saintek`, `Soshum`, `Campuran`)
  * `is_active`: `true` / `false` / `all` (default: `true`)

* **Response Status**: `200 OK`
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "uuid-prodi",
        "proyeksi_universitas_id": "uuid-universitas",
        "nama_prodi": "Teknik Informatika",
        "jenjang": "S1",
        "akreditasi_prodi": "A",
        "daya_tampung": 120,
        "peminat_tahun_lalu": 2500,
        "kelompok_saintek_soshum": "Saintek",
        "is_active": true,
        "proyeksi_universitas": {
          "id": "uuid-universitas",
          "nama_universitas": "Universitas Indonesia",
          "singkatan": "UI"
        }
      }
    ],
    "per_page": 15,
    "total": 100
  }
}
```

---

#### F. Detail Program Studi (FR-57)

* **Endpoint**: `GET /program-studi/{id}`
* **Access**: `auth.any`

* **Response Status**: `200 OK`
```json
{
  "success": true,
  "data": {
    "id": "uuid-prodi",
    "proyeksi_universitas_id": "uuid-universitas",
    "nama_prodi": "Teknik Informatika",
    "jenjang": "S1",
    "akreditasi_prodi": "A",
    "daya_tampung": 120,
    "peminat_tahun_lalu": 2500,
    "kelompok_saintek_soshum": "Saintek",
    "is_active": true,
    "proyeksi_universitas": {
      "id": "uuid-universitas",
      "nama_universitas": "Universitas Indonesia",
      "singkatan": "UI",
      "akreditasi": "A",
      "lokasi_kota": "Depok",
      "lokasi_provinsi": "Jawa Barat"
    }
  }
}
```

---

#### G. Tambah Program Studi (FR-57)

* **Endpoint**: `POST /program-studi`
* **Access**: `auth:web` (Admin only)
* **Payload Request**:
```json
{
  "proyeksi_universitas_id": "uuid-universitas",
  "nama_prodi": "Teknik Informatika",
  "jenjang": "S1",
  "akreditasi_prodi": "A",
  "daya_tampung": 120,
  "peminat_tahun_lalu": 2500,
  "kelompok_saintek_soshum": "Saintek",
  "is_active": true
}
```

* **Response Status**: `201 Created`

---

#### H. Ubah & Hapus Program Studi

* `PUT /program-studi/{id}` (`auth:web` - Admin only)
* `DELETE /program-studi/{id}` (`auth:web` - Admin only)
* *Catatan*: `DELETE` menggunakan **Soft Deletes** (`deleted_at`).

