<?php

namespace App\Http\Controllers;

use App\Models\DetailNilaiSiswa;
use App\Models\MasterMataPelajaran;
use App\Models\NilaiLegerSiswa;
use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Jobs\BulkImportDetailNilaiSiswaJob;

/**
 * FR-13: Impor nilai siswa untuk mata pelajaran tertentu.
 * FR-14: Melihat dan memperbaiki data nilai siswa yang tidak sesuai.
 */
class NilaiSiswaController extends Controller
{
    /**
     * FR-14: Daftar nilai siswa per mata pelajaran, dengan filter.
     */
    public function index(Request $request)
    {
        if ($this->denyIfNotAdmin()) {
            abort(403, 'Akses ditolak. Hanya admin yang dapat melihat daftar nilai siswa.');
        }

        $validated = $request->validate([
            'nisn'          => 'nullable|string|max:20',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'mapel_id'      => 'nullable|uuid|exists:master_mata_pelajaran,id',
            'tahun_ajaran'  => 'nullable|string|max:10',
            'semester'      => 'nullable|string|max:10',
            'per_page'      => 'nullable|integer|min:1|max:100',
        ]);

        $query = DetailNilaiSiswa::with(['mataPelajaran:id,kode_mapel,nama_mapel', 'leger.siswa:id,nisn,nis,nama_lengkap,kelas_asal_id,kelas_asal']);

        if (!empty($validated['mapel_id'])) {
            $query->where('master_mata_pelajaran_id', $validated['mapel_id']);
        }

        $query->whereHas('leger', function ($q) use ($validated) {
            if (!empty($validated['tahun_ajaran'])) {
                $q->where('tahun_ajaran', $validated['tahun_ajaran']);
            }
            if (!empty($validated['semester'])) {
                $q->where('semester', $validated['semester']);
            }
            $q->whereHas('siswa', function ($s) use ($validated) {
                if (!empty($validated['nisn'])) {
                    $s->where('nisn', $validated['nisn']);
                }
                if (!empty($validated['kelas_asal_id'])) {
                    $s->where('kelas_asal_id', $validated['kelas_asal_id']);
                }
            });
        });

        $data = $query->paginate((int) $request->input('per_page', 50));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        return view('nilai-siswa.index', compact('data'));
    }

    /**
     * FR-49: Siswa dapat melihat nilai mata pelajaran mereka sendiri.
     */
    public function indexSiswa(Request $request): JsonResponse
    {
        // Pastikan siswa login via auth:siswa guard
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Silakan login sebagai siswa terlebih dahulu.',
            ], 403);
        }

        $query = DetailNilaiSiswa::with([
            'mataPelajaran:id,kode_mapel,nama_mapel',
            'leger.siswa:id,nisn,nis,nama_lengkap',
            'leger:id,siswa_id,tahun_ajaran,semester,rata_keseluruhan',
        ])
        ->whereHas('leger', function ($q) use ($siswa) {
            $q->where('siswa_id', $siswa->id);
        })
        ->orderBy('master_mata_pelajaran_id');

        $data = $query->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil daftar nilai siswa.',
                'data'    => $data,
            ]);
        }

        return view('nilai-siswa.index-siswa', compact('data'));
    }

    /**
     * FR-14: Perbaiki satu nilai mata pelajaran siswa.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->denyIfNotAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'nilai_angka' => 'required|numeric|min:0|max:100',
        ]);

        $detail = DetailNilaiSiswa::with('mataPelajaran')->findOrFail($id);

        DB::transaction(function () use ($detail, $validated) {
            $detail->update([
                'nilai_angka' => $validated['nilai_angka'],
                'predikat'    => $this->calculatePredikat((float) $validated['nilai_angka']),
            ]);

            $this->recalculateLeger($detail->nilai_leger_siswa_id);
        });

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memperbaiki nilai siswa.',
            'data'    => $detail->fresh(['mataPelajaran', 'leger']),
        ]);
    }

    /**
     * FR-13: Impor nilai siswa untuk satu mata pelajaran tertentu (bulk)
     *
     * Payload: mapel_id, tahun_ajaran, semester, rows: [{nisn, nilai}, ...]
     */
    public function importMapel(Request $request): JsonResponse
    {
        if ($denied = $this->denyIfNotAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'mapel_id'     => 'required|uuid|exists:master_mata_pelajaran,id',
            'tahun_ajaran' => 'required|string|max:10',
            'semester'     => 'required|string|max:10',
            'rows'                 => 'required|array|min:1',
            'rows.*.nisn'          => 'required|string|max:20',
            'rows.*.nilai'         => 'required|numeric|min:0|max:100',
        ]);

        $mapel = MasterMataPelajaran::findOrFail($validated['mapel_id']);

        // Validate NISNs exist before dispatching job
        $siswaList = Siswa::whereIn('nisn', array_column($validated['rows'], 'nisn'))->pluck('nisn');
        $validRows = array_filter($validated['rows'], fn($row) => $siswaList->contains($row['nisn']));
        $invalidRows = array_filter($validated['rows'], fn($row) => !$siswaList->contains($row['nisn']));

        $expectedImported = count($validRows);
        $skippedCount = count($invalidRows);

        // Dispatch bulk import job
        BulkImportDetailNilaiSiswaJob::dispatch(
            $validated['mapel_id'],
            $validated['tahun_ajaran'],
            $validated['semester'],
            $validRows
        );

        return response()->json([
            'success'                => true,
            'message'                => "Impor nilai '{$mapel->nama_mapel}' sedang diproses di background.",
            'expected_imported'      => $expectedImported,
            'skipped_validation'     => $skippedCount,
            'status'                 => 'queued',
        ], 202);
    }

    /**
     * Hitung ulang rata-rata dan nilai_json leger berdasarkan detail nilai terkini.
     */
    private function recalculateLeger(string $legerId): void
    {
        $leger = NilaiLegerSiswa::find($legerId);
        if (!$leger) {
            return;
        }

        $details = DetailNilaiSiswa::with('mataPelajaran:id,nama_mapel')
            ->where('nilai_leger_siswa_id', $legerId)
            ->get();

        $nilaiJson = [];
        foreach ($details as $d) {
            $nilaiJson[$d->mataPelajaran->nama_mapel ?? $d->master_mata_pelajaran_id] = (float) $d->nilai_angka;
        }

        // ponytail: rata_6_mapel dipakai sama dengan rata_keseluruhan (mengikuti LegerImportService).
        // Pisahkan saat aturan 6 mapel wajib sudah ditetapkan.
        $rata = $details->count() > 0 ? round($details->avg('nilai_angka'), 2) : 0.00;

        $leger->update([
            'rata_6_mapel'     => $rata,
            'rata_keseluruhan' => $rata,
            'nilai_json'       => $nilaiJson,
        ]);
    }

    private function denyIfNotAdmin(): ?JsonResponse
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin yang dapat mengubah data nilai siswa.',
            ], 403);
        }

        return null;
    }

    private function calculatePredikat(float $nilai): string
    {
        if ($nilai >= 90) return 'A';
        if ($nilai >= 80) return 'B';
        if ($nilai >= 70) return 'C';
        return 'D';
    }
}
