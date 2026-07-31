# Design Spec: KelasAsalController (Get Data Kelas X)

## Goal
Menyediakan API endpoint untuk mengambil data kelas asal (khusus kelas X) beserta jumlah siswa terdaftar dan detail kelas.

## Proposed Changes

### Controller
Buat controller baru `app/Http/Controllers/KelasAsalController.php`:
- **`index(Request $request)`**:
  - Filter otomatis `tingkat = 'X'`
  - Filter `is_active = true` (kecuali `is_active=all`)
  - Pencarian opsional berdasarkan `nama_kelas` via query param `search`
  - Sertakan `total_siswa` (jumlah relasi `siswas`)
  - Urutkan berdasarkan `nama_kelas` ascending
- **`show(string $identifier)`**:
  - Cari `KelasAsal` di mana `tingkat = 'X'` berdasarkan `id` (UUID) atau `nama_kelas`
  - Jika ditemukan, sertakan data relasi `siswas` (daftar siswa di kelas tersebut)

### Routes
Tambahkan di `routes/web.php`:
```php
use App\Http\Controllers\KelasAsalController;

Route::get('/kelas-asal', [KelasAsalController::class, 'index'])->name('kelas-asal.index');
Route::get('/kelas-asal/{identifier}', [KelasAsalController::class, 'show'])->name('kelas-asal.show');
```

### Response Format

`GET /kelas-asal`:
```json
{
  "success": true,
  "message": "Berhasil mengambil daftar Kelas X.",
  "total": 10,
  "data": [
    {
      "id": "uuid-string",
      "nama_kelas": "X A",
      "tingkat": "X",
      "kapasitas": 36,
      "total_siswa": 32,
      "is_active": true
    }
  ]
}
```

`GET /kelas-asal/{identifier}`:
```json
{
  "success": true,
  "message": "Berhasil mengambil detail Kelas X.",
  "data": {
    "id": "uuid-string",
    "nama_kelas": "X A",
    "tingkat": "X",
    "kapasitas": 36,
    "total_siswa": 32,
    "is_active": true,
    "siswas": [...]
  }
}
```
