<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class SiswaController extends Controller
{
    public function index(): JsonResponse
    {
        $siswa = Siswa::with('kelasAsalRelation')->get();
        return response()->json([
            'message' => 'Berhasil mengambil daftar siswa',
            'data' => $siswa
        ]);
    }

    public function store(Request $request): JsonResponse
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

        return response()->json([
            'message' => 'Berhasil menambahkan data siswa',
            'data' => $siswa
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $siswa = Siswa::with('kelasAsalRelation')->findOrFail($id);
        
        return response()->json([
            'message' => 'Berhasil mengambil detail siswa',
            'data' => $siswa
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
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
            'password' => 'nullable|string|min:8'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $siswa->update($validated);

        return response()->json([
            'message' => 'Berhasil mengubah data siswa',
            'data' => $siswa
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $siswa = Siswa::findOrFail($id);
        $siswa->delete();

        return response()->json([
            'message' => 'Berhasil menghapus data siswa'
        ]);
    }
}
