# Add Angkatan Field to Siswa Table & Registration Form Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `angkatan` column to `siswa` table and update registration API endpoint & validation to require and save `angkatan`.

**Architecture:** Migration adds nullable column `angkatan` to `siswa`. `Siswa` model adds `$fillable` entry. `SiswaAuthController` validates and saves `angkatan` during `register()`.

**Tech Stack:** PHP 8.3, Laravel 12, PostgreSQL / SQLite.

## Global Constraints
- Column name: `angkatan` (string, max 20, nullable)
- Validation rule on register: `['required', 'string', 'max:20']`

---

### Task 1: Migration and Model Updates

**Files:**
- Create: `database/migrations/2026_07_31_000000_add_angkatan_to_siswa_table.php`
- Modify: `app/Models/Siswa.php`

- [ ] **Step 1: Create migration file**

Create `database/migrations/2026_07_31_000000_add_angkatan_to_siswa_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('angkatan', 20)->nullable()->after('kelas_asal');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('angkatan');
        });
    }
};
```

- [ ] **Step 2: Run migration inside container**

Run: `docker exec school_class_system_app php artisan migrate`
Expected: PASS

- [ ] **Step 3: Update Siswa model fillable**

Modify `app/Models/Siswa.php`:
Add `'angkatan'` into `$fillable`.

---

### Task 2: Controller Validation & Response Update

**Files:**
- Modify: `app/Http/Controllers/SiswaAuthController.php`
- Modify: `tests/Feature/SiswaAuthControllerTest.php`

- [ ] **Step 1: Write failing/updated test in SiswaAuthControllerTest**

Update `test_complete_registration_success` in `tests/Feature/SiswaAuthControllerTest.php`:
Include `'angkatan' => '2024/2025'` in payload and assertions.

- [ ] **Step 2: Run test to verify failure**

Run: `docker exec school_class_system_app php artisan test --filter=SiswaAuthControllerTest`
Expected: FAIL (validation error because `angkatan` not yet in controller rules)

- [ ] **Step 3: Update SiswaAuthController register, login, and profile methods**

Modify `app/Http/Controllers/SiswaAuthController.php`:
- Add `'angkatan' => ['required', 'string', 'max:20']` to `register` validation.
- Add `'angkatan.required' => 'Angkatan wajib diisi.'` to error messages.
- Pass `'angkatan' => $validated['angkatan']` to `$siswa->update(...)`.
- Return `angkatan` in JSON responses for `login`, `register`, and `profile`.

- [ ] **Step 4: Run test to verify pass**

Run: `docker exec school_class_system_app php artisan test --filter=SiswaAuthControllerTest`
Expected: PASS

- [ ] **Step 5: Run full test suite**

Run: `docker exec school_class_system_app php artisan test`
Expected: PASS
