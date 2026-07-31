# KriteriaBobotMenu Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create `KriteriaBobotMenu` model, relationships, and `KriteriaBobotMenuController` endpoints (`store`, `index`, `destroy`) for managing choice menu weights.

**Architecture:** Standard Eloquent model `KriteriaBobotMenu` relating `PaketMenuPilihan` and `MasterMataPelajaran`. REST API controller handling batch/single weight creation.

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit.

## Global Constraints
- `store` and `destroy` require `$user->role === 'admin'` via `auth:web` guard.
- Validation: `paket_menu_pilihan_id` and `master_mata_pelajaran_id` must exist in respective tables.
- `bobot_persen` numeric between 0 and 100.

---

### Task 1: Create KriteriaBobotMenu Model and Relationships

**Files:**
- Create: `app/Models/KriteriaBobotMenu.php`
- Modify: `app/Models/PaketMenuPilihan.php`
- Modify: `app/Models/MasterMataPelajaran.php`

- [ ] **Step 1: Create KriteriaBobotMenu model**

Create `app/Models/KriteriaBobotMenu.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KriteriaBobotMenu extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kriteria_bobot_menu';

    public $timestamps = false;

    protected $fillable = [
        'paket_menu_pilihan_id',
        'master_mata_pelajaran_id',
        'bobot_persen',
    ];

    protected $casts = [
        'bobot_persen' => 'float',
    ];

    public function paketMenuPilihan(): BelongsTo
    {
        return $this->belongsTo(PaketMenuPilihan::class, 'paket_menu_pilihan_id');
    }

    public function masterMataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MasterMataPelajaran::class, 'master_mata_pelajaran_id');
    }
}
```

- [ ] **Step 2: Add relations on PaketMenuPilihan and MasterMataPelajaran**

In `app/Models/PaketMenuPilihan.php`:
Add `kriteriaBobots()` relationship (`hasMany(KriteriaBobotMenu::class, 'paket_menu_pilihan_id')`).

In `app/Models/MasterMataPelajaran.php`:
Add `kriteriaBobots()` relationship (`hasMany(KriteriaBobotMenu::class, 'master_mata_pelajaran_id')`).

---

### Task 2: Create Controller, Routes, and Feature Tests

**Files:**
- Modify: `bootstrap/app.php`
- Create: `app/Http/Controllers/KriteriaBobotMenuController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/KriteriaBobotMenuControllerTest.php`

- [ ] **Step 1: Write failing feature test**

Create `tests/Feature/KriteriaBobotMenuControllerTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\MasterMataPelajaran;
use App\Models\PaketMenuPilihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KriteriaBobotMenuControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_kriteria_bobot_menu(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_bobot',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paket = PaketMenuPilihan::create([
            'kode_menu' => 10,
            'nama_menu' => 'Menu Test',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
        ]);

        $mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'MAT_U',
            'nama_mapel' => 'Matematika Umum',
            'kelompok_mapel' => 'umum',
        ]);

        $response = $this->actingAs($admin, 'web')->postJson('/kriteria-bobot-menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel->id,
            'bobot_persen' => 50.00,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menyimpan kriteria bobot menu.',
            ]);

        $this->assertDatabaseHas('kriteria_bobot_menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel->id,
            'bobot_persen' => 50.00,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify failure**

Run: `docker exec school_class_system_app php artisan test --filter=KriteriaBobotMenuControllerTest`
Expected: FAIL (404 / 405 Method Not Allowed)

- [ ] **Step 3: Implement controller, CSRF exception, and routes**

1. Update `bootstrap/app.php` with `'kriteria-bobot-menu', 'kriteria-bobot-menu/*'`.
2. Create `app/Http/Controllers/KriteriaBobotMenuController.php` with `index`, `store` (supporting single/bulk payload), and `destroy`.
3. Update `routes/web.php` with routes.

- [ ] **Step 4: Run test to verify pass**

Run: `docker exec school_class_system_app php artisan test --filter=KriteriaBobotMenuControllerTest`
Expected: PASS

- [ ] **Step 5: Run full test suite**

Run: `docker exec school_class_system_app php artisan test`
Expected: PASS
