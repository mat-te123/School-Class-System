# Design Spec: Add Field Angkatan to Siswa Table & Registration Form

## Goal
Menambahkan kolom `angkatan` (misal: "2024/2025" atau "2024") pada tabel `siswa`, model `Siswa`, serta menyertakan input & validasi `angkatan` pada proses registrasi siswa (`POST /register/siswa`).

## Proposed Changes

### 1. Database Migration
Buat file migrasi baru `database/migrations/2026_07_31_000000_add_angkatan_to_siswa_table.php`:
```php
Schema::table('siswa', function (Blueprint $table) {
    $table->string('angkatan', 20)->nullable()->after('kelas_asal');
});
```

### 2. Model Siswa (`app/Models/Siswa.php`)
Tambahkan `'angkatan'` pada `$fillable`.

### 3. Controller Autentikasi Siswa (`app/Http/Controllers/SiswaAuthController.php`)
Pada method `register(Request $request)`:
- Validasi input `angkatan`:
  - `'angkatan' => ['required', 'string', 'max:20']`
  - Pesan error: `'angkatan.required' => 'Angkatan wajib diisi.'`
- Simpan `angkatan` saat mengupdate record siswa:
  ```php
  $siswa->update([
      'jenis_kelamin' => $validated['jenis_kelamin'],
      'tanggal_lahir' => $validated['tanggal_lahir'],
      'angkatan' => $validated['angkatan'],
      'password' => $validated['password'],
      'is_active' => true,
  ]);
  ```
- Sertakan `angkatan` pada response JSON registrasi, login, dan profil.

### 4. Feature Tests (`tests/Feature/SiswaAuthControllerTest.php`)
Perbarui payload test registrasi siswa untuk mengikutsertakan `'angkatan' => '2024/2025'`.
