<?php

namespace App\Http\Controllers;

use App\Models\PeriodePendaftaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PeriodePenjurusanController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search'   => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PeriodePendaftaran::query();

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nama_periode', 'like', "%{$search}%")
                    ->orWhere('tahun_ajaran', 'like', "%{$search}%");
            });
        }

        $periode = $query->orderByDesc('created_at')->paginate((int) $request->input('per_page', 10));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $periode,
            ]);
        }

        return view('periode-penjurusan.index', compact('periode'));
    }

    public function show(Request $request, string $id)
    {
        $periode = PeriodePendaftaran::findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $periode,
            ]);
        }

        return view('periode-penjurusan.show', compact('periode'));
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        $validated = $request->validate($this->rules());
        $isActive = $validated['is_active'] ?? true;

        $periode = DB::transaction(function () use ($validated, $isActive) {
            if ($isActive) {
                // Non-aktifkan semua periode lain sebelum create yang baru
                $this->deactivateOtherPeriods();
            }
            return PeriodePendaftaran::create([
                ...$validated,
                'gelombang' => $validated['gelombang'] ?? 'Utama',
                'max_pilihan_siswa' => $validated['max_pilihan_siswa'] ?? 3,
                'status_pengumuman' => $validated['status_pengumuman'] ?? 'NON-AKTIF',
                'is_active' => $isActive,
            ]);
        });

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Periode penjurusan berhasil dibuat.',
            'data' => $periode,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $this->ensureAdmin();
        $periode = PeriodePendaftaran::findOrFail($id);
        $validated = $request->validate($this->rules($periode));

        DB::transaction(function () use ($periode, $validated) {
            // Jika request mengaktifkan periode ini, non-aktifkan periode lain dulu
            if (isset($validated['is_active']) && (bool) $validated['is_active'] === true) {
                // excludeId agar periode ini sendiri tidak di-deactivate (idempotent-safe)
                $this->deactivateOtherPeriods(excludeId: $periode->id);
            }
            $periode->update($validated);
        });

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Periode penjurusan berhasil diperbarui.',
            'data' => $periode->fresh(),
        ]);
    }

    /**
     * Non-aktifkan semua periode yang sedang aktif, opsional kecualikan satu ID.
     */
    private function deactivateOtherPeriods(?string $excludeId = null): void
    {
        PeriodePendaftaran::where('is_active', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->update(['is_active' => false]);
    }

    private function ensureAdmin(): void
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'admin') {
            if (request()->wantsJson() || request()->ajax()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Admin yang dapat mengelola periode penjurusan.',
                ], 403));
            }
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengelola periode penjurusan.');
        }
    }

    private function rules(?PeriodePendaftaran $periode = null): array
    {
        $tanggalBuka = request('tanggal_buka', $periode?->tanggal_buka);

        return [
            'nama_periode' => [$periode ? 'sometimes' : 'required', 'string', 'max:100'],
            'tahun_ajaran' => [$periode ? 'sometimes' : 'required', 'string', 'max:10'],
            'gelombang' => ['nullable', 'string', 'max:20'],
            'max_pilihan_siswa' => ['nullable', 'integer', 'min:1'],
            'tanggal_buka' => [$periode ? 'sometimes' : 'required', 'date'],
            'tanggal_tutup' => [
                $periode ? 'sometimes' : 'required',
                'date',
                Rule::when($tanggalBuka, 'after:' . $tanggalBuka),
            ],
            'tanggal_pengumuman' => [
                'nullable',
                'date',
                Rule::when($tanggalBuka, 'after:' . $tanggalBuka),
            ],
            'tanggal_mulai_pertukaran' => ['nullable', 'date'],
            'tanggal_selesai_pertukaran' => [
                'nullable',
                'date',
                Rule::when(
                    request('tanggal_mulai_pertukaran', $periode?->tanggal_mulai_pertukaran),
                    'after:' . (request('tanggal_mulai_pertukaran', $periode?->tanggal_mulai_pertukaran) ?? '')
                ),
            ],
            'status_pengumuman' => ['nullable', 'in:AKTIF,NON-AKTIF'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}