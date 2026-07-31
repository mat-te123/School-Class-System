# Design Spec: Admin CRUD operations for KelasAsalController

## Goal
Menambahkan method `store` (create), `update`, dan `destroy` (delete) pada `KelasAsalController` yang hanya dapat diakses oleh user dengan role `admin`.

## Security & Authorization
- Pengguna harus login via guard `web` (`auth:web`).
- Pengguna harus memiliki role `'admin'` (`$user->role === 'admin'`).
- Jika pengguna belum login -> Respon `401 Unauthorized`.
- Jika pengguna login sebagai bukan admin (misal `guru_bk`) -> Respon `403 Forbidden`.

## Proposed Changes

### 1. `app/Http/Controllers/KelasAsalController.php`
Tambahkan 3 method baru:
- **`store(Request $request)`**:
  - Otorisasi: `auth:web` & role `admin`
  - Validasi: `nama_kelas` (required, unique:kelas_asal), `kapasitas` (integer, min:1), `is_active` (boolean)
  - Otomatis set `tingkat = 'X'`
  - Respon 201 Created
- **`update(Request $request, string $id)`**:
  - Otorisasi: `auth:web` & role `admin`
  - Cari `KelasAsal` (tingkat X, ID/nama) -> 404 jika tidak ketemu
  - Validasi: `nama_kelas` (unique mengabaikan ID kelas ini), `kapasitas`, `is_active`
  - Respon 200 OK
- **`destroy(Request $request, string $id)`**:
  - Otorisasi: `auth:web` & role `admin`
  - Cari `KelasAsal` (tingkat X, ID/nama) -> 404 jika tidak ketemu
  - Cek jika masih ada siswa di kelas -> Respon 422
  - Hapus kelas -> Respon 200 OK

### 2. `routes/web.php`
Tambahkan route di dalam middleware `auth:web`:
```php
Route::middleware(['auth:web'])->group(function () {
    Route::post('/kelas-asal', [KelasAsalController::class, 'store'])->name('kelas-asal.store');
    Route::put('/kelas-asal/{id}', [KelasAsalController::class, 'update'])->name('kelas-asal.update');
    Route::delete('/kelas-asal/{id}', [KelasAsalController::class, 'destroy'])->name('kelas-asal.destroy');
});
```

### 3. `bootstrap/app.php`
Tambahkan `'kelas-asal', 'kelas-asal/*'` pada daftar pengecualian `validateCsrfTokens` agar testing API via Insomnia/Postman dapat melakukan request tanpa token CSRF.
