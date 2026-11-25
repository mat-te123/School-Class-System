<?php

namespace App\Http\Controllers;

use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Validation\Rule;

class PeriodePenjurusanController extends Controller
{
    private function getCurrentAcademicYear(): string
    {
        $year = now()->month >= 7 ? now()->year : now()->year - 1;

        return $year.'/'.($year + 1);
    }

    /**
     * Menampilkan daftar periode pendaftaran yang aktif dan sedang buka untuk siswa.
     */
    public function siswaIndex(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $now = now();

        $periodes = PeriodePendaftaran::query()
            ->where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->withCount(['paketMenuPilihans' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderByDesc('tanggal_buka')
            ->get()
            ->map(function (PeriodePendaftaran $p) {
                return [
                    'id' => $p->id,
                    'nama_periode' => $p->nama_periode,
                    'tahun_ajaran' => $p->tahun_ajaran,
                    'gelombang' => $p->gelombang,
                    'max_pilihan_siswa' => $p->max_pilihan_siswa,
                    'tanggal_buka' => $p->tanggal_buka?->toIso8601String(),
                    'tanggal_tutup' => $p->tanggal_tutup?->toIso8601String(),
                    'tanggal_pengumuman' => $p->tanggal_pengumuman?->toIso8601String(),
                    'status_pengumuman' => $p->status_pengumuman,
                    'is_active' => $p->is_active,
                    'total_paket_aktif' => $p->paket_menu_pilihans_count,
                ];
            });

        $activePeriods = PeriodePendaftaran::query()
            ->where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->orderByDesc('created_at')
            ->get(['id', 'nama_periode', 'tahun_ajaran', 'gelombang', 'tanggal_buka', 'tanggal_tutup', 'status_pengumuman']);

        if ($activePeriods->isEmpty()) {
            $activePeriods = PeriodePendaftaran::query()
                ->where('is_active', true)
                ->orderByDesc('tanggal_buka')
                ->limit(1)
                ->get(['id', 'nama_periode', 'tahun_ajaran', 'gelombang', 'tanggal_buka', 'tanggal_tutup', 'status_pengumuman']);
        }

        if ($activePeriods->isEmpty()) {
            $activePeriods = collect([[
                'id' => null,
                'nama_periode' => 'Belum Ada Periode Aktif',
                'tahun_ajaran' => '-',
                'gelombang' => '1',
                'tanggal_buka' => $now->toIso8601String(),
                'tanggal_tutup' => $now->toIso8601String(),
                'status_pengumuman' => 'NON-AKTIF',
            ]]);
        }

        $paketMenus = PaketMenuPilihan::with(['kriteriaBobots.mataPelajaran:id,kode_mapel,nama_mapel'])
            ->where('is_active', true)
            ->orderBy('rumpun')
            ->orderBy('nama_menu')
            ->get();

        $formattedMenus = $paketMenus->map(function ($paket) {
            return [
                'id' => $paket->id,
                'nama_menu' => $paket->nama_menu,
                'rumpun' => $paket->rumpun,
                'kuota_kapasitas' => $paket->kuota_kapasitas,
                'kuota_terisi' => $paket->kuota_terisi,
                'kuota_tersisa' => $paket->kuota_tersisa,
                'is_active' => $paket->is_active,
                'kriteria_bobot' => $paket->kriteriaBobots->map(function ($kb) {
                    return [
                        'id' => $kb->id,
                        'master_mata_pelajaran_id' => $kb->master_mata_pelajaran_id,
                        'nama_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->nama_mapel : 'N/A',
                        'kode_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->kode_mapel : 'N/A',
                        'bobot_persen' => round($kb->bobot_persen, 2),
                    ];
                }),
            ];
        });

        $isAlreadyAppyClasses = PendaftaranPilihan::where('siswa_id', $siswa->id)
            ->whereIn('periode_pendaftaran_id', $activePeriods->pluck('id')->filter())
            ->exists();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil daftar periode pendaftaran aktif yang sedang berjalan.',
                'data' => $periodes,
                'active_periods' => $activePeriods,
                'classes' => $formattedMenus,
            ]);
        }

