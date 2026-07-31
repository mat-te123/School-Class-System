# KelasAsalController Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create `KelasAsalController` to expose REST API endpoints for fetching Class X data (`/kelas-asal` and `/kelas-asal/{identifier}`) along with student counts and student details.

**Architecture:** A standard Laravel 12 controller `KelasAsalController` handling `GET /kelas-asal` (listing Class X records) and `GET /kelas-asal/{identifier}` (detailed view for a single Class X record).

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit / Pest Feature Testing.

## Global Constraints

- Filter automatically for `tingkat = 'X'`
- Support optional search by `nama_kelas`
- Expose endpoints via `routes/web.php` with named routes `kelas-asal.index` and `kelas-asal.show`

---

### Task 1: Create KelasAsalController and Endpoints

**Files:**
- Create: `app/Http/Controllers/KelasAsalController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/KelasAsalControllerTest.php`

**Interfaces:**
- Consumes: `App\Models\KelasAsal`
- Produces: `GET /kelas-asal`, `GET /kelas-asal/{identifier}`

- [ ] **Step 1: Write the failing feature test**

Create `tests/Feature/KelasAsalControllerTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\KelasAsal;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KelasAsalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_kelas_x(): void
    {
        $kelasX1 = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $kelasX2 = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X B',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        // Kelas XI (should be ignored)
        KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XI IPA 1',
            'tingkat' => 'XI',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $response = $this->getJson('/kelas-asal');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 2,
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_detail_kelas_x_by_id_or_nama(): void
    {
        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $response = $this->getJson('/kelas-asal/' . $kelas->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $kelas->id,
                    'nama_kelas' => 'X A',
                    'tingkat' => 'X',
                ],
            ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker exec school_class_system_app php artisan test --filter=KelasAsalControllerTest`
Expected: FAIL (404 Not Found)

- [ ] **Step 3: Implement KelasAsalController and register routes**

Create `app/Http/Controllers/KelasAsalController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\KelasAsal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelasAsalController extends Controller
{
    /**
     * Mengambil daftar data Kelas (khusus kelas X) dengan filter opsional.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Khusus hanya mengambil kelas tingkat X
        $query = KelasAsal::query()->where('tingkat', 'X')->withCount('siswas');

        // Filter status aktif
        if ($request->has('is_active')) {
            if ($request->is_active !== 'all') {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
        } else {
            $query->where('is_active', true);
        }

        // Pencarian berdasarkan nama kelas
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('nama_kelas', 'like', '%' . $search . '%');
        }

        // Urutkan berdasarkan nama_kelas ascending
        $kelases = $query->orderBy('nama_kelas', 'asc')->get();

        $data = $kelases->map(function ($item) {
            return [
                'id' => $item->id,
                'nama_kelas' => $item->nama_kelas,
                'tingkat' => $item->tingkat,
                'kapasitas' => $item->kapasitas,
                'total_siswa' => $item->siswas_count ?? 0,
                'is_active' => $item->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar Kelas X.',
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * Mengambil detail satu Kelas X berdasarkan ID (UUID) atau nama_kelas.
     *
     * @param string $identifier
     * @return JsonResponse
     */
    public function show(string $identifier): JsonResponse
    {
        $kelas = KelasAsal::where('tingkat', 'X')
            ->where(function ($q) use ($identifier) {
                $q->where('id', $identifier)
                  ->orWhere('nama_kelas', $identifier);
            })
            ->with(['siswas' => function ($q) {
                $q->select('id', 'kelas_asal_id', 'nisn', 'nis', 'nama_lengkap', 'jenis_kelamin', 'is_active');
            }])
            ->first();

        if (!$kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kelas X tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil detail Kelas X.',
            'data' => [
                'id' => $kelas->id,
                'nama_kelas' => $kelas->nama_kelas,
                'tingkat' => $kelas->tingkat,
                'kapasitas' => $kelas->kapasitas,
                'total_siswa' => $kelas->siswas->count(),
                'is_active' => $kelas->is_active,
                'siswas' => $kelas->siswas,
            ],
        ]);
    }
}
```

Register routes in `routes/web.php`:
```php
use App\Http\Controllers\KelasAsalController;

// Route Kelas Asal (khusus Kelas X)
Route::get('/kelas-asal', [KelasAsalController::class, 'index'])->name('kelas-asal.index');
Route::get('/kelas-asal/{identifier}', [KelasAsalController::class, 'show'])->name('kelas-asal.show');
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker exec school_class_system_app php artisan test --filter=KelasAsalControllerTest`
Expected: PASS

- [ ] **Step 5: Run complete test suite**

Run: `docker exec school_class_system_app php artisan test`
Expected: All tests PASS
