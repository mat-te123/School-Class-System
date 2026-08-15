<?php

use App\Http\Controllers\KelasAsalController;
use App\Http\Controllers\KriteriaBobotMenuController;
use App\Http\Controllers\LaporanPesanController;
use App\Http\Controllers\LegerImportController;
use App\Http\Controllers\MasterMataPelajaranController;
use App\Http\Controllers\NilaiSiswaController;
use App\Http\Controllers\PaketMenuPilihanController;
use App\Http\Controllers\PendaftaranPilihanController;
use App\Http\Controllers\PeriodePenjurusanController;
use App\Http\Controllers\SiswaAuthController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\UserAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route Autentikasi User (Admin / Guru BK)
Route::get('/login', [UserAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserAuthController::class, 'login']);
Route::post('/logout', [UserAuthController::class, 'logout'])->name('logout');

Route::middleware(['auth:web'])->group(function () {
    Route::get('/me', [UserAuthController::class, 'me'])->name('user.me');
});

// Route Registrasi Siswa (2 Tahap: Check NISN & Melengkapi Data)
Route::get('/register/siswa', [SiswaAuthController::class, 'showRegisterForm'])->name('siswa.register.form');
Route::post('/register/siswa/check', [SiswaAuthController::class, 'checkNisn'])->name('siswa.register.check');
Route::post('/register/siswa', [SiswaAuthController::class, 'register'])->name('siswa.register');

// Route Login Siswa via NISN
Route::get('/login/siswa', [SiswaAuthController::class, 'showLoginForm'])->name('siswa.login.form');
Route::post('/login/siswa', [SiswaAuthController::class, 'login'])->name('siswa.login');
Route::post('/logout/siswa', [SiswaAuthController::class, 'logout'])->name('siswa.logout');

// Route Profil Siswa (butuh login)
Route::middleware(['auth:siswa'])->group(function () {
    Route::get('/siswa/profile', [SiswaAuthController::class, 'profile'])->name('siswa.profile');
    Route::get('/siswa/nilai', [NilaiSiswaController::class, 'indexSiswa'])->name('siswa.nilai.index');
    Route::get('/siswa/paket-menu-aktif', [PaketMenuPilihanController::class, 'indexSiswa'])->name('siswa.paket-menu-aktif.index');
    Route::get('/siswa/paket-menu-aktif/{identifier}', [PaketMenuPilihanController::class, 'showSiswa'])->name('siswa.paket-menu-aktif.show');

    // FR-52: Pendaftaran pilihan paket prioritas siswa
    Route::get('/siswa/pendaftaran-pilihan', [PendaftaranPilihanController::class, 'indexSiswa'])->name('siswa.pendaftaran-pilihan.index');
    Route::post('/siswa/pendaftaran-pilihan', [PendaftaranPilihanController::class, 'storeSiswa'])->name('siswa.pendaftaran-pilihan.store');

    // Dashboard siswa (render Blade view)
    Route::get('/siswa/dashboard', function () {
        return view('siswa.dashboard');
    })->name('siswa.dashboard');
});

// Route yang wajib login (bisa dari Siswa maupun Admin / Guru BK)
Route::middleware(['auth.any'])->group(function () {
    // Route Paket Menu Pilihan
    Route::get('/paket-menu-pilihan', [PaketMenuPilihanController::class, 'index'])->name('paket-menu.index');
    Route::get('/paket-menu-pilihan/{identifier}', [PaketMenuPilihanController::class, 'show'])->name('paket-menu.show');

    // Route Kelas Asal (khusus Kelas X)
    Route::get('/kelas-asal', [KelasAsalController::class, 'index'])->name('kelas-asal.index');
    Route::get('/kelas-asal/{identifier}', [KelasAsalController::class, 'show'])->name('kelas-asal.show');

    // Route Kriteria Bobot Menu
    Route::get('/kriteria-bobot-menu', [KriteriaBobotMenuController::class, 'index'])->name('kriteria-bobot.index');

    // Route Siswa (Read Only untuk admin & mungkin siswa? Oh wait, Siswa is admin only for CRUD)
    // We will place GET /siswa in auth:web below instead.

    // Route Master Mata Pelajaran (Read Only untuk Siswa & Admin)
    Route::get('/master-mata-pelajaran', [MasterMataPelajaranController::class, 'index'])->name('master-mapel.index');
    Route::get('/master-mata-pelajaran/{id}', [MasterMataPelajaranController::class, 'show'])->name('master-mapel.show');

    // Route Nilai Siswa (FR-14: lihat data nilai)
    Route::get('/nilai-siswa', [NilaiSiswaController::class, 'index'])->name('nilai-siswa.index');

    // Route Tracking Riwayat & Download File Leger
    Route::get('/leger/history', [LegerImportController::class, 'history'])->name('leger.history');
    Route::get('/leger/download/{filename}', [LegerImportController::class, 'download'])->name('leger.download');

    Route::get('/periode-penjurusan', [PeriodePenjurusanController::class, 'index'])->name('periode-penjurusan.index');
    Route::get('/periode-penjurusan/{id}', [PeriodePenjurusanController::class, 'show'])->name('periode-penjurusan.show');
});

