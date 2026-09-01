<?php

namespace App\Http\Controllers;

use App\Models\HasilSeleksi;
use App\Models\PaketMenuPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\PendaftaranPilihan;
use App\Services\PenjurusanPlacementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminHasilPenjurusanController extends Controller
{
    private PenjurusanPlacementService $placementService;

    public function __construct(PenjurusanPlacementService $placementService)
    {
        $this->placementService = $placementService;
    }

    /**
     * FR-23: Admin menjalankan proses penentuan paket kelas.
     */
    public function runProcess(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
        ]);

        $result = $this->placementService->calculatePlacement($validated['periode_id']);

        return $this->handleWriteResponse($request, $result);
    }

    /**
     * FR-24 & FR-25: Admin melihat hasil sementara atau final penjurusan.
     */
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
            'search' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = HasilSeleksi::with(['siswa', 'paketMenuPilihan', 'pengubah'])
            ->whereHas('siswa', function ($q) use ($validated) {
                $q->whereHas('pendaftaranPilihan', function ($pq) use ($validated) {
                    $pq->where('periode_pendaftaran_id', $validated['periode_id']);
                });
            });

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $results = $query->orderByDesc('skor_penempatan')
            ->paginate((int) $request->input('per_page', 20));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        }

        return view('admin-hasil-penjurusan.index', compact('results'));
    }

    /**
     * FR-26: Admin melihat jumlah siswa yang ditempatkan pada setiap kelas.
     */
    public function rekapKuota(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
        ]);

        $rekap = PaketMenuPilihan::with(['kriteriaBobots.mataPelajaran'])
            ->where('is_active', true)
            ->get()
            ->map(function ($paket) use ($validated) {
                $placedCount = HasilSeleksi::whereHas('siswa.pendaftaranPilihan', function ($q) use ($validated) {
                    $q->where('periode_pendaftaran_id', $validated['periode_id']);
                })
                    ->where('paket_menu_pilihan_id', $paket->id)
                    ->count();

                return [
                    'id' => $paket->id,
                    'nama_menu' => $paket->nama_menu,
                    'rumpun' => $paket->rumpun,
                    'kuota_kapasitas' => $paket->kuota_kapasitas,
                    'terisi' => $placedCount,
                    'sisa' => max(0, $paket->kuota_kapasitas - $placedCount),
                    'persentase_terisi' => $paket->kuota_kapasitas > 0
                        ? round(($placedCount / $paket->kuota_kapasitas) * 100, 1)
                        : 0,
                ];
            });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $rekap,
            ]);
        }

        return view('admin-hasil-penjurusan.rekap-kuota', compact('rekap'));
    }

    /**
     * FR-25: Admin melihat skor, peringkat, pilihan kelas yang diperoleh setiap siswa.
     */
    public function showSiswa(string $siswaId, Request $request)
    {
        $this->ensureAdmin();

        $hasil = HasilSeleksi::with(['siswa', 'paketMenuPilihan.kriteriaBobots.mataPelajaran', 'pengubah'])
            ->where('siswa_id', $siswaId)
            ->first();

        if (!$hasil) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hasil penempatan siswa belum tersedia.',
                ], 404);
            }
            abort(404, 'Hasil penempatan siswa belum tersedia.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $hasil,
            ]);
        }

        return view('admin-hasil-penjurusan.show-siswa', compact('hasil'));
    }

    /**
     * FR-27 & FR-28: Admin mengubah hasil kelas siswa dengan wajib mencatat alasan.
     */
    public function overrideHasil(Request $request, string $hasilId)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'paket_menu_pilihan_id' => 'required|uuid|exists:paket_menu_pilihan,id',
            'catatan_perubahan' => 'required|string|max:1000',
        ]);

        $hasil = HasilSeleksi::findOrFail($hasilId);

        // Check if results are locked - get periode via the student's pendaftaran
        $pendaftaran = PendaftaranPilihan::where('siswa_id', $hasil->siswa_id)->latest('tanggal_submit')->first();
        $periode = $pendaftaran ? $pendaftaran->periodePendaftaran : null;

        if ($periode && $periode->is_hasil_final) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hasil penjurusan sudah dikunci. Buka kunci terlebih dahulu untuk melakukan perubahan.',
                ], 422);
            }
            abort(422, 'Hasil penjurusan sudah dikunci. Buka kunci terlebih dahulu untuk melakukan perubahan.');
        }

        $hasil->update([
            'paket_menu_pilihan_id' => $validated['paket_menu_pilihan_id'],
            'is_manual_override' => true,
            'catatan_perubahan' => $validated['catatan_perubahan'],
            'diubah_oleh' => Auth::guard('web')->id(),
            'tanggal_perubahan' => now(),
            'mekanisme' => 'Pelimpahan Kompetensi',
        ]);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Hasil penempatan siswa berhasil diubah.',
            'data' => $hasil->fresh(['siswa', 'paketMenuPilihan', 'pengubah']),
        ]);
    }

    /**
     * FR-29: Admin menetapkan hasil kelas sebagai hasil final.
     */
    public function lockFinal(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
        ]);

        $periode = PeriodePendaftaran::findOrFail($validated['periode_id']);
        $periode->update(['is_hasil_final' => true]);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Hasil penjurusan berhasil dikunci.',
            'data' => $periode->fresh(),
        ]);
    }

    /**
     * FR-30: Admin membuka kembali hasil final.
     */
    public function unlockFinal(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
        ]);

        $periode = PeriodePendaftaran::findOrFail($validated['periode_id']);
        $periode->update(['is_hasil_final' => false]);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Kunci hasil penjurusan berhasil dibuka.',
            'data' => $periode->fresh(),
        ]);
    }

    /**
     * FR-31: Admin mempublikasikan hasil penjurusan kepada siswa.
     */
    public function publishHasil(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
        ]);

        $periode = PeriodePendaftaran::findOrFail($validated['periode_id']);
        $periode->update(['status_pengumuman' => 'AKTIF']);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Hasil penjurusan berhasil dipublikasikan kepada siswa.',
            'data' => $periode->fresh(),
        ]);
    }

    /**
     * FR-32: Admin menonaktifkan publikasi hasil penjurusan.
     */
    public function unpublishHasil(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
        ]);

        $periode = PeriodePendaftaran::findOrFail($validated['periode_id']);
        $periode->update(['status_pengumuman' => 'NON-AKTIF']);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Publikasi hasil penjurusan berhasil dinonaktifkan.',
            'data' => $periode->fresh(),
        ]);
    }

    private function ensureAdmin(): void
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'admin') {
            if (request()->wantsJson() || request()->ajax()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Admin yang dapat mengelola hasil penjurusan.',
                ], 403));
            }
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengelola hasil penjurusan.');
        }
    }
}
