# Design Spec: Support kelas_asal_id during Leger Excel Upload

## Goal
Memungkinkan pengguna mengunggah file Excel Leger dengan menyertakan ID kelas asal (`kelas_asal_id` atau `kelas_id`) opsional/spesifik pada request form-data. Jika `kelas_asal_id` dikirimkan, sistem akan mengaitkan data siswa, leger, dan riwayat unggah dengan kelas asal sesuai ID tersebut.

## Proposed Changes

### 1. `LegerImportController.php`
- Tangkap `kelas_asal_id` dari request: `$kelasAsalId = $request->input('kelas_asal_id') ?? $request->input('kelas_id');`
- Validasi opsional `kelas_asal_id` (jika diisi, harus berupa string / UUID valid yang ada di tabel `kelas_asal` atau dapat ditemukan).
- Teruskan `$kelasAsalId` ke `importService->importFromXlsx(...)` (sync) atau `ProcessLegerImportJob::dispatch(...)` (async).

### 2. `ProcessLegerImportJob.php`
- Tambahkan parameter `$kelasAsalId` pada constructor dan properti kelas.
- Teruskan `$kelasAsalId` saat memanggil `$importService->importFromXlsx($this->filePath, $this->uploadedBy, $this->kelasAsalId)`.

### 3. `LegerImportService.php`
- Signature: `importFromXlsx(string $filePath, ?string $uploadedBy = null, ?string $kelasAsalId = null): array`
- Logika penentuan `KelasAsal`:
  ```php
  if (!empty($kelasAsalId)) {
      $kelasAsalModel = KelasAsal::where('id', $kelasAsalId)
          ->orWhere('nama_kelas', $kelasAsalId)
          ->first();
  }

  if (isset($kelasAsalModel) && $kelasAsalModel) {
      $kelasNama = $kelasAsalModel->nama_kelas;
  } else {
      $kelasNama = $metadata['kelas_asal'] ?? 'X A';
      $kelasAsalModel = KelasAsal::firstOrCreate(
          ['nama_kelas' => $kelasNama],
          ['id' => (string) Str::uuid(), 'tingkat' => 'X', 'kapasitas' => 36, 'is_active' => true]
      );
  }
  ```

### 4. Direct Testing & Backward Compatibility
- Jika `kelas_asal_id` tidak dikirimkan, sistem tetap berjalan otomatis menggunakan nama kelas dari file Excel (backward compatible).
