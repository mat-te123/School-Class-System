<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search'   => 'nullable|string|max:150',
            'per_page' => 'nullable|integer|min:1|max:100',
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

        $siswa = $query->orderBy('nama_lengkap')->paginate((int) $request->input('per_page', 10));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $siswa,
            ]);
        }

        $kelasAsal = \App\Models\KelasAsal::orderBy('nama_kelas')->get();

        return view('siswa.index', compact('siswa', 'kelasAsal'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nisn' => 'required|string|size:10|unique:siswa,nisn',
            'nis' => 'required|string|max:10|unique:siswa,nis',
            'nama_lengkap' => 'required|string|max:150',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'kelas_asal' => 'nullable|string|max:50',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'angkatan' => 'nullable|string|max:4',
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
            'kelas_asal' => 'nullable|string|max:50',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'angkatan' => 'nullable|string|max:4',
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
