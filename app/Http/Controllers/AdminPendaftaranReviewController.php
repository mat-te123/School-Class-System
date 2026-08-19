<?php

namespace App\Http\Controllers;

use App\Models\PendaftaranPilihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPendaftaranReviewController extends Controller
{
    /**
     * FR-40: Admin melihat daftar seluruh pengajuan pilihan siswa.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status'   => 'nullable|string|max:20',
            'search'   => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PendaftaranPilihan::with([
            'siswa',
            'periodePendaftaran',
            'detailPendaftaran.paketMenuPilihan',
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

        $pendaftaran = $query->latest('tanggal_submit')->paginate((int) $request->input('per_page', 10));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $pendaftaran,
            ]);
        }

        return view('admin-pendaftaran-review.index', compact('pendaftaran'));
    }

    /**
     * FR-41: Admin melihat detail satu pengajuan beserta dokumen wali.
     */
    public function show(string $id)
    {
        $pendaftaran = PendaftaranPilihan::with([
            'siswa',
            'periodePendaftaran',
            'detailPendaftaran.paketMenuPilihan',
            'peninjau',
        ])->findOrFail($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $pendaftaran,
            ]);
        }

        return view('admin-pendaftaran-review.show', compact('pendaftaran'));
    }

    /**
     * FR-42: Admin menyetujui pengajuan.
     */
    public function approve(string $id): JsonResponse
    {
        $pendaftaran = PendaftaranPilihan::findOrFail($id);

        $pendaftaran->update([
            'status' => 'disetujui',
            'catatan_penolakan' => null,
            'ditinjau_oleh' => Auth::guard('web')->id(),
            'tanggal_tinjauan' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan pilihan paket berhasil disetujui.',
            'data' => $pendaftaran->fresh(),
        ]);
    }

    /**
     * FR-43: Admin menolak pengajuan dengan catatan penolakan.
     */
    public function reject(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'catatan_penolakan' => 'required|string|max:1000',
        ]);

        $pendaftaran = PendaftaranPilihan::findOrFail($id);

        $pendaftaran->update([
            'status' => 'ditolak',
            'catatan_penolakan' => $validated['catatan_penolakan'],
            'ditinjau_oleh' => Auth::guard('web')->id(),
            'tanggal_tinjauan' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan pilihan paket berhasil ditolak.',
            'data' => $pendaftaran->fresh(),
        ]);
    }

    /**
     * FR-44: Admin mengunduh dokumen wali dari suatu pengajuan.
     */
    public function downloadDokumen(string $id): StreamedResponse|JsonResponse
    {
        $pendaftaran = PendaftaranPilihan::findOrFail($id);

        if (!$pendaftaran->dokumen_wali_path) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen wali belum diunggah oleh siswa.',
            ], 404);
        }

        if (!Storage::disk('public')->exists($pendaftaran->dokumen_wali_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File dokumen tidak ditemukan di penyimpanan.',
            ], 404);
        }

        $filename = 'dokumen_wali_' . $pendaftaran->siswa->nisn
            . '.' . pathinfo($pendaftaran->dokumen_wali_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($pendaftaran->dokumen_wali_path, $filename);
    }
}
