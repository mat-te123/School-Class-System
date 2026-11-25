<?php

use App\Http\Controllers\AdminLaporanController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\HasilPenjurusanController;
use App\Http\Controllers\KelasAsalController;
use App\Http\Controllers\KriteriaBobotMenuController;
use App\Http\Controllers\LaporanPesanController;
use App\Http\Controllers\LegerImportController;
use App\Http\Controllers\MasterMataPelajaranController;
use App\Http\Controllers\NilaiSiswaController;
use App\Http\Controllers\PaketMenuPilihanController;
use App\Http\Controllers\PendaftaranPilihanController;
use App\Http\Controllers\PeriodePenjurusanController;
use App\Http\Controllers\PertukaranController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\ProyeksiUniversitasController;
use App\Http\Controllers\SiswaAuthController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\UserAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Autentikasi
|--------------------------------------------------------------------------
*/

// Public Server Clock / Time (Sinkronisasi Waktu Client)
Route::get('/server-clock', fn () => Controller::serverClock())->name('server.clock');
Route::get('/server-time', fn () => Controller::serverClock())->name('server.time');

// Autentikasi User (Admin / Guru BK)
Route::controller(UserAuthController::class)->group(function () {
    Route::get('/login/admin', 'showLoginForm')->name('login');
    Route::post('/login/admin', 'login');
    Route::post('/logout', 'logout')->name('logout');
});

// Autentikasi & Registrasi Siswa
Route::controller(SiswaAuthController::class)->group(function () {
    Route::get('/register/siswa', 'showRegisterForm')->name('siswa.register.form');
    Route::post('/register/siswa/check', 'checkNisn')->name('siswa.register.check');
    Route::post('/register/siswa', 'register')->name('siswa.register');

    Route::get('/', 'showLoginForm')->name('siswa.login.form');
    Route::post('/', 'login')->name('siswa.login');
    Route::redirect('/login', '/');
    Route::post('/login', 'login');

    Route::post('/logout/siswa', 'logout')->name('siswa.logout');
});

// Laporan Pesan (Publik / Guest / Siswa / Admin)
Route::post('/laporan-pesan', [LaporanPesanController::class, 'store'])->name('laporan-pesan.store');

// Halaman Admin dengan Redirect Berbasis Guard (siswa -> portal siswa, guest -> login)
Route::get('/admin/periode-penjurusan', [PeriodePenjurusanController::class, 'adminIndex'])->name('admin-periode-penjurusan.index');
Route::get('/admin/siswa', [SiswaController::class, 'adminIndex'])->name('admin-siswa.index');

