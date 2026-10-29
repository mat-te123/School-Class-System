<?php

namespace App\Http\Controllers;

use App\Jobs\RunPlacementJob;
use App\Models\DetailPendaftaranPilihan;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\Str;

class SiswaPeriodeController extends Controller
{
    /**
     * Menampilkan daftar periode pendaftaran yang aktif dan sedang buka
     * sesuai dengan tanggal sistem saat ini (tanggal_buka <= now <= tanggal_tutup).
     */
    public function index(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $now = now();

        $periodes = PeriodePendaftaran::query()
            ->where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->withCount(['paketMenuPilihans' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderByDesc('tanggal_buka')
            ->get()
            ->map(function (PeriodePendaftaran $p) {
                return [
                    'id' => $p->id,
                    'nama_periode' => $p->nama_periode,
                    'tahun_ajaran' => $p->tahun_ajaran,
                    'gelombang' => $p->gelombang,
                    'max_pilihan_siswa' => $p->max_pilihan_siswa,
                    'tanggal_buka' => $p->tanggal_buka?->toIso8601String(),
                    'tanggal_tutup' => $p->tanggal_tutup?->toIso8601String(),
                    'tanggal_pengumuman' => $p->tanggal_pengumuman?->toIso8601String(),
                    'status_pengumuman' => $p->status_pengumuman,
                    'is_active' => $p->is_active,
                    'total_paket_aktif' => $p->paket_menu_pilihans_count,
                ];
            });

        $activePeriods = PeriodePendaftaran::query()
            ->where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->orderByDesc('created_at')
            ->get(['id', 'nama_periode', 'tahun_ajaran', 'gelombang', 'tanggal_buka', 'tanggal_tutup', 'status_pengumuman']);

        if ($activePeriods->isEmpty()) {
            $activePeriods = PeriodePendaftaran::query()
                ->where('is_active', true)
                ->orderByDesc('tanggal_buka')
                ->limit(1)
                ->get(['id', 'nama_periode', 'tahun_ajaran', 'gelombang', 'tanggal_buka', 'tanggal_tutup', 'status_pengumuman']);
        }

        if ($activePeriods->isEmpty()) {
            $activePeriods = collect([[
                'id' => null,
                'nama_periode' => 'Belum Ada Periode Aktif',
                'tahun_ajaran' => '-',
                'gelombang' => '1',
                'tanggal_buka' => $now->toIso8601String(),
                'tanggal_tutup' => $now->toIso8601String(),
                'status_pengumuman' => 'NON-AKTIF',
            ]]);
        }

        // Ambil data paket kelas aktif untuk daftar kelas pada view periode
        $paketMenus = PaketMenuPilihan::with(['kriteriaBobots.mataPelajaran:id,kode_mapel,nama_mapel'])
            ->where('is_active', true)
            ->orderBy('rumpun')
            ->orderBy('nama_menu')
            ->get();

        $formattedMenus = $paketMenus->map(function ($paket) {
            return [
                'id' => $paket->id,
                'nama_menu' => $paket->nama_menu,
                'rumpun' => $paket->rumpun,
                'kuota_kapasitas' => $paket->kuota_kapasitas,
                'kuota_terisi' => $paket->kuota_terisi,
                'kuota_tersisa' => $paket->kuota_tersisa,
                'is_active' => $paket->is_active,
                'kriteria_bobot' => $paket->kriteriaBobots->map(function ($kb) {
                    return [
                        'id' => $kb->id,
                        'master_mata_pelajaran_id' => $kb->master_mata_pelajaran_id,
                        'nama_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->nama_mapel : 'N/A',
                        'kode_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->kode_mapel : 'N/A',
                        'bobot_persen' => round($kb->bobot_persen, 2),
                    ];
                }),
            ];
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil daftar periode pendaftaran aktif yang sedang berjalan.',
                'data' => $periodes,
                'active_periods' => $activePeriods,
                'classes' => $formattedMenus,
            ]);
        }

        if (ViewFacade::exists('siswa.periode.index')) {
            return view('siswa.periode.index', compact('periodes', 'activePeriods', 'formattedMenus'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar periode pendaftaran aktif yang sedang berjalan.',
            'data' => $periodes,
        ]);
    }

    /**
     * Menampilkan screen form input kelas dengan mengembalikan data kelas dari periode tertentu.
     */
    public function show(Request $request, string $id)
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

        $periode = PeriodePendaftaran::where('id', $id)
            ->firstOrFail();

        $now = now();
        $isBuka = ($periode->is_active && $periode->tanggal_buka && $periode->tanggal_tutup)
            ? ($now->gte($periode->tanggal_buka) && $now->lte($periode->tanggal_tutup))
            : false;

        // Ambil data paket kelas yang tersedia untuk periode ini
        $paketMenus = PaketMenuPilihan::with(['kriteriaBobots.mataPelajaran:id,kode_mapel,nama_mapel'])
            ->where('is_active', true)
            ->where(function ($q) use ($periode) {
                $q->whereNull('periode_id')->orWhere('periode_id', $periode->id);
            })
            ->orderBy('rumpun')
            ->orderBy('nama_menu')
            ->get();

        if ($paketMenus->isEmpty()) {
            $paketMenus = PaketMenuPilihan::with(['kriteriaBobots.mataPelajaran:id,kode_mapel,nama_mapel'])
                ->where('is_active', true)
                ->orderBy('rumpun')
                ->orderBy('nama_menu')
                ->get();
        }

        $classes = $paketMenus->map(function ($paket) {
            return [
                'id' => $paket->id,
                'nama_menu' => $paket->nama_menu,
                'rumpun' => $paket->rumpun,
                'kuota_kapasitas' => $paket->kuota_kapasitas,
                'kuota_terisi' => $paket->kuota_terisi,
                'kuota_tersisa' => $paket->kuota_tersisa,
                'is_active' => $paket->is_active,
                'kriteria_bobot' => $paket->kriteriaBobots->map(function ($kb) {
                    return [
                        'id' => $kb->id,
                        'master_mata_pelajaran_id' => $kb->master_mata_pelajaran_id,
                        'nama_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->nama_mapel : 'N/A',
                        'kode_mapel' => $kb->mataPelajaran ? $kb->mataPelajaran->kode_mapel : 'N/A',
                        'bobot_persen' => round($kb->bobot_persen, 2),
                    ];
                }),
            ];
        });
        $formattedMenus = $classes;

        // Ambil data pilihan yang sudah disubmit oleh siswa (jika sudah ada)
        $pendaftaran = PendaftaranPilihan::with([
            'detailPendaftaran.paketMenuPilihan' => function ($q) {
                $q->with('kriteriaBobots.mataPelajaran');
            },
            'periodePendaftaran',
        ])
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periode->id)
            ->first();

        $data = array_merge($periode->toArray(), [
            'is_buka' => $isBuka,
            'classes' => $classes,
            'formattedMenus' => $formattedMenus,
            'pendaftaran' => $pendaftaran,
        ]);

        $responseData = [
            'success' => true,
            'message' => 'Berhasil mengambil form input kelas dan daftar kelas periode pendaftaran.',
            'periode' => $periode,
            'is_buka' => $isBuka,
            'classes' => $classes,
            'formattedMenus' => $formattedMenus,
            'pendaftaran' => $pendaftaran,
            'data' => $data,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($responseData);
        }

        if (ViewFacade::exists('siswa.periode.show')) {
            return view('siswa.periode.show', compact('periode', 'isBuka', 'classes', 'formattedMenus', 'pendaftaran'));
        }

        if (ViewFacade::exists('siswa.pilih-kelas.index')) {
            return view('siswa.pilih-kelas.index', compact('periode', 'isBuka', 'classes', 'formattedMenus', 'pendaftaran'));
        }

        return response()->json($responseData);
    }

