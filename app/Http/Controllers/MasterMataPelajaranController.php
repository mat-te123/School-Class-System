<?php

namespace App\Http\Controllers;

use App\Models\MasterMataPelajaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MasterMataPelajaranController extends Controller
{
    /**
     * Mengambil daftar Master Mata Pelajaran (GET /master-mata-pelajaran).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = MasterMataPelajaran::query();

        // Filter berdasarkan kelompok_mapel (umum, pilihan, muatan_lokal)
        if ($request->has('kelompok_mapel') && !empty($request->kelompok_mapel)) {
            $query->where('kelompok_mapel', $request->kelompok_mapel);
        }

        // Filter status tiebreaker_default
        if ($request->has('is_tiebreaker_default')) {
            $query->where('is_tiebreaker_default', filter_var($request->is_tiebreaker_default, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter status aktif
        if ($request->has('is_active') && $request->is_active !== 'all') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Pencarian nama atau kode mapel
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_mapel', 'like', '%' . $search . '%')
                  ->orWhere('kode_mapel', 'like', '%' . $search . '%');
            });
        }

        $mapels = $query->orderBy('kode_mapel', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data Master Mata Pelajaran.',
            'total' => $mapels->count(),
            'data' => $mapels,
        ]);
    }

    /**
     * Membuat data Master Mata Pelajaran baru (POST /master-mata-pelajaran).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $kodeMapel   = strtoupper(trim($request->input('kode_mapel', '')));
        $action      = $request->input('action');
        $isRestore   = $action === 'restore' || $request->boolean('restore');
        $isOverwrite = $action === 'overwrite' || $action === 'replace' || $request->boolean('overwrite');

        // Cek apakah ada data mata pelajaran dengan kode yang sama yang telah di-soft delete
        $trashed = MasterMataPelajaran::onlyTrashed()->where('kode_mapel', $kodeMapel)->first();

        if ($trashed) {
            // Opsi 1: Restore dan update data yang ada
            if ($isRestore) {
                $trashed->restore();
                $trashed->update([
                    'nama_mapel'            => trim($request->input('nama_mapel', $trashed->nama_mapel)),
                    'kelompok_mapel'        => $request->input('kelompok_mapel', $trashed->kelompok_mapel),
                    'is_tiebreaker_default' => filter_var($request->input('is_tiebreaker_default', $trashed->is_tiebreaker_default), FILTER_VALIDATE_BOOLEAN),
                    'is_active'             => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Mata pelajaran '{$trashed->kode_mapel}' yang sebelumnya terhapus berhasil dipulihkan dan diperbarui.",
                    'data'    => $trashed,
                ], 200);
            }

            // Opsi 3: Menimpa data baru (Force delete data lama & buat record baru dengan UUID baru)
            if ($isOverwrite) {
                $trashed->forceDelete();

                $validated = $request->validate([
                    'kode_mapel'            => ['required', 'string', 'max:20', \Illuminate\Validation\Rule::unique('master_mata_pelajaran', 'kode_mapel')->whereNull('deleted_at')],
                    'nama_mapel'            => ['required', 'string', 'max:100'],
                    'kelompok_mapel'        => ['nullable', 'in:umum,pilihan,muatan_lokal'],
                    'is_tiebreaker_default' => ['nullable', 'boolean'],
                    'is_active'             => ['nullable', 'boolean'],
                ]);

                $mapel = MasterMataPelajaran::create([
                    'id'                    => (string) Str::uuid(),
                    'kode_mapel'            => strtoupper(trim($validated['kode_mapel'])),
                    'nama_mapel'            => trim($validated['nama_mapel']),
                    'kelompok_mapel'        => $validated['kelompok_mapel'] ?? 'umum',
                    'is_tiebreaker_default' => filter_var($request->input('is_tiebreaker_default', false), FILTER_VALIDATE_BOOLEAN),
                    'is_active'             => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Mata pelajaran '{$mapel->kode_mapel}' lama telah dihapus permanen dan data mata pelajaran baru berhasil dibuat.",
                    'data'    => $mapel,
                ], 201);
            }

            // Jika belum ada parameter action/restore/overwrite, kembalikan 409 Conflict dengan pilihan opsi
            return response()->json([
                'success'    => false,
                'is_trashed' => true,
                'message'    => "Mata pelajaran dengan kode '{$kodeMapel}' pernah dihapus sebelumnya. Apakah Anda ingin memulihkan (restore) atau menimpa dengan data baru (overwrite)?",
                'trashed_data' => [
                    'id'         => $trashed->id,
                    'kode_mapel' => $trashed->kode_mapel,
                    'nama_mapel' => $trashed->nama_mapel,
                    'deleted_at' => $trashed->deleted_at,
                ],
                'options' => [
                    'restore'   => 'Gunakan payload JSON {"action": "restore"} atau query parameter ?restore=1 untuk memulihkan dan memperbarui data lama.',
                    'overwrite' => 'Gunakan payload JSON {"action": "overwrite"} atau query parameter ?overwrite=1 untuk menghapus permanen data lama dan membuat data baru.',
                ],
            ], 409);
        }

        // 1. Validasi Input Normal
        $validated = $request->validate([
            'kode_mapel'            => ['required', 'string', 'max:20', \Illuminate\Validation\Rule::unique('master_mata_pelajaran', 'kode_mapel')->whereNull('deleted_at')],
            'nama_mapel'            => ['required', 'string', 'max:100'],
            'kelompok_mapel'        => ['nullable', 'in:umum,pilihan,muatan_lokal'],
            'is_tiebreaker_default' => ['nullable', 'boolean'],
            'is_active'             => ['nullable', 'boolean'],
        ], [
            'kode_mapel.required' => 'Kode mata pelajaran wajib diisi.',
            'kode_mapel.unique'   => 'Kode mata pelajaran sudah digunakan.',
            'kode_mapel.max'      => 'Kode mata pelajaran maksimal 20 karakter.',
            'nama_mapel.required' => 'Nama mata pelajaran wajib diisi.',
            'nama_mapel.max'      => 'Nama mata pelajaran maksimal 100 karakter.',
            'kelompok_mapel.in'   => 'Kelompok mata pelajaran harus salah satu dari: umum, pilihan, muatan_lokal.',
        ]);

        // 2. Simpan Data Baru
        $mapel = MasterMataPelajaran::create([
            'id'                    => (string) Str::uuid(),
            'kode_mapel'            => strtoupper(trim($validated['kode_mapel'])),
            'nama_mapel'            => trim($validated['nama_mapel']),
            'kelompok_mapel'        => $validated['kelompok_mapel'] ?? 'umum',
            'is_tiebreaker_default' => filter_var($request->input('is_tiebreaker_default', false), FILTER_VALIDATE_BOOLEAN),
            'is_active'             => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
        ]);

        // 3. Return Respon JSON HTTP 201 Created
        return response()->json([
            'success' => true,
            'message' => 'Berhasil menambahkan Master Mata Pelajaran baru.',
            'data'    => $mapel,
        ], 201);
    }

    /**
     * Mendapatkan detail Master Mata Pelajaran (GET /master-mata-pelajaran/{id}).
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $mapel = MasterMataPelajaran::where('id', $id)
            ->orWhere('kode_mapel', $id)
            ->first();

        if (!$mapel) {
            return response()->json([
                'success' => false,
                'message' => 'Data Master Mata Pelajaran tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $mapel,
        ]);
    }

    /**
     * Mengubah data Master Mata Pelajaran (PUT/PATCH /master-mata-pelajaran/{id}).
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $mapel = MasterMataPelajaran::where('id', $id)
            ->orWhere('kode_mapel', $id)
            ->first();

        if (!$mapel) {
            return response()->json([
                'success' => false,
                'message' => 'Data Master Mata Pelajaran tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'kode_mapel'            => ['nullable', 'string', 'max:20', \Illuminate\Validation\Rule::unique('master_mata_pelajaran', 'kode_mapel')->ignore($mapel->id)->whereNull('deleted_at')],
            'nama_mapel'            => ['nullable', 'string', 'max:100'],
            'kelompok_mapel'        => ['nullable', 'in:umum,pilihan,muatan_lokal'],
            'is_tiebreaker_default' => ['nullable', 'boolean'],
            'is_active'             => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('kode_mapel', $validated) && $validated['kode_mapel'] !== null) {
            $mapel->kode_mapel = strtoupper(trim($validated['kode_mapel']));
        }
        if (array_key_exists('nama_mapel', $validated) && $validated['nama_mapel'] !== null) {
            $mapel->nama_mapel = trim($validated['nama_mapel']);
        }
        if (array_key_exists('kelompok_mapel', $validated) && $validated['kelompok_mapel'] !== null) {
            $mapel->kelompok_mapel = $validated['kelompok_mapel'];
        }
        if ($request->has('is_tiebreaker_default') || array_key_exists('is_tiebreaker_default', $validated)) {
            $mapel->is_tiebreaker_default = $request->boolean('is_tiebreaker_default');
        }
        if ($request->has('is_active') || array_key_exists('is_active', $validated)) {
            $mapel->is_active = $request->boolean('is_active');
        }

        $mapel->save();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memperbarui data Master Mata Pelajaran.',
            'data'    => $mapel->fresh(),
        ]);
    }

    /**
     * Menghapus data Master Mata Pelajaran (DELETE /master-mata-pelajaran/{id}).
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $mapel = MasterMataPelajaran::where('id', $id)
            ->orWhere('kode_mapel', $id)
            ->first();

        if (!$mapel) {
            return response()->json([
                'success' => false,
                'message' => 'Data Master Mata Pelajaran tidak ditemukan.',
            ], 404);
        }

        $mapel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus data Master Mata Pelajaran.',
        ]);
    }
}