/*
|--------------------------------------------------------------------------
| Portal Siswa (auth:siswa)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:siswa'])->prefix('siswa')->group(function () {
    // Dashboard & Profil Siswa
    Route::controller(SiswaController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('siswa.dashboard');
        Route::get('/profile', 'profile')->name('siswa.profile');
    });

    // Nilai Siswa
    Route::get('/nilai', [NilaiSiswaController::class, 'siswaIndex'])->name('siswa.nilai.index');

    // Paket Menu Aktif & Periode Pendaftaran
    Route::controller(PaketMenuPilihanController::class)->group(function () {
        Route::get('/paket-menu-aktif', 'siswaIndex')->name('siswa.paket-menu-aktif.index');
        Route::get('/paket-menu-aktif/{identifier}', 'siswaShow')->name('siswa.paket-menu-aktif.show');
    });
    Route::get('/periode', [PeriodePenjurusanController::class, 'siswaIndex'])->name('siswa.periode.index');

    // Pendaftaran Pilihan Kelas Siswa (FR-52, FR-53, FR-58, FR-59)
    Route::controller(PendaftaranPilihanController::class)->group(function () {
        Route::get('/pendaftaran-pilihan', 'siswaIndex')->name('siswa.pendaftaran-pilihan.index');
        Route::get('/pilih-kelas', 'siswaIndex')->name('siswa.pilih-kelas.index');
        Route::post('/pilih-kelas', 'store')->name('siswa.pilih-kelas.store');
        Route::put('/pilih-kelas', 'update')->name('siswa.pilih-kelas.update');
        Route::delete('/pilih-kelas', 'destroy')->name('siswa.pilih-kelas.cancel');
        Route::put('/pendaftaran-pilihan/dokumen', 'uploadDokumen')->name('siswa.pendaftaran-pilihan.dokumen');

        Route::get('/periode/{id}', 'siswaShow')->name('siswa.periode.show');
        Route::post('/periode/{id}/pilih-kelas', 'store')->name('siswa.periode.store');
        Route::put('/periode/{id}/pilih-kelas', 'update')->name('siswa.periode.update');
    });

    // Hasil Penempatan & Live Ranking (FR-54 s/d FR-56)
    Route::controller(HasilPenjurusanController::class)->group(function () {
        Route::get('/hasil-penempatan', 'siswaIndex')->name('siswa.hasil-penempatan');
        Route::get('/live-ranking', 'liveRanking')->name('siswa.live-ranking');
        Route::get('/hasil-sementara', 'hasilSementara')->name('siswa.hasil-sementara');
    });

    // Pertukaran Kelas Siswa
    Route::controller(PertukaranController::class)->group(function () {
        Route::get('/pertukaran', 'siswaIndex')->name('siswa.pertukaran.index');
        Route::post('/pertukaran', 'store')->name('siswa.pertukaran.store');
        Route::delete('/pertukaran/{id}', 'destroy')->name('siswa.pertukaran.cancel');
    });

    // Laporan Pesan Siswa
    Route::controller(LaporanPesanController::class)->group(function () {
        Route::get('/laporan-pesan', 'siswaIndex')->name('laporan-pesan.siswa.index');
        Route::get('/laporan-pesan/{id}', 'show')->name('laporan-pesan.siswa.show');
    });
});

/*
|--------------------------------------------------------------------------
| Read-Only Bersama: Siswa & Admin / Guru BK (auth.any)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth.any'])->group(function () {
    Route::controller(PaketMenuPilihanController::class)->group(function () {
        Route::get('/paket-menu-pilihan', 'index')->name('paket-menu.index');
        Route::get('/paket-menu-pilihan/{identifier}', 'show')->name('paket-menu.show');
    });

    Route::controller(KelasAsalController::class)->group(function () {
        Route::get('/kelas-asal', 'index')->name('kelas-asal.index');
        Route::get('/kelas-asal/{identifier}', 'show')->name('kelas-asal.show');
    });

    Route::controller(MasterMataPelajaranController::class)->group(function () {
        Route::get('/master-mata-pelajaran', 'index')->name('master-mapel.index');
        Route::get('/master-mata-pelajaran/{id}', 'show')->name('master-mapel.show');
    });

    Route::get('/nilai-siswa', [NilaiSiswaController::class, 'index'])->name('nilai-siswa.index');

    Route::controller(LegerImportController::class)->group(function () {
        Route::get('/leger/history', 'index')->name('leger.history');
        Route::get('/leger/download/{filename}', 'download')->name('leger.download');
    });

    Route::controller(PeriodePenjurusanController::class)->group(function () {
        Route::get('/periode-penjurusan', 'index')->name('periode-penjurusan.index');
        Route::get('/periode-penjurusan/{id}', 'show')->name('periode-penjurusan.show');
    });

    Route::controller(ProyeksiUniversitasController::class)->group(function () {
        Route::get('/proyeksi-universitas', 'index')->name('proyeksi-universitas.index');
        Route::get('/proyeksi-universitas/{id}', 'show')->name('proyeksi-universitas.show');
    });

    Route::controller(ProgramStudiController::class)->group(function () {
        Route::get('/program-studi', 'index')->name('program-studi.index');
        Route::get('/program-studi/{id}', 'show')->name('program-studi.show');
    });
});

/*
|--------------------------------------------------------------------------
| Admin & Guru BK (auth:web)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web'])->group(function () {
    Route::get('/me', [UserAuthController::class, 'me'])->name('user.me');

    // Leger & Nilai Siswa (FR-13, FR-14)
    Route::post('/leger/import', [LegerImportController::class, 'store'])->name('leger.import');
    Route::controller(NilaiSiswaController::class)->group(function () {
        Route::get('/nilai-siswa/{siswa_id}', 'show')->name('nilai-siswa.show');
        Route::post('/nilai-siswa/import-mapel', 'import')->name('nilai-siswa.import-mapel');
        Route::put('/nilai-siswa/{id}', 'update')->name('nilai-siswa.update');
    });

    // Periode Penjurusan (Write)
    Route::controller(PeriodePenjurusanController::class)->group(function () {
        Route::get('/admin/periode-penjurusan/{id}', 'show');
        Route::post('/periode-penjurusan', 'store')->name('periode-penjurusan.store');
        Route::put('/periode-penjurusan/{id}', 'update')->name('periode-penjurusan.update');
        Route::delete('/periode-penjurusan/{id}', 'destroy')->name('periode-penjurusan.destroy');
    });

    // Kelas Asal (Write)
    Route::controller(KelasAsalController::class)->group(function () {
        Route::post('/kelas-asal', 'store')->name('kelas-asal.store');
        Route::put('/kelas-asal/{id}', 'update')->name('kelas-asal.update');
        Route::delete('/kelas-asal/{id}', 'destroy')->name('kelas-asal.destroy');
    });

    // Paket Menu Pilihan (Admin Index & Write)
    Route::controller(PaketMenuPilihanController::class)->group(function () {
        Route::get('/admin/paket-menu-pilihan', 'index')->name('admin-paket-menu.index');
        Route::post('/paket-menu-pilihan', 'store')->name('paket-menu.store');
        Route::put('/paket-menu-pilihan/{identifier}', 'update')->name('paket-menu.update');
        Route::delete('/paket-menu-pilihan/{identifier}', 'destroy')->name('paket-menu.destroy');
    });

    // Kriteria Bobot Menu
    Route::controller(KriteriaBobotMenuController::class)->group(function () {
        Route::get('/admin/periode-penjurusan/{periode}/bobot-mata-pelajaran', 'index')->name('kriteria-bobot.index');
        Route::put('/admin/periode-penjurusan/{periode}/bobot-mata-pelajaran/{paket}', 'savePackage')->whereUuid('paket')->name('kriteria-bobot.package.save');
        Route::post('/kriteria-bobot-menu', 'store')->name('kriteria-bobot.store');
        Route::put('/kriteria-bobot-menu/{id}', 'update')->name('kriteria-bobot.update');
        Route::delete('/kriteria-bobot-menu/{id}', 'destroy')->name('kriteria-bobot.destroy');
    });

    // Master Mata Pelajaran (Write)
    Route::controller(MasterMataPelajaranController::class)->group(function () {
        Route::post('/master-mata-pelajaran', 'store')->name('master-mapel.store');
        Route::put('/master-mata-pelajaran/{id}', 'update')->name('master-mapel.update');
        Route::delete('/master-mata-pelajaran/{id}', 'destroy')->name('master-mapel.destroy');
    });

    // Siswa CRUD
    Route::controller(SiswaController::class)->group(function () {
        Route::get('/siswa', 'index')->name('siswa.index');
        Route::get('/siswa/{id}', 'show')->name('siswa.show');
        Route::post('/siswa', 'store')->name('siswa.store');
        Route::put('/siswa/{id}', 'update')->name('siswa.update');
        Route::delete('/siswa/{id}', 'destroy')->name('siswa.destroy');
    });

    // Review Pengajuan Pilihan Siswa (FR-19, FR-40 s/d FR-44)
    Route::controller(PendaftaranPilihanController::class)->prefix('admin')->group(function () {
        Route::get('/pendaftaran-pilihan', 'index')->name('admin-pendaftaran.index');
        Route::get('/pendaftaran-pilihan/{id}', 'show')->name('admin-pendaftaran.show');
        Route::get('/pendaftaran-pilihan/{id}/dokumen', 'downloadDokumen')->name('admin-pendaftaran.dokumen');
        Route::get('/siswa/status-pilihan', 'statusPilihan')->name('admin.siswa.status-pilihan');
        Route::get('/siswa/{siswaId}/pilihan', 'prioritasSiswa')->name('admin.siswa.pilihan');
    });

    // Manajemen Hasil Penjurusan
    Route::controller(HasilPenjurusanController::class)->prefix('admin/hasil-penjurusan')->group(function () {
        Route::post('/process', 'process')->name('admin.hasil-penjurusan.process');
        Route::get('/', 'index')->name('admin.hasil-penjurusan.index');
        Route::get('/rekap-kuota', 'rekapKuota')->name('admin.hasil-penjurusan.rekap-kuota');
        Route::get('/siswa/{siswaId}', 'show')->name('admin.hasil-penjurusan.show-siswa');
        Route::put('/{hasilId}/override', 'update')->name('admin.hasil-penjurusan.override');
        Route::post('/lock', 'lock')->name('admin.hasil-penjurusan.lock');
        Route::post('/unlock', 'unlock')->name('admin.hasil-penjurusan.unlock');
        Route::post('/publish', 'publish')->name('admin.hasil-penjurusan.publish');
        Route::post('/unpublish', 'unpublish')->name('admin.hasil-penjurusan.unpublish');
    });

    // Manajemen Pertukaran
    Route::controller(PertukaranController::class)->prefix('admin/pertukaran')->group(function () {
        Route::get('/', 'index')->name('admin.pertukaran.index');
        Route::get('/{id}', 'show')->name('admin.pertukaran.show');
        Route::get('/{id}/dokumen', 'downloadDokumen')->name('admin.pertukaran.dokumen');
        Route::put('/{id}/approve', 'approve')->name('admin.pertukaran.approve');
        Route::put('/{id}/reject', 'reject')->name('admin.pertukaran.reject');
    });

    // Laporan & Ekspor
    Route::controller(AdminLaporanController::class)->prefix('admin')->group(function () {
        Route::get('/pemantauan-minat', 'pemantauanMinat')->name('admin.pemantauan-minat.index');
        Route::get('/laporan/hasil-penjurusan', 'hasilPenjurusan')->name('admin.laporan.hasil-penjurusan');
        Route::get('/laporan/minat-siswa', 'minatSiswa')->name('admin.laporan.minat-siswa');
        Route::get('/laporan/peminat-vs-kuota', 'peminatVsKuota')->name('admin.laporan.peminat-vs-kuota');
        Route::get('/laporan/export/hasil-penjurusan', 'exportHasilPenjurusan')->name('admin.laporan.export.hasil-penjurusan');
        Route::get('/laporan/export/minat-siswa', 'exportMinatSiswa')->name('admin.laporan.export.minat-siswa');
    });

    // Laporan Pesan (Admin)
    Route::controller(LaporanPesanController::class)->prefix('laporan-pesan')->group(function () {
        Route::get('/', 'index')->name('laporan-pesan.index');
        Route::get('/{id}', 'show')->name('laporan-pesan.show');
        Route::put('/{id}/status', 'update')->name('laporan-pesan.update-status');
        Route::delete('/{id}', 'destroy')->name('laporan-pesan.destroy');
    });

    // Proyeksi Universitas (Write)
    Route::controller(ProyeksiUniversitasController::class)->group(function () {
        Route::post('/proyeksi-universitas', 'store')->name('proyeksi-universitas.store');
        Route::put('/proyeksi-universitas/{id}', 'update')->name('proyeksi-universitas.update');
        Route::delete('/proyeksi-universitas/{id}', 'destroy')->name('proyeksi-universitas.destroy');
    });

    // Program Studi (Write)
    Route::controller(ProgramStudiController::class)->group(function () {
        Route::post('/program-studi', 'store')->name('program-studi.store');
        Route::put('/program-studi/{id}', 'update')->name('program-studi.update');
        Route::delete('/program-studi/{id}', 'destroy')->name('program-studi.destroy');
    });
});