    /**
     * Menyimpan formulir pemilihan kelas/paket siswa untuk periode yang ditentukan.
     */
    public function store(Request $request, ?string $id = null)
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

        $now = now();
        $targetPeriodeId = $id ?: $request->input('periode_id', $request->input('periode_pendaftaran_id'));

        if ($targetPeriodeId) {
            $periode = PeriodePendaftaran::where('id', $targetPeriodeId)->first();
        } else {
            $periode = PeriodePendaftaran::where('is_active', true)
                ->where('tanggal_buka', '<=', $now)
                ->where('tanggal_tutup', '>=', $now)
                ->orderByDesc('tanggal_buka')
                ->first();
        }

        if (! $periode) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Periode pendaftaran tidak ditemukan atau tidak tersedia.',
                ], 404);
            }

            return redirect()->back()->with('error', 'Periode pendaftaran tidak ditemukan atau tidak tersedia.');
        }

        // Cek apakah periode masih aktif dan dalam rentang tanggal buka - tutup
        $isBuka = $periode->is_active &&
            $periode->tanggal_buka &&
            $periode->tanggal_tutup &&
            $now->gte($periode->tanggal_buka) &&
            $now->lte($periode->tanggal_tutup);

        if (! $isBuka) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pendaftaran ditolak. Periode pendaftaran tidak aktif atau telah ditutup.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Pendaftaran ditolak. Periode pendaftaran tidak aktif atau telah ditutup.');
        }

        // Ekstraksi pilihan kelas / paket dari form
        $rawPilihan = $request->input('pilihan');
        $pilihanIds = [];

        if (is_array($rawPilihan)) {
            $pilihanIds = array_values(array_filter($rawPilihan));
        } elseif (is_string($rawPilihan) && ! empty($rawPilihan)) {
            $pilihanIds = [$rawPilihan];
        } else {
            foreach (['kelas_id', 'paket_id', 'paket_menu_pilihan_id'] as $field) {
                if ($request->filled($field)) {
                    $val = $request->input($field);
                    $pilihanIds = is_array($val) ? array_values(array_filter($val)) : [$val];
                    break;
                }
            }

            if (empty($pilihanIds)) {
                for ($i = 1; $i <= 5; $i++) {
                    $val = $request->input("pilihan_{$i}") ?? $request->input("pilihan_kelas_{$i}");
                    if (! empty($val)) {
                        $pilihanIds[] = $val;
                    }
                }
            }
        }

        $maxPilihan = $periode->max_pilihan_siswa ?? 3;

        if (empty($pilihanIds)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilihan kelas / paket menu wajib diisi.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Pilihan kelas / paket menu wajib diisi.');
        }

        if (count($pilihanIds) > $maxPilihan) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Jumlah pilihan kelas melebihi batas maksimal ({$maxPilihan} pilihan).",
                ], 422);
            }

            return redirect()->back()->with('error', "Jumlah pilihan kelas melebihi batas maksimal ({$maxPilihan} pilihan).");
        }

        if (count($pilihanIds) !== count(array_unique($pilihanIds))) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilihan kelas tidak boleh ada yang duplikat / sama.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Pilihan kelas tidak boleh ada yang duplikat / sama.');
        }

        // Validasi keaktifan kelas/paket menu yang dipilih
        $activePackagesCount = PaketMenuPilihan::whereIn('id', $pilihanIds)
            ->where('is_active', true)
            ->count();

        if ($activePackagesCount !== count($pilihanIds)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salah satu kelas / paket menu yang dipilih tidak aktif atau tidak ditemukan.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Salah satu kelas / paket menu yang dipilih tidak aktif atau tidak ditemukan.');
        }

        // Cek pendaftaran sebelumnya pada periode ini
        $existingPendaftaran = PendaftaranPilihan::with('detailPendaftaran')
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periode->id)
            ->first();

        if ($existingPendaftaran) {

            $pendaftaran = DB::transaction(function () use ($existingPendaftaran, $pilihanIds) {
                foreach ($existingPendaftaran->detailPendaftaran as $detail) {
                    PaketMenuPilihan::where('id', $detail->paket_menu_pilihan_id)->decrement('kuota_terisi');
                }

                DetailPendaftaranPilihan::where('pendaftaran_pilihan_id', $existingPendaftaran->id)->delete();

                foreach ($pilihanIds as $index => $paketId) {
                    DetailPendaftaranPilihan::create([
                        'id' => (string) Str::uuid(),
                        'pendaftaran_pilihan_id' => $existingPendaftaran->id,
                        'paket_menu_pilihan_id' => $paketId,
                        'urutan_pilihan' => $index + 1,
                    ]);

                    PaketMenuPilihan::where('id', $paketId)->increment('kuota_terisi');
                }

                $existingPendaftaran->update([
                    'tanggal_submit' => now(),
                ]);

                return $existingPendaftaran;
            });

            $message = 'Pilihan kelas pada periode ini berhasil diperbarui.';
        } else {
            $pendaftaran = DB::transaction(function () use ($siswa, $periode, $pilihanIds) {
                $pendaftaranRecord = PendaftaranPilihan::create([
                    'id' => (string) Str::uuid(),
                    'siswa_id' => $siswa->id,
                    'periode_pendaftaran_id' => $periode->id,
                    'tanggal_submit' => now(),
                ]);

                foreach ($pilihanIds as $index => $paketId) {
                    DetailPendaftaranPilihan::create([
                        'id' => (string) Str::uuid(),
                        'pendaftaran_pilihan_id' => $pendaftaranRecord->id,
                        'paket_menu_pilihan_id' => $paketId,
                        'urutan_pilihan' => $index + 1,
                    ]);

                    PaketMenuPilihan::where('id', $paketId)->increment('kuota_terisi');
                }

                return $pendaftaranRecord;
            });

            $message = 'Pilihan kelas untuk periode ini berhasil disimpan.';
        }

        $pendaftaran->load([
            'detailPendaftaran.paketMenuPilihan',
            'periodePendaftaran',
        ]);

        RunPlacementJob::dispatch($periode->id);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => $message,
            'data' => $pendaftaran,
        ], $existingPendaftaran ? 200 : 201);
    }

    /**
     * Memperbarui formulir pemilihan kelas/paket siswa untuk periode yang ditentukan.
     */
    public function update(Request $request, ?string $id = null)
    {
        return $this->store($request, $id);
    }
}
