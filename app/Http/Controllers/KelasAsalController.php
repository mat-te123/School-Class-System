<?php

namespace App\Http\Controllers;

use App\Models\KelasAsal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelasAsalController extends Controller
{
    /**
     * Mengambil daftar data Kelas (khusus Kelas X) dengan filter opsional.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search'   => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Khusus hanya mengambil kelas tingkat X
        $query = KelasAsal::query()->where('tingkat', 'X')->withCount('siswas');

        // Filter status aktif (default true kecuali is_active = 'all')
        if ($request->has('is_active')) {
            if ($request->is_active !== 'all') {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
        } else {
            $query->where('is_active', true);
        }

        // Pencarian berdasarkan nama kelas
        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where('nama_kelas', 'like', "%{$search}%");
        }

        // Urutkan berdasarkan nama_kelas ascending
        $kelases = $query->orderBy('nama_kelas', 'asc')->paginate((int) $request->input('per_page', 10));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $kelases,
            ]);
        }

        return view('kelas-asal.index', compact('kelases'));
    }

    /**
     * Mengambil detail satu Kelas X berdasarkan ID (UUID) atau nama_kelas.
     *
     * @param string $identifier (UUID id atau nama_kelas)
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function show(string $identifier)
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
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data Kelas X tidak ditemukan.',
                ], 404);
            }
            abort(404, 'Data Kelas X tidak ditemukan.');
        }

        $data = [
            'id' => $kelas->id,
            'nama_kelas' => $kelas->nama_kelas,
            'tingkat' => $kelas->tingkat,
            'kapasitas' => $kelas->kapasitas,
            'total_siswa' => $kelas->siswas->count(),
            'is_active' => $kelas->is_active,
            'siswas' => $kelas->siswas,
        ];

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil detail Kelas X.',
                'data' => $data,
            ]);
        }

        return view('kelas-asal.show', compact('data'));
    }

    /**
     * Membuat data Kelas X baru (Khusus Role Admin).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
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

        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('kelas_asal', 'nama_kelas')->whereNull('deleted_at')],
            'kapasitas' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'nama_kelas.required' => 'Nama kelas wajib diisi.',
            'nama_kelas.unique' => 'Nama kelas sudah terdaftar.',
            'kapasitas.integer' => 'Kapasitas harus berupa angka.',
            'kapasitas.min' => 'Kapasitas minimal 1.',
        ]);

        $kelas = KelasAsal::create([
            'nama_kelas' => $validated['nama_kelas'],
            'tingkat' => 'X', // Khusus kelas X
            'kapasitas' => $validated['kapasitas'] ?? 36,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil menambahkan Kelas X baru.',
            'data' => $kelas,
        ], 201);
    }

    /**
     * Memperbarui data Kelas X (Khusus Role Admin).
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id)
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
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengubah data kelas.',
            ], 403);
        }

        $kelas = KelasAsal::where('tingkat', 'X')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                  ->orWhere('nama_kelas', $id);
            })
            ->first();

        if (!$kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kelas X tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'nama_kelas' => ['sometimes', 'required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('kelas_asal', 'nama_kelas')->ignore($kelas->id)->whereNull('deleted_at')],
            'kapasitas' => ['sometimes', 'required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'nama_kelas.required' => 'Nama kelas tidak boleh kosong.',
            'nama_kelas.unique' => 'Nama kelas sudah terdaftar.',
            'kapasitas.integer' => 'Kapasitas harus berupa angka.',
            'kapasitas.min' => 'Kapasitas minimal 1.',
        ]);

        $kelas->update($validated);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil memperbarui data Kelas X.',
            'data' => $kelas,
        ]);
    }

    /**
     * Menghapus data Kelas X (Khusus Role Admin).
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id)
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
                'message' => 'Akses ditolak. Hanya Admin yang dapat menghapus data kelas.',
            ], 403);
        }

        $kelas = KelasAsal::where('tingkat', 'X')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                  ->orWhere('nama_kelas', $id);
            })
            ->withCount('siswas')
            ->first();

        if (!$kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Data Kelas X tidak ditemukan.',
            ], 404);
        }

        if ($kelas->siswas_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas X tidak dapat dihapus karena masih memiliki ' . $kelas->siswas_count . ' siswa terdaftar.',
            ], 422);
        }

        $kelas->delete();

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil menghapus data Kelas X.',
        ]);
    }
}