        if (ViewFacade::exists('siswa.periode.index')) {
            return view('siswa.periode.index', compact('periodes', 'activePeriods', 'formattedMenus', 'isAlreadyAppyClasses'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar periode pendaftaran aktif yang sedang berjalan.',
            'data' => $periodes,
        ]);
    }

    /**
     * Halaman periode penjurusan untuk admin, dengan redirect berbasis guard.
     * Siswa diarahkan ke halaman periode miliknya; guest ke form login.
     */
    public function adminIndex(Request $request)
    {
        if (Auth::guard('siswa')->check()) {
            return redirect()->route('siswa.periode.index');
        }

        if (! Auth::guard('web')->check()) {
            return redirect()->guest(route('login'));
        }

        return $this->index($request);
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PeriodePendaftaran::query();

        if (! empty($validated['search'])) {
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
                'data' => $periode,
            ]);
        }

        $tahunAjaranSekarang = $this->getCurrentAcademicYear();

        $canAddPeriod = ! PeriodePendaftaran::where('tahun_ajaran', $tahunAjaranSekarang)->exists();

        return view('auth.period.index', compact(
            'periode',
            'tahunAjaranSekarang',
            'canAddPeriod'
        ));
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
        $this->ensureAdmin('periode penjurusan');

        $tahunAjaran = $this->getCurrentAcademicYear();

        $request->merge(['tahun_ajaran' => $tahunAjaran]);

        $validated = $request->validate($this->rules());
        $isActive = $validated['is_active'] ?? true;

        $periode = DB::transaction(function () use ($validated, $isActive) {
            if ($isActive) {
                // Non-aktifkan semua periode lain sebelum create yang baru
                $this->deactivateOtherPeriods();
            }

            return PeriodePendaftaran::create([
                ...$validated,
                'tahun_ajaran' => $validated['tahun_ajaran'],
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
        $this->ensureAdmin('periode penjurusan');
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

    public function destroy(Request $request, string $id)
    {
        $this->ensureAdmin('periode penjurusan');
        $periode = PeriodePendaftaran::findOrFail($id);

        DB::transaction(function () use ($periode) {
            $periode->paketMenuPilihans()->delete();
            $periode->delete();
        });

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Periode dihapus. Kelas dan bobot periode tersebut tidak lagi ditampilkan.',
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

    private function rules(?PeriodePendaftaran $periode = null): array
    {
        $tanggalBuka = request('tanggal_buka', $periode?->tanggal_buka);

        return [
            'nama_periode' => [$periode ? 'sometimes' : 'required', 'string', 'max:100'],
            'tahun_ajaran' => [
                $periode ? 'sometimes' : 'required',
                'string',
                Rule::unique('periode_pendaftaran', 'tahun_ajaran')
                    ->ignore($periode?->id)
                    ->whereNull('deleted_at'),
            ],
            'gelombang' => ['nullable', 'string', 'in:Utama,Susulan'],
            'max_pilihan_siswa' => ['nullable', 'integer', 'min:1'],
            'tanggal_buka' => [$periode ? 'sometimes' : 'required', 'date'],
            'tanggal_tutup' => [
                $periode ? 'sometimes' : 'required',
                'date',
                Rule::when($tanggalBuka, 'after:'.$tanggalBuka),
            ],
            'tanggal_pengumuman' => [
                'nullable',
                'date',
                Rule::when($tanggalBuka, 'after:'.$tanggalBuka),
            ],
            'tanggal_mulai_pertukaran' => [
                'nullable',
                'date',
                Rule::when(
                    request('tanggal_tutup', $periode?->tanggal_tutup),
                    'after:'.request('tanggal_tutup', $periode?->tanggal_tutup)
                ),
            ],
            'tanggal_selesai_pertukaran' => [
                'nullable',
                'date',
                Rule::when(
                    request('tanggal_mulai_pertukaran', $periode?->tanggal_mulai_pertukaran),
                    'after:'.(request('tanggal_mulai_pertukaran', $periode?->tanggal_mulai_pertukaran) ?? '')
                ),
            ],
            'status_pengumuman' => ['nullable', 'in:AKTIF,NON-AKTIF'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
