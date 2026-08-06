# Kelas Asal Soft Delete Restore & Overwrite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement conflict resolution handling in `POST /kelas-asal` when creating a class with a name (`nama_kelas`) that was previously soft-deleted, giving clients the choice to either **Restore & Update** (Opsi 1) or **Overwrite / Create New** (Opsi 3).

**Architecture:** Update `KelasAsalController@store` to check for soft-deleted records (`onlyTrashed()`). When found without an explicit action parameter, return a `409 Conflict` JSON response presenting two options. If `action=restore` (or `restore=true`) is passed, restore the record and update its values. If `action=overwrite` (or `overwrite=true`) is passed, force delete the old record and create a fresh one with a new UUID.

**Tech Stack:** Laravel 11.x, PHP 8.3, Eloquent SoftDeletes, PHPUnit / Laravel Feature Testing.

## Global Constraints

- Preserve existing multi-guard authentication (`auth:web` for admin write access).
- Return standard JSON responses with `success`, `message`, and `data` or `options`.
- All automated tests must pass 100%.

---

### Task 1: Update KelasAsalController Store Logic for Soft-Deleted Conflict Resolution

**Files:**
- Modify: `app/Http/Controllers/KelasAsalController.php:100-150`
- Test: `tests/Feature/KelasAsalControllerTest.php`

**Interfaces:**
- Consumes: `POST /kelas-asal` with payload `{ "nama_kelas": "X A", "kapasitas": 36, "action": "restore" | "overwrite" }`
- Produces: 
  - `409 Conflict` if soft-deleted record exists and no `action`/`restore`/`overwrite` flag is supplied.
  - `200/201 OK` if `action=restore` or `restore=true` (restores & updates).
  - `201 Created` if `action=overwrite` or `overwrite=true` (force deletes old & creates new).

- [ ] **Step 1: Write failing tests for conflict detection, restore, and overwrite**

