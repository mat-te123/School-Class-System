# Design Spec: Admin CRUD operations for PaketMenuPilihanController

## Goal
Menambahkan method `store` (create), `update`, dan `destroy` (delete) pada `PaketMenuPilihanController` yang hanya dapat diakses oleh user ber-role `admin`.

## Security & Authorization
- Pengguna harus terautentikasi via guard `web` (`auth:web`).
- Pengguna harus memiliki role `'admin'` (`$user->role === 'admin'`).
- Jika pengguna belum login -> Respon `401 Unauthorized`.
- Jika pengguna login sebagai non-admin -> Respon `403 Forbidden`.

## Proposed Changes

### 1. `app/Http/Controllers/PaketMenuPilihanController.php`
Tambahkan 3 method baru:
- **`store(Request $request)`**:
  - Validasi: `kode_menu` (required, unique, integer), `nama_menu` (required, max 50), `rumpun` (in:eksakta,sosial), `kuota_kapasitas` (integer, min 1), `is_active` (boolean).
  - Respon: 201 Created.
- **`update(Request $request, string $identifier)`**:
  - Cari berdasarkan UUID `id` atau `kode_menu`. Jika tidak ditemukan -> Respon 404.
  - Validasi: `kode_menu` (unique mengabaikan ID paket ini), `nama_menu`, `rumpun`, `kuota_kapasitas`, `is_active`.
  - Respon: 200 OK.
- **`destroy(Request $request, string $identifier)`**:
  - Cari berdasarkan UUID `id` atau `kode_menu`. Jika tidak ditemukan -> Respon 404.
  - Cek jika `kuota_terisi > 0` -> Respon 422 Unprocessable Entity.
  - Hapus paket menu pilihan -> Respon 200 OK.

### 2. `routes/web.php`
Tambahkan route di dalam middleware `auth:web`:
```php
Route::post('/paket-menu-pilihan', [PaketMenuPilihanController::class, 'store'])->name('paket-menu.store');
Route::put('/paket-menu-pilihan/{identifier}', [PaketMenuPilihanController::class, 'update'])->name('paket-menu.update');
Route::delete('/paket-menu-pilihan/{identifier}', [PaketMenuPilihanController::class, 'destroy'])->name('paket-menu.destroy');
```

### 3. `bootstrap/app.php`
Tambahkan `'paket-menu-pilihan', 'paket-menu-pilihan/*'` pada pengecualian CSRF `validateCsrfTokens`.
