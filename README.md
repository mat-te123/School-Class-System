<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Hasil penjurusan

Jalankan `php artisan migrate` saat deploy. Hasil seleksi disimpan per siswa dan periode. **Proses Data** mengganti hasil periode yang dipilih saja; pendaftar belum muncul di hasil sampai seleksi periode itu dijalankan.

Buka hasil penjurusan dari **Detail Periode**. Tanpa ID periode, halaman hasil mengarahkan ke daftar periode; halaman pemantauan memakai periode aktif.

Satu tahun ajaran hanya boleh punya satu periode yang belum dihapus, apa pun gelombang atau status publikasinya. Tombol **Tambah Periode** nonaktif jika tahun ajaran berjalan sudah punya periode. Migrasi menolak duplikat yang masih ada; data tidak dihapus atau dipindahkan otomatis.

Jika periode terakhir dihapus, laporan hasil dan minat siswa tetap bisa dibuka. Keduanya menampilkan daftar kosong dan menonaktifkan unduhan sampai ada periode baru.

Menghapus periode juga mengarsipkan paket kelasnya. Bobot tetap tersimpan bersama paket arsip, tetapi tidak tampil dan tidak disalin ke periode pengganti. Periode baru dimulai tanpa kelas dan bobot.

Untuk mengisi paket kelas contoh P1–P5 pada periode aktif, jalankan `php artisan db:seed --class=PaketMenuPilihanSeeder`. Seeder ini tidak menimpa paket yang sudah ada atau mengisi bobot.

Daftar paket aktif siswa hanya menampilkan paket dari periode yang dipilih lewat kartu **Periode pendaftaran**. Jika URL tidak menyertakan ID, daftar memakai periode aktif.

Setelah hasil penjurusan dipublikasikan, termasuk melalui jadwal pengumuman, pilihan kelas yang sudah dikirim hanya bisa dilihat. Siswa tidak bisa mengirim atau memperbarui pilihan pada periode tersebut.

Admin mengatur bobot per paket melalui **Kelas → Detail Kelas → Atur Bobot**. Halaman pengaturan terbuka dengan paket tersebut terpilih. Tombol **Kembali** membuka detail paket yang sedang dipilih. Aktifkan switch mata pelajaran yang dipakai, isi bobot, lalu klik **Simpan Bobot Paket**. Total hanya menjumlahkan mapel aktif (target 100%); menonaktifkan mapel menghapus bobotnya setelah dikonfirmasi dan disimpan. Jalankan **Proses Data** untuk memperbarui skor draf.

Migrasi mengambil periode hasil lama dari paket penempatan. Jika paket tidak menunjukkan periode, migrasi memakai pendaftaran yang sudah dikirim saat seleksi hanya jika kandidatnya tepat satu; jika tidak, migrasi berhenti agar hasil tidak dipindahkan ke periode yang salah.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
