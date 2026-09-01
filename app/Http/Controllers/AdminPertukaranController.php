<?php

namespace App\Http\Controllers;

use App\Models\HasilSeleksi;
use App\Models\PengajuanPertukaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPertukaranController extends Controller
{
    /**
     * Admin melihat daftar seluruh pengajuan pertukaran siswa.
     */
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'status'   => 'nullable|string|in:menunggu,disetujui,ditolak',
            'search'   => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PengajuanPertukaran::with([
            'siswa',
            'periodePendaftaran',
            'paketAsal',
            'paketTujuan',
            'peninjau',
        ]);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->whereHas('siswa', function ($s) use ($search) {
                    $s->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nisn', 'like', "%{$search}%");
                });
            });
        }

        $pengajuan = $query->latest('created_at')->paginate((int) $request->input('per_page', 10));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $pengajuan,
            ]);
        }

        return view('admin-pertukaran.index', compact('pengajuan'));
    }

    /**
     * Admin melihat detail 1 pengajuan pertukaran.
     */
    public function show(string $id)
    {
        $this->ensureAdmin();

        $pengajuan = PengajuanPertukaran::with([
            'siswa',
            'periodePendaftaran',
            'paketAsal',
            'paketTujuan',
            'peninjau',
        ])->findOrFail($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $pengajuan,
            ]);
        }

        return view('admin-pertukaran.show', compact('pengajuan'));
    }

    /**
     * Admin mengunduh dokumen persetujuan wali dari pengajuan pertukaran.
     */
    public function downloadDokumen(string $id): StreamedResponse|JsonResponse
    {
        $this->ensureAdmin();

        $pengajuan = PengajuanPertukaran::findOrFail($id);

        if (!$pengajuan->dokumen_persetujuan_path) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen persetujuan wali belum diunggah oleh siswa.',
            ], 404);
        }

        if (!Storage::disk('public')->exists($pengajuan->dokumen_persetujuan_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File dokumen tidak ditemukan di penyimpanan.',
            ], 404);
        }

        $filename = 'dokumen_pertukaran_' . $pengajuan->siswa->nisn
            . '.' . pathinfo($pengajuan->dokumen_persetujuan_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($pengajuan->dokumen_persetujuan_path, $filename);
    }

    /**
     * Admin menyetujui pengajuan pertukaran.
     * Saat disetujui, hasil_seleksi siswa diperbarui ke paket tujuan.
     */
    public function approve(Request $request, string $id)
    {
        $this->ensureAdmin();

        $pengajuan = PengajuanPertukaran::findOrFail($id);

        if ($pengajuan->status !== 'menunggu') {
            return $this->handleWriteResponse($request, [
                'success' => false,
                'message' => 'Pengajuan sudah diproses sebelumnya.',
            ], 422);
        }

        DB::transaction(function () use ($pengajuan, $request) {
            // Update status pengajuan
            $pengajuan->update([
                'status'           => 'disetujui',
                'catatan_admin'    => $request->input('catatan_admin'),
                'ditinjau_oleh'    => Auth::guard('web')->id(),
                'tanggal_tinjauan' => now(),
            ]);

            // Update hasil_seleksi siswa ke paket tujuan
            $hasil = HasilSeleksi::where('siswa_id', $pengajuan->siswa_id)->first();
            if ($hasil) {
                $hasil->update([
                    'paket_menu_pilihan_id' => $pengajuan->paket_tujuan_id,
                    'is_manual_override'    => true,
                    'catatan_perubahan'     => 'Persetujuan pertukaran: dari '
                        . ($pengajuan->paketAsal?->nama_menu ?? 'paket sebelumnya')
                        . ' ke ' . ($pengajuan->paketTujuan?->nama_menu ?? 'paket baru'),
                    'diubah_oleh'           => Auth::guard('web')->id(),
                    'tanggal_perubahan'     => now(),
                    'mekanisme'             => 'Pelimpahan Kompetensi',
                ]);
            }
        });

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Pengajuan pertukaran berhasil disetujui. Paket kelas siswa telah diperbarui.',
            'data'    => $pengajuan->fresh(['siswa', 'paketAsal', 'paketTujuan', 'peninjau']),
        ]);
    }

    /**
     * Admin menolak pengajuan pertukaran dengan catatan wajib.
     */
    public function reject(Request $request, string $id)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'catatan_admin' => 'required|string|max:1000',
        ]);

        $pengajuan = PengajuanPertukaran::findOrFail($id);

        if ($pengajuan->status !== 'menunggu') {
            return $this->handleWriteResponse($request, [
                'success' => false,
                'message' => 'Pengajuan sudah diproses sebelumnya.',
            ], 422);
        }

        $pengajuan->update([
            'status'           => 'ditolak',
            'catatan_admin'    => $validated['catatan_admin'],
            'ditinjau_oleh'    => Auth::guard('web')->id(),
            'tanggal_tinjauan' => now(),
        ]);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Pengajuan pertukaran berhasil ditolak.',
            'data'    => $pengajuan->fresh(['siswa', 'paketAsal', 'paketTujuan', 'peninjau']),
        ]);
    }

    private function ensureAdmin(): void
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'admin') {
            if (request()->wantsJson() || request()->ajax()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Admin yang dapat mengelola pengajuan pertukaran.',
                ], 403));
            }
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengelola pengajuan pertukaran.');
        }
    }
}
