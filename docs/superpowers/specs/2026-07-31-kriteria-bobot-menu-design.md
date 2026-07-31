# Design Spec: KriteriaBobotMenu (Setting Bobot Mapel per Paket Menu Pilihan)

## Goal
Menyediakan fitur dan API endpoint untuk mengatur (create/set) bobot persentase mata pelajaran (`master_mata_pelajaran`) pada setiap paket menu pilihan (`paket_menu_pilihan`) di tabel `kriteria_bobot_menu`.

## Security & Authorization
- Pengaturan bobot hanya dapat diakses oleh user ber-role `'admin'` (`$user->role === 'admin'`).
- Pengguna belum login -> Respon `401 Unauthorized`.
- Pengguna non-admin -> Respon `403 Forbidden`.

## Proposed Changes

### 1. Model `KriteriaBobotMenu.php` (`app/Models/KriteriaBobotMenu.php`)
- Table: `kriteria_bobot_menu`
- Uses `HasFactory, HasUuids`
- `$timestamps = false;`
- `$fillable = ['paket_menu_pilihan_id', 'master_mata_pelajaran_id', 'bobot_persen']`
- Relasi `belongsTo` ke `PaketMenuPilihan` dan `MasterMataPelajaran`.

### 2. Model Relasi (`PaketMenuPilihan.php` & `MasterMataPelajaran.php`)
- Tambahkan relasi `kriteriaBobots()` pada `PaketMenuPilihan` dan `MasterMataPelajaran`.

### 3. Controller `KriteriaBobotMenuController.php` (`app/Http/Controllers/KriteriaBobotMenuController.php`)
- **`index(Request $request)`**:
  - Mengambil daftar bobot kriteria.
  - Filter opsional: `paket_menu_pilihan_id`
  - Relasi `paketMenuPilihan` & `masterMataPelajaran`.
- **`store(Request $request)`**:
  - Menerima single object ATAU bulk array `kriteria`:
    - `paket_menu_pilihan_id` (required, exists:paket_menu_pilihan,id)
    - `master_mata_pelajaran_id` (required, exists:master_mata_pelajaran,id)
    - `bobot_persen` (required, numeric, min:0, max:100)
  - Menyimpan / memperbarui (`updateOrCreate`) kriteria bobot menu.
  - Respon: 201 Created.
- **`destroy(Request $request, string $id)`**:
  - Menghapus 1 record kriteria bobot menu berdasarkan ID (UUID).

### 4. Routes (`routes/web.php`)
```php
use App\Http\Controllers\KriteriaBobotMenuController;

// Public / Read route
Route::get('/kriteria-bobot-menu', [KriteriaBobotMenuController::class, 'index'])->name('kriteria-bobot.index');

// Admin only routes
Route::middleware(['auth:web'])->group(function () {
    Route::post('/kriteria-bobot-menu', [KriteriaBobotMenuController::class, 'store'])->name('kriteria-bobot.store');
    Route::delete('/kriteria-bobot-menu/{id}', [KriteriaBobotMenuController::class, 'destroy'])->name('kriteria-bobot.destroy');
});
```

### 5. `bootstrap/app.php`
Tambahkan `'kriteria-bobot-menu', 'kriteria-bobot-menu/*'` ke `validateCsrfTokens` except array.
