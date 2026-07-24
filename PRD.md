# Product Requirement Document (PRD): Sistem Pembagian Kelas Otomatis (School-Class-System)

| Informasi Document | Detail |
| :--- | :--- |
| **Nama Proyek** | School-Class-System (Sistem Otomasi Pembagian Kelas Sekolah) |
| **Versi Document** | 1.0.0 |
| **Status** | Approved / Initial Draft |
| **Tanggal** | 24 Juli 2026 |
| **Target Pengguna** | Guru (Admin Controller) & Siswa |

---

## 1. Latar Belakang & Masalah (Problem Statement)

Saat ini, proses pembagian kelas siswa di sekolah masih dilakukan secara manual oleh pihak sekolah/guru. Proses ini melibatkan:
- Ekstraksi data nilai siswa dari file eRaport mentah satu per satu.
- Penghitungan nilai rata-rata dan pembobotan secara manual.
- Penentuan alokasi siswa ke kelas berdasarkan prioritas dan kuota kelas.

Proses manual ini memakan banyak waktu, rentan terhadap kesalahan manusia (*human error*), serta kurang transparan bagi siswa mengenai dasar pertimbangan penempatan kelas mereka. 

Aplikasi versi terdahulu baru sebatas tampilan HTML sederhana tanpa adanya otomatisasi sistem secara menyeluruh (*end-to-end*).

---

## 2. Tujuan Proyek (Project Goals)

Membangun sistem web yang berjalan **otomatis end-to-end**:
1. **Mengotomatiskan Ekstraksi Data**: Memproses dan mengekstrak nilai siswa dari file mentah eRaport.
2. **Fleksibilitas Bobot Nilai**: Memungkinkan guru mengatur mata pelajaran yang relevan, persentase bobot per kelas, serta kuota tiap kelas.
3. **Pemilihan Kelas Mandiri**: Memberikan akses kepada siswa untuk memilih hingga **3 pilihan kelas** sesuai prioritas minat mereka.
4. **Transparansi Perhitungan & Hasil**: Menampilkan rincian perhitungan skor dan hasil alokasi kelas secara transparan baik untuk guru maupun siswa.
5. **Efisiensi Waktu**: Memangkas durasi proses pembagian kelas dari hitungan hari/minggu menjadi hitungan menit.

---

## 3. Pengguna Target & Peran (User Roles)

Sistem ini memiliki 2 peran utama:

| Peran (Role) | Deskripsi & Hak Akses |
| :--- | :--- |
| **Guru (Main Controller)** | Administrator utama sistem. Berhak mengelola jadwal pemilihan, mengunggah data eRaport, mengonfigurasi mata pelajaran & bobot nilai per kelas, menentukan kuota kelas, memicu algoritma pembagian kelas otomatis, serta melihat/mengeksport laporan hasil akhir. |
| **Siswa (End User)** | Pengguna akhir aplikasi. Berhak melakukan login, melihat jadwal pemilihan kelas, memilih maksimal 3 pilihan kelas sesuai urutan prioritas, serta melihat transparansi perhitungan nilai dan hasil akhir pembagian kelas. |

---

## 4. Ruang Lingkup Sistem (System Scope)

### 4.1 In Scope (Fitur yang Masuk Dalam Pengembangan)

1. **Autentikasi & Manajemen Pengguna (Auth & User Management)**
   - Login terpisah / terpadu untuk peran **Guru** dan **Siswa**.
   - Keamanan sesi pengguna (*session-based authentication*).

2. **Ekstraksi Data Nilai eRaport (eRaport Data Extraction)**
   - Modul unggah file mentah eRaport (Format Spreadsheet Excel `.xlsx`/`.csv` atau data terstruktur).
   - *Parsing* otomatis data siswa dan nilai mata pelajaran dari eRaport ke basis data terstruktur.

3. **Konfigurasi Mata Pelajaran & Pembobotan Nilai (Subject & Weight Configuration)**
   - Guru dapat menentukan mata pelajaran apa saja yang dihitung untuk penempatan kelas.
   - Pengaturan persentase/bobot (*weight*) nilai tiap mata pelajaran per kelas (misal: Kelas IPA A fokus Bobot Matematika 40%, Fisika 30%, Kimia 30%).

4. **Pengaturan Kelas & Kuota (Class & Capacity Management)**
   - Pengaturan mata pelajaran yang tersedia untuk masing-masing kelas.
   - Pengaturan kuota (kapasitas maksimum siswa) untuk tiap-tiap kelas.

5. **Modul Pemilihan Kelas oleh Siswa (Student Class Selection)**
   - Siswa dapat memilih maksimal **3 pilihan kelas** berdasarkan urutan prioritas (Pilihan 1, Pilihan 2, Pilihan 3).
   - Penguncian pilihan otomatis setelah batas waktu jadwal berakhir atau setelah dikonfirmasi oleh siswa.

6. **Pengaturan Jadwal Proses Pemilihan (Selection Schedule Management)**
   - Guru dapat menetapkan waktu mulai (*start date*) dan waktu selesai (*end date*) periode pemilihan kelas.
   - Sistem membatasi akses input pilihan kelas siswa di luar jadwal yang ditentukan.

