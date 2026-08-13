# 🚀 Panduan & Daftar Perintah Terminal Laravel (Artisan Commands)

Dokumen ini berisi daftar lengkap perintah terminal (**Laravel Artisan**) yang umum dan dapat digunakan pada proyek Laravel ini, baik secara langsung maupun melalui **Docker**.

---

## 🐋 Cara Eksekusi Perintah

### 1. Via Docker (Proyek ini berjalan via Docker)
Gunakan awalan `docker compose exec app`:
```bash
docker compose exec app php artisan <nama-command>
```

### 2. Via Terminal Lokal (Tanpa Docker)
```bash
php artisan <nama-command>
```

---

## 🛠️ 1. Generator Commands (`make:*`)
Perintah untuk membuat class, model, controller, migrasi, dan komponen Laravel baru.

| Perintah | Deskripsi |
| :--- | :--- |
| `make:controller <Name>` | Membuat controller baru. Contoh: `make:controller SiswaController` |
| `make:model <Name>` | Membuat Eloquent model baru. |
| `make:model <Name> -mcr` | Membuat Model beserta Migration (`-m`), Controller (`-c`), dan Resource Controller (`-r`). |
| `make:migration <name>` | Membuat file migrasi database baru. |
| `make:seeder <Name>` | Membuat database seeder baru. |
| `make:factory <Name>` | Membuat Model Factory baru untuk testing/dummy data. |
| `make:middleware <Name>` | Membuat HTTP Middleware baru. |
| `make:request <Name>` | Membuat Form Request validation class baru. |
| `make:resource <Name>` | Membuat API Resource class baru (transformasi JSON). |
| `make:command <Name>` | Membuat custom Artisan command baru. |
| `make:event <Name>` | Membuat Event class baru. |
| `make:listener <Name>` | Membuat Event Listener class baru. |
| `make:job <Name>` | Membuat Queue Job class baru. |
| `make:mail <Name>` | Membuat Mailable email class baru. |
| `make:notification <Name>` | Membuat Notification class baru. |
| `make:policy <Name>` | Membuat Policy class baru (akses otorisasi model). |
| `make:observer <Name>` | Membuat Observer class baru untuk event Eloquent model. |
| `make:provider <Name>` | Membuat Service Provider baru. |
| `make:test <Name>` | Membuat Unit/Feature Test class baru. |
| `make:enum <Name>` | Membuat PHP Enum baru (Laravel 11/12). |
| `make:class <Name>` | Membuat generic PHP Class baru. |
| `make:interface <Name>` | Membuat PHP Interface baru. |
| `make:trait <Name>` | Membuat PHP Trait baru. |
| `make:view <Name>` | Membuat file Blade View baru. |

---

## 🗄️ 2. Database & Migrasi (`migrate:*` & `db:*`)

| Perintah | Deskripsi |
| :--- | :--- |
| `migrate` | Menjalankan seluruh file migrasi yang belum dieksekusi. |
| `migrate:fresh` | Menghapus semua tabel lalu menjalankan ulang seluruh migrasi dari awal. |
| `migrate:fresh --seed` | Menghapus tabel, jalankan migrasi, dan langsung eksekusi seeder. |
| `migrate:rollback` | Mengembalikan (undo) batch migrasi terakhir. |
| `migrate:reset` | Mengembalikan seluruh migrasi yang pernah dijalankan. |
| `migrate:refresh` | Mengubah ulang seluruh migrasi (reset + migrate). |
| `migrate:status` | Menampilkan status setiap file migrasi (Ran / Pending). |
| `db:seed` | Menjalankan database seeder (`DatabaseSeeder`). |
| `db:seed --class=<SeederName>` | Menjalankan seeder tertentu (contoh: `--class=UserSeeder`). |
| `db:wipe` | Menghapus seluruh tabel dan tipe dari database. |
| `db:show` | Menampilkan ringkasan informasi database. |
| `db:table <table_name>` | Menampilkan detail informasi & kolom dari tabel database tertentu. |
| `db:monitor` | Memantau jumlah koneksi database aktif. |

---

## 🛣️ 3. Routing (`route:*`)

| Perintah | Deskripsi |
| :--- | :--- |
| `route:list` | Menampilkan daftar seluruh URL route yang terdaftar. |
| `route:cache` | Membuat file cache routing agar performa aplikasi lebih cepat. |
| `route:clear` | Menghapus file cache routing. |

---

## ⚡ 4. Caching, Optimasi, & Perawatan (`optimize:*`, `cache:*`, `config:*`, `view:*`)

| Perintah | Deskripsi |
| :--- | :--- |
| `optimize` | Membuat cache untuk konfigurasi dan routing (mode produksi). |
| `optimize:clear` | Menghapus **semua** cache (config, route, view, event, bootstrap). |
| `config:cache` | Membuat file cache untuk file konfigurasi `.env`. |
| `config:clear` | Menghapus cache file konfigurasi. |
| `config:show <key>` | Menampilkan nilai konfigurasi tertentu. |
| `cache:clear` | Membersihkan cache aplikasi (Redis/File/Database). |
| `cache:forget <key>` | Menghapus 1 item tertentu dari cache. |
| `view:cache` | Kompilasi seluruh file template Blade ke cache. |
| `view:clear` | Menghapus cache file kompilasi Blade. |

