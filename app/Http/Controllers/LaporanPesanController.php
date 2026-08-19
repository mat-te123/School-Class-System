<?php

namespace App\Http\Controllers;

use App\Models\LaporanPesan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LaporanPesanController extends Controller
{
    /**
     * Menampilkan daftar semua laporan (Khusus Admin / Guru BK).
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search'   => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = LaporanPesan::with(['user:id,name,email', 'siswa:id,nama_lengkap,nisn', 'penangan:id,name,email']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->query('kategori'));
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('pesan', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $laporan = $query->latest()->paginate((int) $request->input('per_page', 10));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $laporan,
            ]);
        }

        return view('laporan-pesan.index', compact('laporan'));
    }

    /**
     * Menampilkan daftar laporan milik siswa yang sedang login.
     */
    public function indexSiswa(Request $request)
    {
        $siswaId = Auth::guard('siswa')->id();

        $laporan = LaporanPesan::where('siswa_id', $siswaId)
            ->latest()
            ->paginate((int) $request->input('per_page', 10));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Berhasil mengambil daftar laporan milik siswa',
                'data' => $laporan,
            ]);
        }

        return view('laporan-pesan.index-siswa', compact('laporan'));
    }

    /**
     * Membuat laporan pesan baru (Bisa Publik/Guest, Siswa, maupun Admin/User).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:150',
            'kategori' => 'nullable|string|max:50',
            'pesan' => 'required|string',
            'lampiran_path' => 'nullable|string|max:255',
            // Field khusus guest (opsional jika login)
            'nisn' => 'nullable|string|max:20',
            'nama' => 'nullable|string|max:100',
            'kelas' => 'nullable|string|max:50',
        ]);

        // Auto-assign ID pelapor jika terautentikasi
        if (Auth::guard('web')->check()) {
            $validated['user_id'] = Auth::guard('web')->id();
        } elseif (Auth::guard('siswa')->check()) {
            $validated['siswa_id'] = Auth::guard('siswa')->id();
            $siswa = Auth::guard('siswa')->user();
            $validated['nisn'] = $siswa->nisn ?? $validated['nisn'];
            $validated['nama'] = $siswa->nama_lengkap ?? $validated['nama'];
            $validated['kelas'] = $siswa->kelas_asal ?? $validated['kelas'];
        }

        $validated['status'] = 'pending';

        $laporan = LaporanPesan::create($validated);

        return response()->json([
            'message' => 'Laporan pesan berhasil dikirim',
            'data' => $laporan,
        ], 201);
    }

    /**
     * Menampilkan detail laporan pesan.
     */
    public function show(string $id)
    {
        $laporan = LaporanPesan::with(['user:id,name,email', 'siswa:id,nama_lengkap,nisn', 'penangan:id,name,email'])
            ->findOrFail($id);

        // Jika siswa, hanya boleh lihat milik sendiri
        if (Auth::guard('siswa')->check() && $laporan->siswa_id !== Auth::guard('siswa')->id()) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke laporan ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'message' => 'Berhasil mengambil detail laporan pesan',
                'data' => $laporan,
            ]);
        }

        return view('laporan-pesan.show', compact('laporan'));
    }

    /**
     * Memperbarui status dan catatan penanganan laporan (Khusus Admin / Guru BK).
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $laporan = LaporanPesan::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,diproses,selesai,ditolak',
            'catatan_penanganan' => 'nullable|string',
        ]);

        $validated['ditangani_oleh'] = Auth::guard('web')->id();

        $laporan->update($validated);

        return response()->json([
            'message' => 'Status laporan pesan berhasil diperbarui',
            'data' => $laporan->load(['penangan:id,name,email']),
        ]);
    }

    /**
     * Menghapus laporan pesan (Khusus Admin / Guru BK).
     */
    public function destroy(string $id): JsonResponse
    {
        $laporan = LaporanPesan::findOrFail($id);
        $laporan->delete();

        return response()->json([
            'message' => 'Laporan pesan berhasil dihapus',
        ]);
    }
}
