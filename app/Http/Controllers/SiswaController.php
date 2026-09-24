<?php

namespace App\Http\Controllers;

use App\Models\KelasAsal;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search'        => 'nullable|string|max:150',
            'kelas_id'      => 'nullable|uuid|exists:kelas_asal,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'jenis_kelamin' => 'nullable|in:L,P',
            'angkatan'      => 'nullable|string|max:20',
            'tahun_ajaran'  => 'nullable|string|max:20',
            'periode_id'    => 'nullable|uuid|exists:periode_pendaftaran,id',
            'per_page'      => 'nullable|integer|min:1|max:100',
        ]);

        $query = Siswa::with('kelasAsalRelation');

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $kelasId = $validated['kelas_id'] ?? $validated['kelas_asal_id'] ?? null;
        if (!empty($kelasId)) {
            $query->where('kelas_asal_id', $kelasId);
        }

        if (!empty($validated['jenis_kelamin'])) {
            $query->where('jenis_kelamin', $validated['jenis_kelamin']);
        }

        $angkatan = $validated['angkatan'] ?? $validated['tahun_ajaran'] ?? null;
        if (!empty($angkatan)) {
            $query->where('angkatan', $angkatan);
        }

        if (!empty($validated['periode_id'])) {
            $query->whereHas('pendaftaranPilihan', function ($q) use ($validated) {
                $q->where('periode_pendaftaran_id', $validated['periode_id']);
            });
        }

        $siswa = $query->orderBy('nama_lengkap')
            ->paginate((int) $request->input('per_page', 10))
            ->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $siswa,
            ]);
        }

        $kelasAsal = KelasAsal::orderBy('nama_kelas')->get();
        $tahunAjaranList = PeriodePendaftaran::whereNotNull('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        return view('auth.siswa.index', compact('siswa', 'kelasAsal', 'tahunAjaranList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nisn' => 'required|string|size:10|unique:siswa,nisn',
            'nis' => 'required|string|max:10|unique:siswa,nis',
            'nama_lengkap' => 'required|string|max:150',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'angkatan' => 'nullable|string|max:9',
        ]);

        // is_active selalu false saat pembuatan (default)
        $validated['is_active'] = false;

        $siswa = Siswa::create($validated);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil menambahkan data siswa',
            'data' => $siswa
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $siswa = Siswa::with('kelasAsalRelation')->findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Berhasil mengambil detail siswa',
                'data' => $siswa
            ]);
        }

        return view('siswa.show', compact('siswa'));
    }

    public function update(Request $request, string $id)
    {
        $siswa = Siswa::findOrFail($id);

        $validated = $request->validate([
            'nisn' => 'sometimes|string|size:10|unique:siswa,nisn,' . $id,
            'nis' => 'sometimes|string|max:10|unique:siswa,nis,' . $id,
            'nama_lengkap' => 'sometimes|string|max:150',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'angkatan' => 'nullable|string|max:9',
            'is_active' => 'boolean',
        ]);


        $siswa->update($validated);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil mengubah data siswa',
            'data' => $siswa
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $siswa = Siswa::findOrFail($id);
        $siswa->delete();

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil menghapus data siswa'
        ]);
    }
}