7. **Algoritma Otomatisasi Pembagian Kelas (Automated Class Allocation Algorithm)**
   - Perhitungan Skor Kelayakan Siswa per kelas berdasarkan pembobotan nilai yang diatur guru.
   - Penempatan otomatis berdasarkan kombinasi: **Ranking Skor Tertinggi** $\rightarrow$ **Prioritas Pilihan Siswa (Pilihan 1 > 2 > 3)** $\rightarrow$ **Ketersediaan Kuota Kelas**.

8. **Transparansi Hasil Perhitungan & Pembagian Kelas (Result & Score Transparency Dashboard)**
   - Tampilan transparan rincian nilai akhir yang digunakan dan kalkulasi bobotnya.
   - Pengumuman status penerimaan kelas siswa (Diterima di Pilihan 1/2/3).

---

### 4.2 Out of Scope (Tidak Masuk Dalam Pengembangan)

- Kustomisasi profil siswa (unggah foto profil, ubah avatar, preferensi tema tampilan).
- Fitur komunikasi/chat langsung antara siswa dan guru.
- Integrasi pembayaran sekolah atau SPP.

---

## 5. Alur Kerja Utama (Core Workflow)

```
[Guru] Update Data eRaport & Konfigurasi Kelas/Bobot/Kuota
                         │
                         ▼
[Guru] Buka Jadwal Pemilihan Kelas
                         │
                         ▼
[Siswa] Login & Memilih Maksimal 3 Pilihan Kelas (Prioritas 1, 2, 3)
                         │
                         ▼
[Siswa] Simpan Pilihan (Sebelum Deadline Jadwal Berakhir)
                         │
                         ▼
[Sistem] Eksekusi Algoritma Pembagian Kelas Otomatis:
   1. Hitung Weighted Score siswa untuk setiap kelas
   2. Ranking siswa dari skor tertinggi
   3. Alokasikan ke Pilihan 1 jika kuota tersedia
   4. Jika Pilihan 1 penuh, alokasikan ke Pilihan 2, dst.
                         │
                         ▼
[Guru & Siswa] Lihat Transparansi Rincian Skor & Hasil Pembagian Kelas
```

---

## 6. Struktur Data Utama (Entity Relationship Overview)

1. **`users`**: `id`, `username/nis_nip`, `password`, `name`, `role` (`guru` / `siswa`).
2. **`subjects`**: `id`, `code`, `name`.
3. **`classes`**: `id`, `name`, `quota`, `description`.
4. **`class_subject_weights`**: `id`, `class_id`, `subject_id`, `weight_percentage`.
5. **`student_grades`**: `id`, `student_id`, `subject_id`, `score`.
6. **`class_selections`**: `id`, `student_id`, `class_id`, `priority_order` (1, 2, 3).
7. **`selection_schedules`**: `id`, `title`, `start_time`, `end_time`, `is_active`.
8. **`class_assignments`**: `id`, `student_id`, `class_id`, `final_calculated_score`, `assigned_priority`, `status`.

---

## 7. Kebutuhan Non-Fungsional (Non-Functional Requirements)

1. **Keamanan (Security)**:
   - Password dienkripsi menggunakan algoritma `Bcrypt`.
   - Proteksi CSRF pada setiap form masukan.
   - Pengamanan akses halaman berbasis *Middleware Role* (`guru` vs `siswa`).

2. **Performa & Kecepatan (Performance)**:
   - Proses parsing eRaport dan kalkulasi pembagian kelas otomatis harus dapat mengeksekusi data ratusan siswa dalam waktu $< 5$ detik.

3. **Antarmuka & Pengalaman Pengguna (UI/UX)**:
   - Desain responsif (*Mobile & Desktop Friendly*).
   - Antarmuka transparan dan informatif dengan indikator kuota dan tenggat waktu jadwal.

---

## 8. Rencana Tahapan Eksekusi (Milestones)

1. **Tahap 1: Setup Architecture & DB Schema**
   - Pembuatan skema migrasi database Laravel & Model Eloquent.
   - Setup Auth & Role Middleware (`Guru` & `Siswa`).
2. **Tahap 2: Modul Guru (Konfigurasi & Parsing eRaport)**
   - Halaman upload & parsing file mentah eRaport.
   - Halaman manajemen kelas, kuota, mata pelajaran, dan bobot persentase.
   - Halaman pengaturan jadwal pemilihan kelas.
3. **Tahap 3: Modul Siswa (Pemilihan Kelas)**
   - Halaman dashboard siswa & indikator jadwal.
   - Form interaktif pemilihan 3 kelas prioritas.
4. **Tahap 4: Algoritma Pembagian & Dashboard Transparansi**
   - Pembuatan Service/Job kalkulasi otomatis alokasi kelas.
   - Tampilan rincian skor transparan & pengumuman hasil akhir.
5. **Tahap 5: Pengujian & Dokumentasi**
   - Testing skenario alokasi kelas & penyelesaian bug.

---

*Dokumen PRD ini disusun secara resmi berdasarkan spesifikasi pada file [prompt.text](file:///d:/project_new/School-Class-System/prompt.text) dan siap digunakan sebagai acuan pengembangan.*
