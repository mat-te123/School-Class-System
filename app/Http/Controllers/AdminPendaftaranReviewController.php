<?php

namespace App\Http\Controllers;

use App\Models\PendaftaranPilihan;
use App\Models\Siswa;
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
    public function approve(string $id, Request $request)
    {
        $pendaftaran = PendaftaranPilihan::findOrFail($id);

        $pendaftaran->update([
            'status' => 'disetujui',
            'catatan_penolakan' => null,
            'ditinjau_oleh' => Auth::guard('web')->id(),
            'tanggal_tinjauan' => now(),
        ]);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Pengajuan pilihan paket berhasil disetujui.',
            'data' => $pendaftaran->fresh(),
        ]);
    }

    /**
     * FR-43: Admin menolak pengajuan dengan catatan penolakan.
     */
    public function reject(Request $request, string $id)
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

        return $this->handleWriteResponse($request, [
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

    /**
     * FR-20: Admin melihat daftar siswa yang sudah dan belum mengisi pilihan kelas.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
     */
    public function statusPilihan(Request $request)
    {
        $validated = $request->validate([
            'periode_id'    => 'required|uuid|exists:periode_pendaftaran,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'search'        => 'nullable|string|max:50',
            'per_page'      => 'nullable|integer|min:1|max:100',
        ]);

        $query = Siswa::with('kelasAsalRelation')
            ->whereNull('deleted_at')
            ->where('is_active', true);

        if (!empty($validated['kelas_asal_id'])) {
            $query->where('kelas_asal_id', $validated['kelas_asal_id']);
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $siswa = $query->orderBy('nama_lengkap')
            ->paginate((int) $request->input('per_page', 20));

        // Collect all siswa IDs
        $siswaIds = $siswa->pluck('id');

        // Get all submissions for this period
        $submissions = PendaftaranPilihan::with('detailPendaftaran.paketMenuPilihan')
            ->where('periode_pendaftaran_id', $validated['periode_id'])
            ->whereIn('siswa_id', $siswaIds)
            ->get()
            ->keyBy('siswa_id');

        $data = $siswa->map(function ($s) use ($submissions) {
            $sub = $submissions->get($s->id);
            return [
                'siswa' => [
                    'id' => $s->id,
                    'nisn' => $s->nisn,
                    'nis' => $s->nis,
                    'nama_lengkap' => $s->nama_lengkap,
                    'kelas_asal' => $s->kelasAsalRelation?->nama_kelas,
                    'jenis_kelamin' => $s->jenis_kelamin,
                    'angkatan' => $s->angkatan,
                ],
                'has_submitted' => !is_null($sub),
                'submission' => $sub ? [
                    'id' => $sub->id,
                    'tanggal_submit' => $sub->tanggal_submit,
                    'status' => $sub->status,
                    'catatan_penolakan' => $sub->catatan_penolakan,
                ] : null,
                'pilihan' => $sub ? $sub->detailPendaftaran->map(function ($d) {
                    return [
                        'urutan_pilihan' => $d->urutan_pilihan,
                        'paket_menu' => [
                            'id' => $d->paketMenuPilihan?->id,
                            'nama_menu' => $d->paketMenuPilihan?->nama_menu,
                            'rumpun' => $d->paketMenuPilihan?->rumpun,
                        ],
                    ];
                }) : null,
            ];
        });

        $totalSudah = $data->where('has_submitted', true)->count();
        $totalBelum = $data->where('has_submitted', false)->count();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'meta' => [
                    'total_siswa' => $data->count(),
                    'total_sudah' => $totalSudah,
                    'total_belum' => $totalBelum,
                    'periode_id' => $validated['periode_id'],
                ],
                'data' => $data,
                'pagination' => [
                    'current_page' => $siswa->currentPage(),
                    'per_page' => $siswa->perPage(),
                    'total' => $siswa->total(),
                    'last_page' => $siswa->lastPage(),
                ],
            ]);
        }

        return view('admin-pendaftaran-review.status-pilihan', compact('data', 'totalSudah', 'totalBelum'));
    }

    /**
     * FR-22: Admin melihat urutan prioritas pilihan milik siswa tertentu.
     *
     * @param string $siswaId
     * @param Request $request
     * @return JsonResponse|\Illuminate\Contracts\View\View
     */
    public function prioritasPilihanSiswa(string $siswaId, Request $request)
    {
        $siswa = Siswa::with('kelasAsalRelation')->findOrFail($siswaId);

        $pendaftaranHistory = PendaftaranPilihan::with([
            'periodePendaftaran',
            'detailPendaftaran.paketMenuPilihan.kriteriaBobots.mataPelajaran',
            'peninjau',
        ])
        ->where('siswa_id', $siswaId)
        ->orderBy('tanggal_submit', 'desc')
        ->get()
        ->map(function ($p) {
            return [
                'id'                 => $p->id,
                'periode'            => [
                    'id'           => $p->periodePendaftaran?->id,
                    'nama_periode' => $p->periodePendaftaran?->nama_periode,
                    'tahun_ajaran' => $p->periodePendaftaran?->tahun_ajaran,
                    'gelombang'    => $p->periodePendaftaran?->gelombang,
                ],
                'tanggal_submit'     => $p->tanggal_submit,
                'status'             => $p->status,
                'catatan_penolakan'  => $p->catatan_penolakan,
                'dokumen_wali_path'  => $p->dokumen_wali_path,
                'ditinjau_oleh'      => $p->peninjau?->name ?? $p->peninjau?->username,
                'tanggal_tinjauan'   => $p->tanggal_tinjauan,
                'pilihan_prioritas'  => $p->detailPendaftaran->sortBy('urutan_pilihan')->values()->map(function ($d) {
                    return [
                        'urutan_pilihan' => $d->urutan_pilihan,
                        'paket_menu'     => [
                            'id'        => $d->paketMenuPilihan?->id,
                            'nama_menu' => $d->paketMenuPilihan?->nama_menu,
                            'rumpun'    => $d->paketMenuPilihan?->rumpun,
                            'kuota'     => $d->paketMenuPilihan?->kuota_kapasitas,
                        ],
                    ];
                }),
            ];
        });

        $data = [
            'siswa' => [
                'id'            => $siswa->id,
                'nisn'          => $siswa->nisn,
                'nis'           => $siswa->nis,
                'nama_lengkap'  => $siswa->nama_lengkap,
                'kelas_asal'    => $siswa->kelasAsalRelation?->nama_kelas,
                'jenis_kelamin' => $siswa->jenis_kelamin,
                'angkatan'      => $siswa->angkatan,
            ],
            'riwayat_pendaftaran' => $pendaftaranHistory,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        return view('admin-pendaftaran-review.prioritas-siswa', compact('data'));
    }
}
