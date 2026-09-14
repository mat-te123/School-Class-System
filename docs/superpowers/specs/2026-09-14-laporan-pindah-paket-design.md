# Design: Laporan Pindah Paket Otomatis

## 1. Pendahuluan
Fitur ini memungkinkan siswa untuk mengajukan permohonan pindah paket pilihan (setelah hasil penjurusan keluar) melalui sistem Laporan Pesan. Admin atau Guru BK dapat meninjau laporan tersebut, dan apabila disetujui, sistem akan secara otomatis memperbarui data Hasil Seleksi siswa ke paket yang baru tanpa perlu modifikasi manual di menu lain.

## 2. Arsitektur Database
Kita akan memperluas fungsionalitas tabel `laporan_pesan` yang sudah ada agar dapat menyimpan data terstruktur yang diperlukan untuk proses otomatisasi.

- **Modifikasi Database**: Menambahkan kolom `payload` dengan tipe data `json` (nullable) pada tabel `laporan_pesan`.
- **Penggunaan `payload`**: Saat siswa memilih kategori "Pindah Paket", aplikasi akan menyimpan informasi spesifik seperti `{"target_paket_id": "uuid-paket-tujuan"}` ke dalam kolom `payload`.

## 3. Alur Kerja (Data Flow)

### 3.1. Pembuatan Laporan (Siswa)
1. Siswa mengakses menu pembuatan Laporan.
2. Siswa memilih kategori laporan "Pindah Paket" (atau kategori ekuivalen).
3. Muncul *dropdown* pilihan paket tujuan. Siswa memilih paket yang diinginkan.
4. Data dikirim ke `LaporanPesanController@store`.
5. Controller memvalidasi input, lalu menyimpan ID paket tujuan ke dalam kolom `payload`. Status laporan diset default ke `pending`.

### 3.2. Persetujuan Laporan (Admin/Guru BK)
1. Admin melihat daftar laporan dan membuka detail laporan pindah paket.
2. Admin mengevaluasi alasan dan ketersediaan kuota paket tujuan.
3. Admin mengubah status laporan menjadi `selesai` (Approve) melalui `LaporanPesanController@updateStatus`.
4. **Logika Otomatisasi (Intersepsi Update Status)**:
   - Jika status diubah menjadi `selesai` DAN kategori laporan adalah "Pindah Paket" (atau terdapat `target_paket_id` pada `payload`):
     - Sistem mencari record `HasilSeleksi` milik `siswa_id` pelapor.
     - Sistem mengambil `target_paket_id` dari `payload` laporan.
     - Sistem memperbarui `HasilSeleksi`:
       - `paket_menu_pilihan_id` = `target_paket_id`
       - `is_manual_override` = `true`
       - `diubah_oleh` = ID Admin yang meng-approve
       - `catatan_perubahan` = "Disetujui melalui laporan pindah paket" (atau sesuai catatan admin).
       - Menambahkan entry riwayat proses ke kolom `riwayat_proses`.

## 4. Penanganan Error & Batasan (Constraints)
- Jika laporan ditolak (`ditolak`), maka tidak ada perubahan pada `HasilSeleksi`.
- Laporan hanya dapat dibuat jika siswa sudah memiliki `HasilSeleksi` (atau form akan membatasi ini jika belum ada).
- Validasi tambahan perlu dilakukan sebelum approval otomatis, misalnya memastikan `target_paket_id` valid dan siswa yang bersangkutan benar ada dalam database Hasil Seleksi.

## 5. Rencana Pengujian
- Memastikan migrasi kolom `payload` berhasil ditambahkan tanpa merusak data lama.
- Membuat Laporan biasa tanpa payload (harus berhasil dan tidak terpengaruh).
- Membuat Laporan kategori Pindah Paket dengan target ID paket valid.
- Admin mengubah status menjadi `diproses` (Hasil seleksi siswa belum berubah).
- Admin mengubah status menjadi `selesai` (Hasil seleksi siswa berubah, field `is_manual_override` dan logger terupdate).
- Memastikan laporan yang tidak valid tidak memicu error sistem.
