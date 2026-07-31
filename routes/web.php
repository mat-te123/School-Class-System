<?php

use App\Http\Controllers\KelasAsalController;
use App\Http\Controllers\KriteriaBobotMenuController;
use App\Http\Controllers\LegerImportController;
use App\Http\Controllers\PaketMenuPilihanController;
use App\Http\Controllers\SiswaAuthController;
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
});

// Route Import Leger XLSX & Tracking Riwayat
Route::get('/leger/history', [LegerImportController::class, 'history'])->name('leger.history');

// Route Paket Menu Pilihan
Route::get('/paket-menu-pilihan', [PaketMenuPilihanController::class, 'index'])->name('paket-menu.index');
Route::get('/paket-menu-pilihan/{identifier}', [PaketMenuPilihanController::class, 'show'])->name('paket-menu.show');

// Route Kelas Asal (khusus Kelas X)
Route::get('/kelas-asal', [KelasAsalController::class, 'index'])->name('kelas-asal.index');
Route::get('/kelas-asal/{identifier}', [KelasAsalController::class, 'show'])->name('kelas-asal.show');

// Route Kriteria Bobot Menu
Route::get('/kriteria-bobot-menu', [KriteriaBobotMenuController::class, 'index'])->name('kriteria-bobot.index');

Route::middleware(['auth:web'])->group(function () {
    // Leger Import - hanya admin (dicek di controller)
    Route::post('/leger/import', [LegerImportController::class, 'import'])->name('leger.import');

    Route::post('/kelas-asal', [KelasAsalController::class, 'store'])->name('kelas-asal.store');
    Route::put('/kelas-asal/{id}', [KelasAsalController::class, 'update'])->name('kelas-asal.update');
    Route::delete('/kelas-asal/{id}', [KelasAsalController::class, 'destroy'])->name('kelas-asal.destroy');

    Route::post('/paket-menu-pilihan', [PaketMenuPilihanController::class, 'store'])->name('paket-menu.store');
    Route::put('/paket-menu-pilihan/{identifier}', [PaketMenuPilihanController::class, 'update'])->name('paket-menu.update');
    Route::delete('/paket-menu-pilihan/{identifier}', [PaketMenuPilihanController::class, 'destroy'])->name('paket-menu.destroy');

    Route::post('/kriteria-bobot-menu', [KriteriaBobotMenuController::class, 'store'])->name('kriteria-bobot.store');
    Route::delete('/kriteria-bobot-menu/{id}', [KriteriaBobotMenuController::class, 'destroy'])->name('kriteria-bobot.destroy');
});

