<?php

namespace App\Http\Controllers;

use App\Models\PaketMenuPilihan;
use App\Models\PeriodePendaftaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaketMenuPilihanController extends Controller
{
    /**
     * Mengambil daftar data Paket Menu Pilihan dengan filter opsional (rumpun, is_active, search).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = PaketMenuPilihan::query();

        // 1. Filter Rumpun (eksakta / sosial)
        if ($request->has('rumpun') && !empty($request->rumpun)) {
            $query->where('rumpun', strtolower($request->rumpun));
        }

        // 2. Filter status aktif (default true jika tidak ditentukan, atau bisa 'all')
        if ($request->has('is_active')) {
            if ($request->is_active !== 'all') {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
        } else {
            // Default hanya mengambil menu yang aktif
            $query->where('is_active', true);
        }

        // 3. Pencarian berdasarkan nama menu
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('nama_menu', 'like', '%' . $search . '%');
        }

        // 4. Urutkan berdasarkan nama_menu ascending
        $paketMenu = $query->orderBy('nama_menu', 'asc')->get();

        // 5. Transformasi data dengan menambahkan field sisa kuota
        $data = $paketMenu->map(function ($item) {
            return [
                'id' => $item->id,
                'nama_menu' => $item->nama_menu,
                'rumpun' => $item->rumpun,
                'kuota_kapasitas' => $item->kuota_kapasitas,
                'kuota_terisi' => $item->kuota_terisi,
                'kuota_tersisa' => $item->kuota_tersisa,
                'is_active' => $item->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar Paket Menu Pilihan.',
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * FR-50: Siswa melihat daftar paket menu aktif periode berjalan.
     */
    public function indexSiswa(Request $request): JsonResponse
    {
        // 1. Verifikasi siswa login
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Silakan login sebagai siswa terlebih dahulu.',
            ], 403);
        }

        // 2. Cari periode aktif yang sedang berjalan
        $activePeriods = PeriodePendaftaran::query()
            ->where('is_active', true)
            ->where('status_pengumuman', 'AKTIF')
            ->where('tanggal_buka', '<=', now())
            ->where('tanggal_tutup', '>=', now())
            ->orderByDesc('created_at')
            ->get(['id', 'nama_periode', 'tahun_ajaran', 'gelombang', 'tanggal_buka', 'tanggal_tutup', 'status_pengumuman']);

        // 3. Cari paket menu aktif yang minimal punya 1 kriteria bobot
        $paketMenus = PaketMenuPilihan::with(['kriteriaBobots.mataPelajaran:id,kode_mapel,nama_mapel'])
            ->where('is_active', true)
            ->has('kriteriaBobots')
            ->orderBy('rumpun')
            ->orderBy('nama_menu')
            ->get();

        // 4. Filter jika with_criteria=false (untuk performa)
        $includeCriteria = $request->boolean('with_criteria', true);

        $formattedMenus = $paketMenus->map(function ($paket) use ($includeCriteria) {
            $item = [
                'id' => $paket->id,
                'nama_menu' => $paket->nama_menu,
                'rumpun' => $paket->rumpun,
                'kuota_kapasitas' => $paket->kuota_kapasitas,
                'kuota_terisi' => $paket->kuota_terisi,
                'kuota_tersisa' => $paket->kuota_tersisa,
                'is_active' => $paket->is_active,
            ];

            if ($includeCriteria) {
                $item['kriteria_bobot'] = $paket->kriteriaBobots->map(function ($kb) {
                    return [
                        'id' => $kb->id,
                        'master_mata_pelajaran_id' => $kb->master_mata_pelajaran_id,
                        'nama_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->nama_mapel : 'N/A',
                        'kode_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->kode_mapel : 'N/A',
                        'bobot_persen' => round($kb->bobot_persen, 2),
                    ];
                });
            }

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar paket menu aktif.',
            'meta' => [
                'active_periods' => $activePeriods->map(fn($p) => [
                    'id' => $p->id,
                    'nama_periode' => $p->nama_periode,
                    'tahun_ajaran' => $p->tahun_ajaran,
                    'gelombang' => $p->gelombang,
                    'tanggal_buka' => is_string($p->tanggal_buka) ? $p->tanggal_buka : null,
                    'tanggal_tutup' => is_string($p->tanggal_tutup) ? $p->tanggal_tutup : null,
                    'status_pengumuman' => $p->status_pengumuman,
                ]),
                'total_paket' => $formattedMenus->count(),
                'last_updated' => now()->toIso8601String(),
            ],
            'data' => $formattedMenus,
        ]);
    }

    /**
     * FR-51: Siswa melihat detail paket menu lengkap (termasuk kriteria bobot & informasi periode).
     */
    public function showSiswa(string $identifier): JsonResponse
    {
        // 1. Verifikasi siswa login
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Silakan login sebagai siswa terlebih dahulu.',
            ], 403);
        }

        // 2. Cari paket menu aktif (hanya yang aktif)
        $paketMenu = PaketMenuPilihan::with(['kriteriaBobots.mataPelajaran:id,kode_mapel,nama_mapel,kelompok_mapel'])
            ->where(function ($q) use ($identifier) {
                $q->where('id', $identifier)->orWhere('nama_menu', $identifier);
            })
            ->where('is_active', true)
            ->first();

        if (!$paketMenu) {
            return response()->json([
                'success' => false,
                'message' => 'Paket Menu Pilihan tidak ditemukan atau tidak aktif.',
            ], 404);
        }

        // 3. Ambil informasi periode aktif berjalan
        $activePeriod = PeriodePendaftaran::query()
            ->where('is_active', true)
            ->where('status_pengumuman', 'AKTIF')
            ->where('tanggal_buka', '<=', now())
            ->where('tanggal_tutup', '>=', now())
            ->orderByDesc('created_at')
            ->first(['id', 'nama_periode', 'tahun_ajaran', 'gelombang', 'tanggal_buka', 'tanggal_tutup', 'status_pengumuman', 'max_pilihan_siswa']);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil detail Paket Menu Pilihan.',
            'data' => [
                'id' => $paketMenu->id,
                'nama_menu' => $paketMenu->nama_menu,
                'rumpun' => $paketMenu->rumpun,
                'kuota_kapasitas' => $paketMenu->kuota_kapasitas,
                'kuota_terisi' => $paketMenu->kuota_terisi,
                'kuota_tersisa' => $paketMenu->kuota_tersisa,
                'is_active' => $paketMenu->is_active,
                'kriteria_bobot' => $paketMenu->kriteriaBobots->map(function ($kb) {
                    return [
                        'id' => $kb->id,
                        'master_mata_pelajaran_id' => $kb->master_mata_pelajaran_id,
                        'nama_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->nama_mapel : 'N/A',
                        'kode_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->kode_mapel : 'N/A',
                        'kelompok_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->kelompok_mapel : 'N/A',
                        'bobot_persen' => round($kb->bobot_persen, 2),
                    ];
                }),
                'periode_aktif' => $activePeriod ? [
                    'id' => $activePeriod->id,
                    'nama_periode' => $activePeriod->nama_periode,
                    'tahun_ajaran' => $activePeriod->tahun_ajaran,
                    'gelombang' => $activePeriod->gelombang,
                    'tanggal_buka' => is_string($activePeriod->tanggal_buka) ? $activePeriod->tanggal_buka : null,
                    'tanggal_tutup' => is_string($activePeriod->tanggal_tutup) ? $activePeriod->tanggal_tutup : null,
                    'status_pengumuman' => $activePeriod->status_pengumuman,
                    'max_pilihan_siswa' => $activePeriod->max_pilihan_siswa,
                ] : null,
            ],
        ]);
    }

    /**
     * Mengambil detail satu Paket Menu Pilihan berdasarkan ID (UUID) atau nama_menu.
     *
     * @param string $identifier (UUID id atau nama_menu)
     * @return JsonResponse
     */
    public function show(string $identifier): JsonResponse
    {
        $paketMenu = PaketMenuPilihan::where('id', $identifier)
            ->orWhere('nama_menu', $identifier)
            ->first();

        if (!$paketMenu) {
            return response()->json([
                'success' => false,
                'message' => 'Paket Menu Pilihan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil detail Paket Menu Pilihan.',
            'data' => [
                'id' => $paketMenu->id,
                'nama_menu' => $paketMenu->nama_menu,
                'rumpun' => $paketMenu->rumpun,
                'kuota_kapasitas' => $paketMenu->kuota_kapasitas,
                'kuota_terisi' => $paketMenu->kuota_terisi,
                'kuota_tersisa' => $paketMenu->kuota_tersisa,
                'is_active' => $paketMenu->is_active,
            ],
        ]);
    }

    /**
     * Membuat Paket Menu Pilihan baru (Khusus Role Admin).
     *
     * @param Request $request
     * @return JsonResponse
     */
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
                'message' => 'Akses ditolak. Hanya Admin yang dapat menambah Paket Menu Pilihan.',
            ], 403);
        }

        $namaMenu    = trim($request->input('nama_menu', ''));
        $action      = $request->input('action');
        $isRestore   = $action === 'restore' || $request->boolean('restore');
        $isOverwrite = $action === 'overwrite' || $action === 'replace' || $request->boolean('overwrite');

        // Cek apakah ada paket menu dengan nama yang sama yang telah di-soft delete
        $trashed = PaketMenuPilihan::onlyTrashed()->where('nama_menu', $namaMenu)->first();

        if ($trashed) {
            // Opsi 1: Restore dan update data yang ada
            if ($isRestore) {
                $trashed->restore();
                $trashed->update([
                    'rumpun'          => strtolower($request->input('rumpun', $trashed->rumpun)),
                    'kuota_kapasitas' => $request->input('kuota_kapasitas', $trashed->kuota_kapasitas),
                    'is_active'       => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Paket menu pilihan '{$trashed->nama_menu}' yang sebelumnya terhapus berhasil dipulihkan dan diperbarui.",
                    'data'    => [
                        'id'              => $trashed->id,
                        'nama_menu'       => $trashed->nama_menu,
                        'rumpun'          => $trashed->rumpun,
                        'kuota_kapasitas' => $trashed->kuota_kapasitas,
                        'kuota_terisi'    => $trashed->kuota_terisi,
                        'kuota_tersisa'   => $trashed->kuota_tersisa,
                        'is_active'       => $trashed->is_active,
                    ],
                ], 200);
            }

            // Opsi 3: Menimpa data baru (Force delete data lama & buat record baru dengan UUID baru)
            if ($isOverwrite) {
                $trashed->forceDelete();

                $validated = $request->validate([
                    'nama_menu'       => ['required', 'string', 'max:50', Rule::unique('paket_menu_pilihan', 'nama_menu')->whereNull('deleted_at')],
                    'rumpun'          => ['required', 'string', 'in:eksakta,sosial'],
                    'kuota_kapasitas' => ['nullable', 'integer', 'min:1'],
                    'is_active'       => ['nullable', 'boolean'],
                ]);

                $paketMenu = PaketMenuPilihan::create([
                    'nama_menu'       => $validated['nama_menu'],
                    'rumpun'          => strtolower($validated['rumpun']),
                    'kuota_kapasitas' => $validated['kuota_kapasitas'] ?? 36,
                    'kuota_terisi'    => 0,
                    'is_active'       => $validated['is_active'] ?? true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Paket menu pilihan '{$paketMenu->nama_menu}' lama telah dihapus permanen dan data paket menu baru berhasil dibuat.",
                    'data'    => [
                        'id'              => $paketMenu->id,
                        'nama_menu'       => $paketMenu->nama_menu,
                        'rumpun'          => $paketMenu->rumpun,
                        'kuota_kapasitas' => $paketMenu->kuota_kapasitas,
                        'kuota_terisi'    => $paketMenu->kuota_terisi,
                        'kuota_tersisa'   => $paketMenu->kuota_tersisa,
                        'is_active'       => $paketMenu->is_active,
                    ],
                ], 201);
            }

            // Jika belum ada parameter action/restore/overwrite, kembalikan 409 Conflict dengan pilihan opsi
            return response()->json([
                'success'    => false,
                'is_trashed' => true,
                'message'    => "Paket menu pilihan dengan nama '{$trashed->nama_menu}' pernah dihapus sebelumnya. Apakah Anda ingin memulihkan (restore) atau menimpa dengan data baru (overwrite)?",
                'trashed_data' => [
                    'id'         => $trashed->id,
                    'nama_menu'  => $trashed->nama_menu,
                    'deleted_at' => $trashed->deleted_at,
                ],
                'options' => [
                    'restore'   => 'Gunakan payload JSON {"action": "restore"} atau query parameter ?restore=1 untuk memulihkan dan memperbarui data lama.',
                    'overwrite' => 'Gunakan payload JSON {"action": "overwrite"} atau query parameter ?overwrite=1 untuk menghapus permanen data lama dan membuat data baru.',
                ],
            ], 409);
        }

        $validated = $request->validate([
            'nama_menu' => ['required', 'string', 'max:50', Rule::unique('paket_menu_pilihan', 'nama_menu')->whereNull('deleted_at')],
            'rumpun' => ['required', 'string', 'in:eksakta,sosial'],
            'kuota_kapasitas' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'nama_menu.required' => 'Nama menu wajib diisi.',
            'nama_menu.unique' => 'Nama menu sudah terdaftar.',
            'rumpun.required' => 'Rumpun wajib diisi.',
            'rumpun.in' => 'Rumpun harus berupa eksakta atau sosial.',
            'kuota_kapasitas.integer' => 'Kuota kapasitas harus berupa angka.',
            'kuota_kapasitas.min' => 'Kuota kapasitas minimal 1.',
        ]);

        $paketMenu = PaketMenuPilihan::create([
            'nama_menu' => $validated['nama_menu'],
            'rumpun' => strtolower($validated['rumpun']),
            'kuota_kapasitas' => $validated['kuota_kapasitas'] ?? 36,
            'kuota_terisi' => 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menambahkan Paket Menu Pilihan baru.',
            'data' => [
                'id' => $paketMenu->id,
                'nama_menu' => $paketMenu->nama_menu,
                'rumpun' => $paketMenu->rumpun,
                'kuota_kapasitas' => $paketMenu->kuota_kapasitas,
                'kuota_terisi' => $paketMenu->kuota_terisi,
                'kuota_tersisa' => $paketMenu->kuota_tersisa,
                'is_active' => $paketMenu->is_active,
            ],
        ], 201);
    }

    /**
     * Memperbarui data Paket Menu Pilihan (Khusus Role Admin).
     *
     * @param Request $request
     * @param string $identifier (UUID id atau nama_menu)
     * @return JsonResponse
     */
    public function update(Request $request, string $identifier): JsonResponse
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
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengubah Paket Menu Pilihan.',
            ], 403);
        }

        $paketMenu = PaketMenuPilihan::where('id', $identifier)
            ->orWhere('nama_menu', $identifier)
            ->first();

        if (!$paketMenu) {
            return response()->json([
                'success' => false,
                'message' => 'Paket Menu Pilihan tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'nama_menu' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('paket_menu_pilihan', 'nama_menu')->ignore($paketMenu->id)->whereNull('deleted_at')],
            'rumpun' => ['sometimes', 'required', 'string', 'in:eksakta,sosial'],
            'kuota_kapasitas' => ['sometimes', 'required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'nama_menu.required' => 'Nama menu tidak boleh kosong.',
            'nama_menu.unique' => 'Nama menu sudah terdaftar.',
            'rumpun.in' => 'Rumpun harus berupa eksakta atau sosial.',
            'kuota_kapasitas.integer' => 'Kuota kapasitas harus berupa angka.',
            'kuota_kapasitas.min' => 'Kuota kapasitas minimal 1.',
        ]);

        if (isset($validated['rumpun'])) {
            $validated['rumpun'] = strtolower($validated['rumpun']);
        }

        $paketMenu->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memperbarui data Paket Menu Pilihan.',
            'data' => [
                'id' => $paketMenu->id,
                'nama_menu' => $paketMenu->nama_menu,
                'rumpun' => $paketMenu->rumpun,
                'kuota_kapasitas' => $paketMenu->kuota_kapasitas,
                'kuota_terisi' => $paketMenu->kuota_terisi,
                'kuota_tersisa' => $paketMenu->kuota_tersisa,
                'is_active' => $paketMenu->is_active,
            ],
        ]);
    }

    /**
     * Menghapus Paket Menu Pilihan (Khusus Role Admin).
     *
     * @param Request $request
     * @param string $identifier (UUID id atau nama_menu)
     * @return JsonResponse
     */
    public function destroy(Request $request, string $identifier): JsonResponse
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
                'message' => 'Akses ditolak. Hanya Admin yang dapat menghapus Paket Menu Pilihan.',
            ], 403);
        }

        $paketMenu = PaketMenuPilihan::where('id', $identifier)
            ->orWhere('nama_menu', $identifier)
            ->first();

        if (!$paketMenu) {
            return response()->json([
                'success' => false,
                'message' => 'Paket Menu Pilihan tidak ditemukan.',
            ], 404);
        }

        if ($paketMenu->kuota_terisi > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Paket Menu Pilihan tidak dapat dihapus karena sudah memiliki ' . $paketMenu->kuota_terisi . ' kuota terisi / pendaftar.',
            ], 422);
        }

        $paketMenu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus Paket Menu Pilihan.',
        ]);
    }
}