Route::middleware(['auth:web'])->group(function () {
    // Leger Import - hanya admin (dicek di controller)
    Route::post('/leger/import', [LegerImportController::class, 'import'])->name('leger.import');

    // Nilai Siswa - FR-13 impor nilai per mapel, FR-14 perbaiki nilai (admin only, dicek di controller)
    Route::post('/nilai-siswa/import-mapel', [NilaiSiswaController::class, 'importMapel'])->name('nilai-siswa.import-mapel');
    Route::put('/nilai-siswa/{id}', [NilaiSiswaController::class, 'update'])->name('nilai-siswa.update');

    Route::post('/periode-penjurusan', [PeriodePenjurusanController::class, 'store'])->name('periode-penjurusan.store');
    Route::put('/periode-penjurusan/{id}', [PeriodePenjurusanController::class, 'update'])->name('periode-penjurusan.update');

    Route::post('/kelas-asal', [KelasAsalController::class, 'store'])->name('kelas-asal.store');
    Route::put('/kelas-asal/{id}', [KelasAsalController::class, 'update'])->name('kelas-asal.update');
    Route::delete('/kelas-asal/{id}', [KelasAsalController::class, 'destroy'])->name('kelas-asal.destroy');

    Route::post('/paket-menu-pilihan', [PaketMenuPilihanController::class, 'store'])->name('paket-menu.store');
    Route::put('/paket-menu-pilihan/{identifier}', [PaketMenuPilihanController::class, 'update'])->name('paket-menu.update');
    Route::delete('/paket-menu-pilihan/{identifier}', [PaketMenuPilihanController::class, 'destroy'])->name('paket-menu.destroy');

    Route::post('/kriteria-bobot-menu', [KriteriaBobotMenuController::class, 'store'])->name('kriteria-bobot.store');
    Route::put('/kriteria-bobot-menu/{id}', [KriteriaBobotMenuController::class, 'update'])->name('kriteria-bobot.update');
    Route::delete('/kriteria-bobot-menu/{id}', [KriteriaBobotMenuController::class, 'destroy'])->name('kriteria-bobot.destroy');

    // Master Mata Pelajaran (Write Operations - khusus Admin/Guru BK)
    Route::post('/master-mata-pelajaran', [MasterMataPelajaranController::class, 'store'])->name('master-mapel.store');
    Route::put('/master-mata-pelajaran/{id}', [MasterMataPelajaranController::class, 'update'])->name('master-mapel.update');
    Route::delete('/master-mata-pelajaran/{id}', [MasterMataPelajaranController::class, 'destroy'])->name('master-mapel.destroy');

    // Siswa CRUD (Khusus Admin)
    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/{id}', [SiswaController::class, 'show'])->name('siswa.show');
    Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
    Route::put('/siswa/{id}', [SiswaController::class, 'update'])->name('siswa.update');
    Route::delete('/siswa/{id}', [SiswaController::class, 'destroy'])->name('siswa.destroy');
});

// ===== Route Laporan Pesan (Report) =====
// Publik / Guest: kirim laporan (bisa juga dipakai siswa & admin yang login)
Route::post('/laporan-pesan', [LaporanPesanController::class, 'store'])->name('laporan-pesan.store');

// Siswa (login): lihat laporan milik sendiri
Route::middleware(['auth:siswa'])->group(function () {
    Route::get('/siswa/laporan-pesan', [LaporanPesanController::class, 'indexSiswa'])->name('laporan-pesan.siswa.index');
    Route::get('/siswa/laporan-pesan/{id}', [LaporanPesanController::class, 'show'])->name('laporan-pesan.siswa.show');
});

// Admin / Guru BK (login): kelola seluruh laporan
Route::middleware(['auth:web'])->group(function () {
    Route::get('/laporan-pesan', [LaporanPesanController::class, 'index'])->name('laporan-pesan.index');
    Route::get('/laporan-pesan/{id}', [LaporanPesanController::class, 'show'])->name('laporan-pesan.show');
    Route::put('/laporan-pesan/{id}/status', [LaporanPesanController::class, 'updateStatus'])->name('laporan-pesan.update-status');
    Route::delete('/laporan-pesan/{id}', [LaporanPesanController::class, 'destroy'])->name('laporan-pesan.destroy');
});