---

## 🔄 5. Queue & Schedule Worker (`queue:*` & `schedule:*`)

| Perintah | Deskripsi |
| :--- | :--- |
| `queue:work` | Menjalankan worker untuk memproses antrean job di background. |
| `queue:listen` | Mendengarkan antrean job (otomatis reload jika kode berubah, bagus untuk dev). |
| `queue:failed` | Menampilkan daftar job antrean yang gagal dieksekusi. |
| `queue:retry <id>` | Menjalankan ulang job gagal berdasarkan ID (`all` untuk semua). |
| `queue:clear` | Menghapus seluruh job dari antrean. |
| `schedule:run` | Menjalankan task scheduler yang dijadwalkan. |
| `schedule:work` | Menjalankan scheduler daemon (loop per menit untuk dev). |
| `schedule:list` | Menampilkan daftar seluruh task yang terdaftar di scheduler. |

---

## 🔑 6. Autentikasi & Kunci Aplikasi

| Perintah | Deskripsi |
| :--- | :--- |
| `key:generate` | Membuat dan memasukkan `APP_KEY` baru ke file `.env`. |
| `env:encrypt` | Mengenkripsi file environment `.env`. |
| `env:decrypt` | Mendekripsi file environment yang terenkripsi. |
| `storage:link` | Membuat symbolic link dari `public/storage` ke `storage/app/public`. |
| `install:api` | Menginstal setup API routes dan paket autentikasi API. |

---

## 💻 7. Perintah Umum & Utility

| Perintah | Deskripsi |
| :--- | :--- |
| `tinker` | Membuka REPL / Terminal interaktif PHP untuk mengeksekusi kode Laravel secara langsung. |
| `test` | Menjalankan unit test & feature test (PHPUnit / Pest). |
| `pail` | Menampilkan log aplikasi secara real-time (Log Tail). |
| `down` | Mengubah status aplikasi ke Mode Maintenance. |
| `up` | Mengaktifkan kembali aplikasi dari Mode Maintenance. |
| `about` | Menampilkan informasi lengkap mengenai environment dan versi aplikasi Laravel. |

---

### 💡 Contoh Perintah yang Sering Digunakan di Proyek Ini (Via Docker):

```bash
# 1. Build & Jalankan Docker Container
docker compose up -d --build

# 2. Jalankan Migrasi + Seeder
docker compose exec app php artisan migrate:fresh --seed

# 3. Jalankan Queue Worker (Memproses Background Job / Import Leger)
docker compose exec app php artisan queue:work

# 4. Buat Controller Baru
docker compose exec app php artisan make:controller Api/SiswaController

# 5. Lihat Semua Route
docker compose exec app php artisan route:list

# 6. Bersihkan Seluruh Cache
docker compose exec app php artisan optimize:clear

# 7. Masuk ke Terminal Interaktif Tinker
docker compose exec app php artisan tinker
```

---

## 🐳 Panduan Build & Menjalankan Laravel via Docker

| Perintah | Deskripsi |
| :--- | :--- |
| `docker compose build` | Membangun (build) ulang image Docker dari `Dockerfile`. |
| `docker compose up -d` | Menjalankan seluruh container di background (detached mode). |
| `docker compose up -d --build` | Membangun ulang image dan langsung menjalankan container. |
| `docker compose down` | Menghentikan dan menghapus container yang sedang berjalan. |
| `docker compose down -v` | Menghentikan container sekaligus menghapus volume database/storage. |
| `docker compose ps` | Menampilkan status seluruh container Docker yang aktif. |
| `docker compose logs -f app` | Menampilkan log real-time dari container aplikasi Laravel. |

---

## ⚙️ Mengaktifkan & Menjalankan Queue Worker (Background Job)

Aplikasi ini menggunakan **Laravel Queue Job** untuk pemrosesan latar belakang (seperti `ProcessLegerImportJob` pada impor file Leger XLSX).

> **Note**: Pada environment Docker, service `queue` sudah dikonfigurasi pada `docker-compose.yml` untuk otomatis berjalan secara terus-menerus (`php artisan queue:work`) bersamaan dengan service utama aplikasi (`app`).

### 1. Memantau Log Queue Worker di Docker
```bash
# Melihat log aktivitas queue worker secara real-time
docker compose logs -f queue

# Restart service queue worker jika ada perubahan pada logika Job
docker compose restart queue
```

### 2. Menjalankan Manual Via Terminal (Jika Diperlukan)
```bash
# Memproses antrean job secara manual di container app
docker compose exec app php artisan queue:work

# Memantau job yang gagal
docker compose exec app php artisan queue:failed

# Menjalankan ulang job yang gagal
docker compose exec app php artisan queue:retry all
```
# 1. Hapus semua tabel
docker compose exec app php artisan db:wipe

# 2. Jalankan migration
docker compose exec app php artisan migrate

# 3. Jalankan seeder
docker compose exec app php artisan db:seed
