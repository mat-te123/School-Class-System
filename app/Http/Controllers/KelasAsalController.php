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
    public function index(Request $request): JsonResponse
    {
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
     * @param string $identifier (UUID id atau nama_kelas)
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

    /**
     * Membuat data Kelas X baru (Khusus Role Admin).
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
                'message' => 'Akses ditolak. Hanya Admin yang dapat menambah data kelas.',
            ], 403);
        }

        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', 'unique:kelas_asal,nama_kelas'],
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

        return response()->json([
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
    public function update(Request $request, string $id): JsonResponse
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
            'nama_kelas' => ['sometimes', 'required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('kelas_asal', 'nama_kelas')->ignore($kelas->id)],
            'kapasitas' => ['sometimes', 'required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'nama_kelas.required' => 'Nama kelas tidak boleh kosong.',
            'nama_kelas.unique' => 'Nama kelas sudah terdaftar.',
            'kapasitas.integer' => 'Kapasitas harus berupa angka.',
            'kapasitas.min' => 'Kapasitas minimal 1.',
        ]);

        $kelas->update($validated);

        return response()->json([
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
    public function destroy(Request $request, string $id): JsonResponse
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

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus data Kelas X.',
        ]);
    }
}
