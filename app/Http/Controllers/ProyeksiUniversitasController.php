<?php

namespace App\Http\Controllers;

use App\Models\ProyeksiUniversitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProyeksiUniversitasController extends Controller
{
    /**
     * FR-39 / FR-57: Daftar proyeksi universitas (admin & siswa).
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search'   => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = ProyeksiUniversitas::query();

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nama_universitas', 'like', "%{$search}%")
                  ->orWhere('singkatan', 'like', "%{$search}%")
                  ->orWhere('lokasi_kota', 'like', "%{$search}%")
                  ->orWhere('lokasi_provinsi', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            if ($request->is_active !== 'all') {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
        } else {
            $query->where('is_active', true);
        }

        $data = $query->orderBy('nama_universitas')
                      ->paginate((int) $request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * FR-57: Detail universitas beserta program studinya.
     */
    public function show(string $id)
    {
        $univ = ProyeksiUniversitas::with(['programStudis' => function ($q) {
            $q->where('is_active', true)->orderBy('nama_prodi');
        }])->find($id);

        if (!$univ) {
            return response()->json([
                'success' => false,
                'message' => 'Data proyeksi universitas tidak ditemukan.',
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $univ]);
    }

    /**
     * FR-39: Admin menambahkan data proyeksi universitas.
     */
    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'nama_universitas' => 'required|string|max:200',
            'singkatan'        => 'nullable|string|max:20',
            'akreditasi'       => 'nullable|string|max:20',
            'lokasi_kota'      => 'nullable|string|max:100',
            'lokasi_provinsi'  => 'nullable|string|max:100',
            'website'          => 'nullable|url|max:255',
            'deskripsi'        => 'nullable|string',
            'tahun_data'       => 'nullable|integer|min:2000|max:2100',
            'is_active'        => 'nullable|boolean',
        ], [
            'nama_universitas.required' => 'Nama universitas wajib diisi.',
            'website.url'               => 'Format website harus berupa URL valid.',
        ]);

        $univ = ProyeksiUniversitas::create($validated);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Data proyeksi universitas berhasil ditambahkan.',
            'data'    => $univ,
        ], 201);
    }

    /**
     * FR-39: Admin memperbarui data proyeksi universitas.
     */
    public function update(Request $request, string $id)
    {
        $this->ensureAdmin();

        $univ = ProyeksiUniversitas::find($id);

        if (!$univ) {
            return response()->json([
                'success' => false,
                'message' => 'Data proyeksi universitas tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'nama_universitas' => 'sometimes|required|string|max:200',
            'singkatan'        => 'sometimes|nullable|string|max:20',
            'akreditasi'       => 'sometimes|nullable|string|max:20',
            'lokasi_kota'      => 'sometimes|nullable|string|max:100',
            'lokasi_provinsi'  => 'sometimes|nullable|string|max:100',
            'website'          => 'sometimes|nullable|url|max:255',
            'deskripsi'        => 'sometimes|nullable|string',
            'tahun_data'       => 'sometimes|nullable|integer|min:2000|max:2100',
            'is_active'        => 'sometimes|boolean',
        ]);

        $univ->update($validated);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Data proyeksi universitas berhasil diperbarui.',
            'data'    => $univ->fresh(),
        ]);
    }

    /**
     * FR-39: Admin menghapus data proyeksi universitas (soft delete).
     */
    public function destroy(Request $request, string $id)
    {
        $this->ensureAdmin();

        $univ = ProyeksiUniversitas::find($id);

        if (!$univ) {
            return response()->json([
                'success' => false,
                'message' => 'Data proyeksi universitas tidak ditemukan.',
            ], 404);
        }

        $univ->delete();

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Data proyeksi universitas berhasil dihapus.',
        ]);
    }

    private function ensureAdmin(): void
    {
        $user = Auth::guard('web')->user();

        if (!$user || $user->role !== 'admin') {
            abort(response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengelola data proyeksi universitas.',
            ], 403));
        }
    }
}
