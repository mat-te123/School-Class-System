<?php

use App\Http\Controllers\LegerImportController;
use App\Http\Controllers\SiswaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route Login Siswa via NISN
Route::get('/login/siswa', [SiswaController::class, 'showLoginForm'])->name('siswa.login.form');
Route::post('/login/siswa', [SiswaController::class, 'login'])->name('siswa.login');
Route::post('/logout/siswa', [SiswaController::class, 'logout'])->name('siswa.logout');

// Route Profil Siswa (butuh login)
Route::middleware(['auth:siswa'])->group(function () {
    Route::get('/siswa/profile', [SiswaController::class, 'profile'])->name('siswa.profile');
});

// Route Import Leger XLSX
Route::post('/leger/import', [LegerImportController::class, 'import'])->name('leger.import');
