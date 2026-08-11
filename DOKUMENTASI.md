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
   - [9. Periode Penjurusan](#9-periode-penjurusan)

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

#### D. Ubah Periode dan Jadwal Pengisian Minat
* **Endpoint**: `PUT /periode-penjurusan/{id}`
* **Access**: `auth:web` (Admin)
* Field wajib saat membuat periode: `nama_periode`, `tahun_ajaran`, `tanggal_buka`, `tanggal_tutup`.
* `tanggal_buka` adalah tanggal mulai pengisian minat; `tanggal_tutup` adalah tanggal berakhirnya.
* `tanggal_tutup` wajib setelah `tanggal_buka`.
* Field opsional: `gelombang`, `max_pilihan_siswa`, `status_pengumuman` (`AKTIF`/`NON-AKTIF`), `is_active`.
* Default pembuatan: `gelombang: "Utama"`, `max_pilihan_siswa: 3`, `status_pengumuman: "NON-AKTIF"`, `is_active: true`.

* **Payload Request**:
```json
{
  "nama_periode": "Penjurusan 2026/2027",
  "tahun_ajaran": "2026/2027",
  "tanggal_buka": "2026-08-11 08:00:00",
  "tanggal_tutup": "2026-08-20 23:59:59"
}
```