Edit `tests/Feature/KelasAsalControllerTest.php` and add test methods:
```php
    public function test_create_kelas_with_soft_deleted_name_returns_409_conflict(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_conflict_1',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X D',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);
        $kelas->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X D',
            'kapasitas' => 40,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'is_trashed' => true,
            ])
            ->assertJsonStructure(['message', 'options' => ['restore', 'overwrite']]);
    }

    public function test_create_kelas_with_action_restore_restores_and_updates(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_conflict_2',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $oldId = (string) Str::uuid();
        $kelas = KelasAsal::create([
            'id' => $oldId,
            'nama_kelas' => 'X E',
            'tingkat' => 'X',
            'kapasitas' => 30,
            'is_active' => false,
        ]);
        $kelas->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X E',
            'kapasitas' => 36,
            'is_active' => true,
            'action' => 'restore',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "Kelas 'X E' yang sebelumnya terhapus berhasil dipulihkan dan diperbarui.",
                'data' => [
                    'id' => $oldId,
                    'nama_kelas' => 'X E',
                    'kapasitas' => 36,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('kelas_asal', [
            'id' => $oldId,
            'deleted_at' => null,
            'kapasitas' => 36,
        ]);
    }

    public function test_create_kelas_with_action_overwrite_force_deletes_and_creates_new(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_conflict_3',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $oldId = (string) Str::uuid();
        $kelas = KelasAsal::create([
            'id' => $oldId,
            'nama_kelas' => 'X F',
            'tingkat' => 'X',
            'kapasitas' => 30,
            'is_active' => true,
        ]);
        $kelas->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X F',
            'kapasitas' => 36,
            'is_active' => true,
            'action' => 'overwrite',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => "Kelas 'X F' lama telah dihapus permanen dan data kelas baru berhasil dibuat.",
            ]);

        $newId = $response->json('data.id');
        $this->assertNotEquals($oldId, $newId);
        $this->assertDatabaseMissing('kelas_asal', ['id' => $oldId]);
        $this->assertDatabaseHas('kelas_asal', ['id' => $newId, 'nama_kelas' => 'X F']);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `docker exec school_class_system_app php artisan test --filter=KelasAsalControllerTest`
Expected: 3 FAILURES because conflict detection and `action` options are not implemented in `KelasAsalController@store`.

- [ ] **Step 3: Implement soft-deleted conflict resolution in KelasAsalController@store**

In `app/Http/Controllers/KelasAsalController.php`:
```php
    public function store(Request $request): JsonResponse
    {
        $user = \Illuminate\Support\Facades\Auth::guard('web')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated / Belum login.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat menambah data kelas.',
            ], 403);
        }

        $namaKelas = trim($request->input('nama_kelas', ''));
        $action    = $request->input('action');
        $isRestore   = $action === 'restore' || $request->boolean('restore');
        $isOverwrite = $action === 'overwrite' || $action === 'replace' || $request->boolean('overwrite');

        // Cek apakah ada data kelas dengan nama yang sama yang telah di-soft delete
        $trashed = KelasAsal::onlyTrashed()->where('nama_kelas', $namaKelas)->first();

        if ($trashed) {
            // Opsi 1: Restore dan update data yang ada
            if ($isRestore) {
                $trashed->restore();
                $trashed->update([
                    'tingkat'   => 'X',
                    'kapasitas' => $request->input('kapasitas', $trashed->kapasitas),
                    'is_active' => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Kelas '{$trashed->nama_kelas}' yang sebelumnya terhapus berhasil dipulihkan dan diperbarui.",
                    'data'    => $trashed,
                ], 200);
            }

            // Opsi 3: Menimpa data baru (Force delete data lama & buat record baru dengan UUID baru)
            if ($isOverwrite) {
                $trashed->forceDelete();

                $validated = $request->validate([
                    'nama_kelas' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('kelas_asal', 'nama_kelas')->whereNull('deleted_at')],
                    'kapasitas'  => ['nullable', 'integer', 'min:1'],
                    'is_active'  => ['nullable', 'boolean'],
                ]);

                $kelas = KelasAsal::create([
                    'nama_kelas' => $validated['nama_kelas'],
                    'tingkat'   => 'X',
                    'kapasitas' => $validated['kapasitas'] ?? 36,
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Kelas '{$kelas->nama_kelas}' lama telah dihapus permanen dan data kelas baru berhasil dibuat.",
                    'data'    => $kelas,
                ], 201);
            }

            // Jika belum ada parameter action/restore/overwrite, kembalikan 409 Conflict dengan pilihan opsi
            return response()->json([
                'success'    => false,
                'is_trashed' => true,
                'message'    => "Kelas dengan nama '{$namaKelas}' pernah dihapus sebelumnya. Apakah Anda ingin memulihkan (restore) atau menimpa dengan data baru (overwrite)?",
                'trashed_data' => [
                    'id'         => $trashed->id,
                    'nama_kelas' => $trashed->nama_kelas,
                    'deleted_at' => $trashed->deleted_at,
                ],
                'options' => [
                    'restore'   => 'Gunakan payload JSON {"action": "restore"} atau query parameter ?restore=1 untuk memulihkan dan memperbarui data lama.',
                    'overwrite' => 'Gunakan payload JSON {"action": "overwrite"} atau query parameter ?overwrite=1 untuk menghapus permanen data lama dan membuat data baru.',
                ],
            ], 409);
        }

        // Alur normal pembuatan kelas baru
        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('kelas_asal', 'nama_kelas')->whereNull('deleted_at')],
            'kapasitas'  => ['nullable', 'integer', 'min:1'],
            'is_active'  => ['nullable', 'boolean'],
        ], [
            'nama_kelas.required' => 'Nama kelas wajib diisi.',
            'nama_kelas.unique'   => 'Nama kelas sudah terdaftar.',
            'kapasitas.integer'   => 'Kapasitas harus berupa angka.',
            'kapasitas.min'       => 'Kapasitas minimal 1.',
        ]);

        $kelas = KelasAsal::create([
            'nama_kelas' => $validated['nama_kelas'],
            'tingkat'   => 'X',
            'kapasitas' => $validated['kapasitas'] ?? 36,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menambahkan Kelas X baru.',
            'data'    => $kelas,
        ], 201);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec school_class_system_app php artisan test --filter=KelasAsalControllerTest`
Expected: PASS (all 10 tests passed).

- [ ] **Step 5: Update API Documentation in DOKUMENTASI.md**

Update `DOKUMENTASI.md` under section `3. C. Tambah Kelas X Baru` to detail the `409 Conflict` response and `action` (`restore` / `overwrite`) parameters.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/KelasAsalController.php tests/Feature/KelasAsalControllerTest.php DOKUMENTASI.md
git commit -m "feat: add conflict resolution (restore vs overwrite) for soft-deleted kelas_asal on store"
```
