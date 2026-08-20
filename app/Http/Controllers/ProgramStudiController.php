<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgramStudiController extends Controller
{
    /**
     * FR-57: Daftar program studi dengan filter universitas/jenjang/kelompok.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search'                  => 'nullable|string|max:100',
            'per_page'                => 'nullable|integer|min:1|max:100',
            'proyeksi_universitas_id' => 'nullable|uuid',
            'jenjang'                 => 'nullable|in:D3,D4,S1,S2,S3,Profesi',
            'kelompok_saintek_soshum' => 'nullable|in:Saintek,Soshum,Campuran',
        ]);

        $query = ProgramStudi::with('proyeksiUniversitas');

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nama_prodi', 'like', "%{$search}%")
                  ->orWhereHas('proyeksiUniversitas', function ($u) use ($search) {
                      $u->where('nama_universitas', 'like', "%{$search}%")
                        ->orWhere('singkatan', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($validated['proyeksi_universitas_id'])) {
            $query->where('proyeksi_universitas_id', $validated['proyeksi_universitas_id']);
        }

        if (!empty($validated['jenjang'])) {
            $query->where('jenjang', $validated['jenjang']);
        }

        if (!empty($validated['kelompok_saintek_soshum'])) {
            $query->where('kelompok_saintek_soshum', $validated['kelompok_saintek_soshum']);
        }

        if ($request->has('is_active')) {
            if ($request->is_active !== 'all') {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
        } else {
            $query->where('is_active', true);
        }

        $data = $query->orderBy('nama_prodi')
                      ->paginate((int) $request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * FR-57: Detail satu program studi.
     */
    public function show(string $id)
    {
        $prodi = ProgramStudi::with('proyeksiUniversitas')->find($id);

        if (!$prodi) {
            return response()->json([
                'success' => false,
                'message' => 'Data program studi tidak ditemukan.',
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $prodi]);
    }

    /**
     * FR-57: Admin menambahkan program studi.
     */
    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'proyeksi_universitas_id' => 'required|uuid|exists:proyeksi_universitas,id',
            'nama_prodi'              => 'required|string|max:200',
            'jenjang'                 => 'nullable|in:D3,D4,S1,S2,S3,Profesi',
            'akreditasi_prodi'        => 'nullable|string|max:20',
            'daya_tampung'            => 'nullable|integer|min:0',
            'peminat_tahun_lalu'      => 'nullable|integer|min:0',
            'kelompok_saintek_soshum' => 'nullable|in:Saintek,Soshum,Campuran',
            'is_active'               => 'nullable|boolean',
        ], [
            'proyeksi_universitas_id.required' => 'Universitas wajib dipilih.',
            'proyeksi_universitas_id.exists'   => 'Universitas yang dipilih tidak ditemukan.',
            'nama_prodi.required'              => 'Nama program studi wajib diisi.',
        ]);

        $prodi = ProgramStudi::create($validated);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Data program studi berhasil ditambahkan.',
            'data'    => $prodi->load('proyeksiUniversitas'),
        ], 201);
    }

    /**
     * FR-57: Admin memperbarui program studi.
     */
    public function update(Request $request, string $id)
    {
        $this->ensureAdmin();

        $prodi = ProgramStudi::find($id);

        if (!$prodi) {
            return response()->json([
                'success' => false,
                'message' => 'Data program studi tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'proyeksi_universitas_id' => 'sometimes|required|uuid|exists:proyeksi_universitas,id',
            'nama_prodi'              => 'sometimes|required|string|max:200',
            'jenjang'                 => 'sometimes|nullable|in:D3,D4,S1,S2,S3,Profesi',
            'akreditasi_prodi'        => 'sometimes|nullable|string|max:20',
            'daya_tampung'            => 'sometimes|nullable|integer|min:0',
            'peminat_tahun_lalu'      => 'sometimes|nullable|integer|min:0',
            'kelompok_saintek_soshum' => 'sometimes|nullable|in:Saintek,Soshum,Campuran',
            'is_active'               => 'sometimes|boolean',
        ]);

        $prodi->update($validated);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Data program studi berhasil diperbarui.',
            'data'    => $prodi->fresh()->load('proyeksiUniversitas'),
        ]);
    }

    /**
     * FR-57: Admin menghapus program studi (soft delete).
     */
    public function destroy(Request $request, string $id)
    {
        $this->ensureAdmin();

        $prodi = ProgramStudi::find($id);

        if (!$prodi) {
            return response()->json([
                'success' => false,
                'message' => 'Data program studi tidak ditemukan.',
            ], 404);
        }

        $prodi->delete();

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Data program studi berhasil dihapus.',
        ]);
    }

    private function ensureAdmin(): void
    {
        $user = Auth::guard('web')->user();

        if (!$user || $user->role !== 'admin') {
            abort(response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengelola data program studi.',
            ], 403));
        }
    }
}
