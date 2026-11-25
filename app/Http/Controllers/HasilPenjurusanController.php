<?php

namespace App\Http\Controllers;

use App\Models\DetailPendaftaranPilihan;
use App\Models\HasilSeleksi;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use App\Services\PenjurusanPlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class HasilPenjurusanController extends Controller
{
    private PenjurusanPlacementService $placementService;

    public function __construct(PenjurusanPlacementService $placementService)
    {
        $this->placementService = $placementService;
    }

    /**
     * FR-23: Admin menjalankan proses penentuan paket kelas.
     */
    public function process(Request $request)
    {
        $this->ensureAdmin('hasil penjurusan');

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
        $this->ensureAdmin('hasil penjurusan');

        $validated = $request->validate([
            'periode_id' => 'nullable|uuid|exists:periode_pendaftaran,id',
            'search' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
            'paket_menu_pilihan_id' => 'nullable|uuid|exists:paket_menu_pilihan,id',
        ]);

        $periodeId = $validated['periode_id'] ?? null;

        if ($periodeId === null && ! $request->wantsJson() && ! $request->ajax()) {
            return redirect()->route('periode-penjurusan.index');
        }

        $isFinal = PeriodePendaftaran::where('id', $periodeId)->value('is_hasil_final');
        $isPublished = PeriodePendaftaran::where('id', $periodeId)->value('status_pengumuman') === 'AKTIF';
        $AllKelas = PaketMenuPilihan::select('id', 'nama_menu')->where('is_active', true)->where('periode_id', $periodeId)->get();

        $query = HasilSeleksi::with(['siswa', 'paketMenuPilihan', 'pengubah'])
            ->where('periode_pendaftaran_id', $periodeId);

        if (! empty($validated['paket_menu_pilihan_id'])) {
            $query->where('paket_menu_pilihan_id', $validated['paket_menu_pilihan_id']);
        }

        if (! empty($validated['search'])) {
            $search = mb_strtolower(trim($validated['search']));
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->whereRaw('LOWER(nama_lengkap) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(nisn) like ?', ["%{$search}%"]);
            });
        }

        $totalSiswa = (clone $query)->count();
        $totalKelas = (clone $query)->distinct('paket_menu_pilihan_id')->count('paket_menu_pilihan_id');

        $detailKelas = (clone $query)
            ->without(['siswa', 'pengubah'])
            ->select('paket_menu_pilihan_id', DB::raw('count(*) as total'))
            ->groupBy('paket_menu_pilihan_id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->paket_menu_pilihan_id,
                    'nama_menu' => $item->paketMenuPilihan ? $item->paketMenuPilihan->nama_menu : 'Belum Ditentukan',
                    'total' => $item->total,
                ];
            });

        $results = $query->orderByDesc('skor_penempatan')
            ->paginate((int) $request->input('per_page', 20))->withQueryString();

        $summary = [
            'total_siswa' => $totalSiswa,
            'total_kelas' => $totalKelas,
            'detail_kelas' => $detailKelas,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $results,
                'summary' => $summary,
            ]);
        }

        return view('auth.hasil-kelas.index', compact('results', 'summary', 'isFinal', 'isPublished', 'AllKelas'));
    }

    /**
     * FR-26: Admin melihat jumlah siswa yang ditempatkan pada setiap kelas.
     */
    public function rekapKuota(Request $request)
    {
        $this->ensureAdmin('hasil penjurusan');

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
        ]);

        $rekap = PaketMenuPilihan::where('periode_id', $validated['periode_id'])
            ->where('is_active', true)
            ->get()
            ->map(function ($paket) use ($validated) {
                $placedCount = HasilSeleksi::where('periode_pendaftaran_id', $validated['periode_id'])
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
    public function show(string $siswaId, Request $request)
    {
        $this->ensureAdmin('hasil penjurusan');

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
        ]);
        $periodeId = $validated['periode_id'];

        $hasil = HasilSeleksi::with([
            'siswa.pendaftaranPilihan' => fn ($query) => $query
                ->where('periode_pendaftaran_id', $periodeId)
                ->with([
                    'periodePendaftaran:id,nama_periode,tahun_ajaran,gelombang',
                    'detailPendaftaran.paketMenuPilihan:id,nama_menu,rumpun',
                ]),
            'siswa.nilaiLegerSiswa' => fn ($query) => $query
                ->with(['details.mataPelajaran:id,kode_mapel,nama_mapel'])
                ->orderByDesc('tahun_ajaran')
                ->orderByDesc('semester'),
            'paketMenuPilihan.kriteriaBobots.mataPelajaran',
            'pengubah',
        ])
            ->where('siswa_id', $siswaId)
            ->where('periode_pendaftaran_id', $periodeId)
            ->first();

        if (! $hasil) {
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

        return view('auth.hasil-kelas.edit-hasil', compact('hasil'));
    }

    /**
     * FR-27 & FR-28: Admin mengubah hasil kelas siswa dengan wajib mencatat alasan.
     */
    public function update(Request $request, string $hasilId)
    {
        $this->ensureAdmin('hasil penjurusan');

        $validated = $request->validate([
            'paket_menu_pilihan_id' => 'required|uuid|exists:paket_menu_pilihan,id',
            'catatan_perubahan' => 'required|string|max:1000',
        ]);

        $hasil = HasilSeleksi::findOrFail($hasilId);

        $isPreviousChoice = DetailPendaftaranPilihan::where('paket_menu_pilihan_id', $validated['paket_menu_pilihan_id'])
            ->whereHas('pendaftaranPilihan', fn ($query) => $query
                ->where('siswa_id', $hasil->siswa_id)
                ->where('periode_pendaftaran_id', $hasil->periode_pendaftaran_id))
            ->whereHas('paketMenuPilihan', fn ($query) => $query
                ->where('periode_id', $hasil->periode_pendaftaran_id))
            ->exists();

        if (! $isPreviousChoice) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kelas tujuan harus merupakan salah satu pilihan siswa.',
                ], 422);
            }
            abort(422, 'Kelas tujuan harus merupakan salah satu pilihan siswa.');
        }

        $periode = PeriodePendaftaran::findOrFail($hasil->periode_pendaftaran_id);

        if ($periode->is_hasil_final) {
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
    public function lock(Request $request)
    {
        $this->ensureAdmin('hasil penjurusan');

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
    public function unlock(Request $request)
    {
        $this->ensureAdmin('hasil penjurusan');

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
    public function publish(Request $request)
    {
        $this->ensureAdmin('hasil penjurusan');

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
    public function unpublish(Request $request)
    {
        $this->ensureAdmin('hasil penjurusan');

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

    /**
     * FR-54 - FR-56: Siswa melihat hasil penempatan, status diterima, dan detail skor akhir.
     * Hasil hanya tampil bila status_pengumuman periode = AKTIF.
     */
    public function siswaIndex(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated / Akses ditolak.',
                ], 401);
            }
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $periode = PeriodePendaftaran::where('is_active', true)->latest('tanggal_tutup')->first();

        if (! $periode || ! $periode->isPengumumanDibuka()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hasil penempatan belum dipublikasikan.',
                ], 403);
            }
            abort(403, 'Hasil penempatan belum dipublikasikan.');
        }

        $hasil = HasilSeleksi::with('paketMenuPilihan')
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periode->id)
            ->first();

        if (! $hasil) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hasil penempatan untuk Anda belum tersedia.',
                ], 404);
            }
            abort(404, 'Hasil penempatan untuk Anda belum tersedia.');
        }

        $payload = [
            'id' => $hasil->id,
            'pilihan_ke_diterima' => $hasil->pilihan_ke_diterima,
            'mekanisme' => $hasil->mekanisme,
            'status' => $hasil->pilihan_ke_diterima ? 'Diterima' : 'Belum diterima',
            'skor_penempatan' => $hasil->skor_penempatan,
            'rata_6_mapel' => $hasil->rata_6_mapel,
            'rank_pada_pilihan' => $hasil->rank_pada_pilihan,
            'paket_diterima' => $hasil->paketMenuPilihan?->nama_menu,
            'tanggal_diproses' => $hasil->tanggal_diproses,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $payload,
            ]);
        }

        if (View::exists('siswa.hasil-kelas.index')) {
            return view('siswa.hasil-kelas.index', compact('hasil'));
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    /**
     * FR-55/Alur 5: Siswa menampilkan hasil penilaian sementara berdasarkan periode dan kelas yang dipilih.
     */
    public function hasilSementara(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated / Akses ditolak.'], 401);
            }
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $periodeQuery = PeriodePendaftaran::query();
        if ($request->filled('periode_id')) {
            $periode = $periodeQuery->where('id', $request->input('periode_id'))->first();
        } else {
            $periode = $periodeQuery->where('is_active', true)->latest('tanggal_tutup')->first();
        }

        if (! $periode) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Periode tidak ditemukan.'], 404);
            }
            abort(404, 'Periode tidak ditemukan.');
        }

        $pendaftaran = PendaftaranPilihan::with('detailPendaftaran.paketMenuPilihan')
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periode->id)
            ->first();

        $hasil = HasilSeleksi::with('paketMenuPilihan')
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periode->id)
            ->first();

        $isFinal = $periode->isPengumumanDibuka();

        $filterKelasId = $request->input('kelas_id')
            ?? $request->input('paket_menu_pilihan_id')
            ?? $request->input('paket_id');

        $responsePilihan = [];
        if ($pendaftaran) {
            foreach ($pendaftaran->detailPendaftaran as $detail) {
                if ($filterKelasId && $detail->paket_menu_pilihan_id !== $filterKelasId && $detail->paketMenuPilihan?->nama_menu !== $filterKelasId) {
                    continue;
                }

                $status = 'Sedang Dikalkulasi';
                $rank = null;
                $kuota = $detail->paketMenuPilihan?->kuota_maksimal ?? $detail->paketMenuPilihan?->kuota_kapasitas;

                if ($hasil) {
                    if ($hasil->paket_menu_pilihan_id === null) {
                        $status = 'Tergeser (Tidak Masuk Kuota)';
                    } elseif ($hasil->pilihan_ke_diterima === $detail->urutan_pilihan) {
                        $status = $isFinal ? 'Lolos Final' : 'Lolos Sementara';
                        $rank = $hasil->rank_pada_pilihan;
                    } elseif ($detail->urutan_pilihan < $hasil->pilihan_ke_diterima) {
                        $status = 'Tergeser (Tidak Masuk Kuota)';
                    } elseif ($detail->urutan_pilihan > $hasil->pilihan_ke_diterima) {
                        $status = 'Tidak Dievaluasi';
                    }
                }

                $responsePilihan[] = [
                    'urutan' => $detail->urutan_pilihan,
                    'paket_menu_id' => $detail->paket_menu_pilihan_id,
                    'paket_menu' => $detail->paketMenuPilihan?->nama_menu,
                    'rumpun' => $detail->paketMenuPilihan?->rumpun,
                    'kuota_maksimal' => $kuota,
                    'status' => $status,
                    'rank' => $rank,
                ];
            }
        }

        $riwayatProses = [];
        if ($hasil && $hasil->riwayat_proses) {
            $riwayatProses = is_array($hasil->riwayat_proses)
                ? $hasil->riwayat_proses
                : json_decode($hasil->riwayat_proses, true);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mendapatkan hasil penilaian dan penempatan sementara.',
            'periode' => [
                'id' => $periode->id,
                'nama_periode' => $periode->nama_periode,
                'tahun_ajaran' => $periode->tahun_ajaran,
                'status_pengumuman' => $periode->status_pengumuman,
                'is_pengumuman_dibuka' => $isFinal,
            ],
            'hasil_penilaian' => $hasil ? [
                'id' => $hasil->id,
                'pilihan_ke_diterima' => $hasil->pilihan_ke_diterima,
                'paket_diterima' => $hasil->paketMenuPilihan?->nama_menu,
                'skor_penempatan' => $hasil->skor_penempatan,
                'rata_6_mapel' => $hasil->rata_6_mapel,
                'rank_pada_pilihan' => $hasil->rank_pada_pilihan,
                'mekanisme' => $hasil->mekanisme,
                'status' => ($hasil->paket_menu_pilihan_id === null)
                    ? 'Tergeser (Tidak Masuk Kuota)'
                    : ($isFinal ? 'Lolos Final' : 'Lolos Sementara'),
                'tanggal_diproses' => $hasil->tanggal_diproses,
            ] : null,
            'data' => $responsePilihan,
            'riwayat_proses' => $riwayatProses,
        ]);
    }

    /**
     * Endpoint untuk fitur Live Ranking. Menampilkan status tiap pilihan paket siswa
     * secara realtime (Lolos Sementara, Tergeser, atau Tidak Dievaluasi).
     */
    public function liveRanking(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            abort(401, 'Unauthenticated.');
        }

        $periode = PeriodePendaftaran::where('is_active', true)->first();
        if (! $periode) {
            return response()->json(['success' => false, 'message' => 'Tidak ada periode pendaftaran aktif.'], 400);
        }

        $pendaftaran = PendaftaranPilihan::with('detailPendaftaran.paketMenuPilihan')
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periode->id)
            ->first();

        if (! $pendaftaran) {
            return response()->json(['success' => false, 'message' => 'Anda belum mengirimkan pendaftaran pilihan.'], 404);
        }

        $hasil = HasilSeleksi::where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periode->id)
            ->first();
        $isFinal = $periode->isPengumumanDibuka();

        $responsePilihan = [];
        foreach ($pendaftaran->detailPendaftaran as $detail) {
            $status = 'Sedang Dikalkulasi';
            $rank = null;
            $kuota = $detail->paketMenuPilihan->kuota_maksimal;

            if ($hasil) {
                if ($hasil->paket_menu_pilihan_id === null) {
                    $status = 'Tergeser (Tidak Masuk Kuota)';
                } elseif ($hasil->pilihan_ke_diterima === $detail->urutan_pilihan) {
                    $status = $isFinal ? 'Lolos Final' : 'Lolos Sementara';
                    $rank = $hasil->rank_pada_pilihan;
                } elseif ($detail->urutan_pilihan < $hasil->pilihan_ke_diterima) {
                    $status = 'Tergeser (Tidak Masuk Kuota)';
                } elseif ($detail->urutan_pilihan > $hasil->pilihan_ke_diterima) {
                    $status = 'Tidak Dievaluasi';
                }
            }

            $responsePilihan[] = [
                'urutan' => $detail->urutan_pilihan,
                'paket_menu' => $detail->paketMenuPilihan->nama_menu,
                'kuota_maksimal' => $kuota,
                'status' => $status,
                'rank' => $rank,
            ];
        }

        $riwayatProses = [];
        if ($hasil && $hasil->riwayat_proses) {
            $riwayatProses = is_array($hasil->riwayat_proses) ? $hasil->riwayat_proses : json_decode($hasil->riwayat_proses, true);
        }

        return response()->json([
            'success' => true,
            'is_final' => $isFinal,
            'data' => $responsePilihan,
            'riwayat_proses' => $riwayatProses,
        ]);
    }
}
