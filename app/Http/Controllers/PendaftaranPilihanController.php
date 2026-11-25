<?php

namespace App\Http\Controllers;

use App\Jobs\RunPlacementJob;
use App\Models\DetailPendaftaranPilihan;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PendaftaranPilihanController extends Controller
{
    /**
     * FR-40: Admin melihat daftar seluruh pengajuan pilihan siswa.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PendaftaranPilihan::with([
            'siswa',
            'periodePendaftaran',
            'detailPendaftaran.paketMenuPilihan',
            'peninjau',
        ]);

        if (! empty($validated['search'])) {
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

        return view('auth.pendaftaran-pilihan.index', compact('pendaftaran'));
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
     * FR-44: Admin mengunduh dokumen wali dari suatu pengajuan.
     */
    public function downloadDokumen(string $id): StreamedResponse|JsonResponse
    {
        $pendaftaran = PendaftaranPilihan::findOrFail($id);

        if (! $pendaftaran->dokumen_wali_path) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen wali belum diunggah oleh siswa.',
            ], 404);
        }

        if (! Storage::disk('public')->exists($pendaftaran->dokumen_wali_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File dokumen tidak ditemukan di penyimpanan.',
            ], 404);
        }

        $filename = 'dokumen_wali_'.$pendaftaran->siswa->nisn
            .'.'.pathinfo($pendaftaran->dokumen_wali_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($pendaftaran->dokumen_wali_path, $filename);
    }

    /**
     * FR-20: Admin melihat daftar siswa yang sudah dan belum mengisi pilihan kelas.
     *
     * @return JsonResponse|View
     */
    public function statusPilihan(Request $request)
    {
        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'search' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Siswa::with('kelasAsalRelation')
            ->whereNull('deleted_at')
            ->where('is_active', true);

        if (! empty($validated['kelas_asal_id'])) {
            $query->where('kelas_asal_id', $validated['kelas_asal_id']);
        }

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $siswa = $query->orderBy('nama_lengkap')
            ->paginate((int) $request->input('per_page', 20));

        $siswaIds = $siswa->pluck('id');

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
                'has_submitted' => ! is_null($sub),
                'submission' => $sub ? [
                    'id' => $sub->id,
                    'tanggal_submit' => $sub->tanggal_submit,
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
     * @return JsonResponse|View
     */
    public function prioritasSiswa(string $siswaId, Request $request)
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
                    'id' => $p->id,
                    'periode' => [
                        'id' => $p->periodePendaftaran?->id,
                        'nama_periode' => $p->periodePendaftaran?->nama_periode,
                        'tahun_ajaran' => $p->periodePendaftaran?->tahun_ajaran,
                        'gelombang' => $p->periodePendaftaran?->gelombang,
                    ],
                    'tanggal_submit' => $p->tanggal_submit,
                    'dokumen_wali_path' => $p->dokumen_wali_path,
                    'ditinjau_oleh' => $p->peninjau?->name ?? $p->peninjau?->username,
                    'tanggal_tinjauan' => $p->tanggal_tinjauan,
                    'pilihan_prioritas' => $p->detailPendaftaran->sortBy('urutan_pilihan')->values()->map(function ($d) {
                        return [
                            'urutan_pilihan' => $d->urutan_pilihan,
                            'paket_menu' => [
                                'id' => $d->paketMenuPilihan?->id,
                                'nama_menu' => $d->paketMenuPilihan?->nama_menu,
                                'rumpun' => $d->paketMenuPilihan?->rumpun,
                                'kuota' => $d->paketMenuPilihan?->kuota_kapasitas,
                            ],
                        ];
                    }),
                ];
            });

        $data = [
            'siswa' => [
                'id' => $siswa->id,
                'nisn' => $siswa->nisn,
                'nis' => $siswa->nis,
                'nama_lengkap' => $siswa->nama_lengkap,
                'kelas_asal' => $siswa->kelasAsalRelation?->nama_kelas,
                'jenis_kelamin' => $siswa->jenis_kelamin,
                'angkatan' => $siswa->angkatan,
            ],
            'riwayat_pendaftaran' => $pendaftaranHistory,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('admin-pendaftaran-review.prioritas-siswa', compact('data'));
    }

    /**
     * FR-52: Siswa melihat status/pilihan yang sudah diambil pada periode berjalan.
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

        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        $periodeAktif ??= PeriodePendaftaran::where('is_active', true)->pengumumanDibuka()->latest('tanggal_tutup')->first();

        if (! $periodeAktif) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada periode pendaftaran yang sedang berjalan saat ini.',
                    'data' => null,
                ]);
            }

            return view('siswa.pilih-kelas.index', [
                'pendaftaran' => null, 'kelasTersedia' => collect(), 'siswaMaxChoose' => 0,
                'isBuka' => false, 'periode' => null,
                'message' => 'Tidak ada periode pendaftaran yang sedang berjalan saat ini.',
            ]);
        }

        $pendaftaran = PendaftaranPilihan::with([
            'detailPendaftaran.paketMenuPilihan' => function ($q) {
                $q->with('kriteriaBobots.mataPelajaran');
            },
            'periodePendaftaran',
        ])
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();

        $kelasTersedia = PaketMenuPilihan::with('kriteriaBobots.mataPelajaran')
            ->where('is_active', true)
            ->orderBy('rumpun')
            ->orderBy('nama_menu')
            ->get();

        $siswaMaxChoose = $periodeAktif->max_pilihan_siswa;
        $isBuka = ! $periodeAktif->isPengumumanDibuka();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mendapatkan data pendaftaran pilihan siswa.',
                'data' => $pendaftaran,
                'kelas_tersedia' => $kelasTersedia,
            ]);
        }

        $periode = $periodeAktif;

        return view('siswa.pilih-kelas.index', compact('pendaftaran', 'kelasTersedia', 'siswaMaxChoose', 'isBuka', 'periode'));
    }

    /**
     * Siswa melihat form input pilihan kelas untuk periode tertentu.
     */
    public function siswaShow(Request $request, string $id)
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

        $periode = PeriodePendaftaran::where('id', $id)->firstOrFail();

        $now = now();
        $isBuka = ($periode->is_active && ! $periode->isPengumumanDibuka() && $periode->tanggal_buka && $periode->tanggal_tutup)
            ? ($now->gte($periode->tanggal_buka) && $now->lte($periode->tanggal_tutup))
            : false;

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

        if (ViewFacade::exists('siswa.pilih-kelas.index')) {
            $kelasTersedia = $paketMenus;
            $siswaMaxChoose = $periode->max_pilihan_siswa;

            return view('siswa.pilih-kelas.index', compact('periode', 'isBuka', 'kelasTersedia', 'siswaMaxChoose', 'pendaftaran'));
        }

        return response()->json($responseData);
    }

    /**
     * FR-52: Siswa memilih paket prioritas (mendukung route /siswa/pilih-kelas maupun /siswa/periode/{id}/pilih-kelas).
     */
    public function store(Request $request, ?string $id = null)
    {
        if ($id !== null) {
            return $this->storeForPeriode($request, $id);
        }

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
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        if (! $periodeAktif) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pendaftaran ditolak. Tidak ada periode pendaftaran aktif yang sedang berjalan.',
                ], 422);
            }
            abort(422, 'Pendaftaran ditolak. Tidak ada periode pendaftaran aktif yang sedang berjalan.');
        }

        if ($periodeAktif->isPengumumanDibuka()) {
            return $this->publishedChoicesResponse($request);
        }

        $existingPendaftaran = PendaftaranPilihan::where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();

        if ($existingPendaftaran) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah pernah mengirimkan pilihan paket pada periode ini.',
                ], 409);
            }

            return redirect()->route('siswa.dashboard')
                ->withErrors(['pilihan' => 'Anda sudah pernah mengirimkan pilihan paket pada periode ini.'])
                ->withInput();
        }

        $maxPilihan = $periodeAktif->max_pilihan_siswa ?? 3;

        $validated = $request->validate([
            'pilihan' => ['required', 'array', "size:{$maxPilihan}"],
            'pilihan.*' => ['required', 'uuid', 'exists:paket_menu_pilihan,id'],
        ], [
            'pilihan.required' => 'Pilihan paket menu wajib diisi.',
            'pilihan.array' => 'Format pilihan paket menu harus berupa array.',
            'pilihan.size' => "Anda wajib memilih tepat {$maxPilihan} paket menu prioritas.",
            'pilihan.*.required' => 'Paket menu prioritas tidak boleh kosong.',
            'pilihan.*.uuid' => 'ID paket menu harus berupa UUID valid.',
            'pilihan.*.exists' => 'Salah satu paket menu pilihan tidak ditemukan di sistem.',
        ]);

        $pilihanIds = $validated['pilihan'];

        if (count($pilihanIds) !== count(array_unique($pilihanIds))) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilihan paket menu prioritas tidak boleh ada yang duplikat / sama.',
                ], 422);
            }

            return back()->withErrors(['pilihan' => 'Pilihan paket menu prioritas tidak boleh ada yang duplikat / sama.'])->withInput();
        }

        $activePackagesCount = PaketMenuPilihan::whereIn('id', $pilihanIds)
            ->where('is_active', true)
            ->count();

        if ($activePackagesCount !== count($pilihanIds)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salah satu paket menu yang Anda pilih tidak aktif.',
                ], 422);
            }
            abort(422, 'Salah satu paket menu yang Anda pilih tidak aktif.');
        }

        $pendaftaran = DB::transaction(function () use ($siswa, $periodeAktif, $pilihanIds) {
            $pendaftaranRecord = PendaftaranPilihan::create([
                'id' => (string) Str::uuid(),
                'siswa_id' => $siswa->id,
                'periode_pendaftaran_id' => $periodeAktif->id,
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

        $pendaftaran->load([
            'detailPendaftaran.paketMenuPilihan',
            'periodePendaftaran',
        ]);

        RunPlacementJob::dispatch($pendaftaran->periode_pendaftaran_id);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil menyimpan 3 paket menu pilihan prioritas Anda.',
            'data' => $pendaftaran,
        ], 201);
    }

    /**
     * FR-53: Siswa mengubah paket prioritas pada periode pendaftaran yang masih buka.
     */
    public function update(Request $request, ?string $id = null)
    {
        if ($id !== null) {
            return $this->storeForPeriode($request, $id);
        }

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
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        if (! $periodeAktif) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pendaftaran ditolak. Tidak ada periode pendaftaran aktif yang sedang berjalan.',
                ], 422);
            }
            abort(422, 'Pendaftaran ditolak. Tidak ada periode pendaftaran aktif yang sedang berjalan.');
        }

        if ($periodeAktif->isPengumumanDibuka()) {
            return $this->publishedChoicesResponse($request);
        }

        $pendaftaran = PendaftaranPilihan::with('detailPendaftaran')
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();

        if (! $pendaftaran) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum ada pengajuan pilihan untuk periode ini. Silakan submit terlebih dahulu.',
                ], 404);
            }
            abort(404, 'Belum ada pengajuan pilihan untuk periode ini. Silakan submit terlebih dahulu.');
        }

        $maxPilihan = $periodeAktif->max_pilihan_siswa ?? 3;

        $validated = $request->validate([
            'pilihan' => ['required', 'array', "size:{$maxPilihan}"],
            'pilihan.*' => ['required', 'uuid', 'exists:paket_menu_pilihan,id'],
        ], [
            'pilihan.required' => 'Pilihan paket menu wajib diisi.',
            'pilihan.array' => 'Format pilihan paket menu harus berupa array.',
            'pilihan.size' => "Anda wajib memilih tepat {$maxPilihan} paket menu prioritas.",
            'pilihan.*.required' => 'Paket menu prioritas tidak boleh kosong.',
            'pilihan.*.uuid' => 'ID paket menu harus berupa UUID valid.',
            'pilihan.*.exists' => 'Salah satu paket menu pilihan tidak ditemukan di sistem.',
        ]);

        $pilihanIds = $validated['pilihan'];

        if (count($pilihanIds) !== count(array_unique($pilihanIds))) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilihan paket menu prioritas tidak boleh ada yang duplikat / sama.',
                ], 422);
            }

            return back()->withErrors(['pilihan' => 'Pilihan paket menu prioritas tidak boleh ada yang duplikat / sama.'])->withInput();
        }

        $activePackagesCount = PaketMenuPilihan::whereIn('id', $pilihanIds)
            ->where('is_active', true)
            ->count();

        if ($activePackagesCount !== count($pilihanIds)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salah satu paket menu yang Anda pilih tidak aktif.',
                ], 422);
            }
            abort(422, 'Salah satu paket menu yang Anda pilih tidak aktif.');
        }

        $existingIds = $pendaftaran->detailPendaftaran
            ->sortBy('urutan_pilihan')
            ->pluck('paket_menu_pilihan_id')
            ->values()
            ->all();

        if ($existingIds === $pilihanIds) {
            return $this->handleWriteResponse($request, [
                'success' => true,
                'message' => 'Tidak ada perubahan pada pilihan kelas.',
                'data' => $pendaftaran,
            ]);
        }

        DB::transaction(function () use ($pendaftaran, $pilihanIds) {
            foreach ($pendaftaran->detailPendaftaran as $detail) {
                PaketMenuPilihan::where('id', $detail->paket_menu_pilihan_id)->decrement('kuota_terisi');
                $detail->delete();
            }

            foreach ($pilihanIds as $index => $paketId) {
                DetailPendaftaranPilihan::create([
                    'id' => (string) Str::uuid(),
                    'pendaftaran_pilihan_id' => $pendaftaran->id,
                    'paket_menu_pilihan_id' => $paketId,
                    'urutan_pilihan' => $index + 1,
                ]);

                PaketMenuPilihan::where('id', $paketId)->increment('kuota_terisi');
            }
        });

        $pendaftaran->load([
            'detailPendaftaran.paketMenuPilihan',
            'periodePendaftaran',
        ]);

        RunPlacementJob::dispatch($pendaftaran->periode_pendaftaran_id);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Pilihan paket prioritas Anda berhasil diperbarui.',
            'data' => $pendaftaran,
        ]);
    }

    /**
     * FR-58: Siswa upload dokumen wali pada pengajuan yang sedang menunggu.
     */
    public function uploadDokumen(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            abort(401, 'Unauthenticated.');
        }

        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        if (! $periodeAktif) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Tidak ada periode pendaftaran yang sedang aktif.'], 422);
            }
            abort(422, 'Tidak ada periode pendaftaran yang sedang aktif.');
        }

        $pendaftaran = PendaftaranPilihan::where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();

        if (! $pendaftaran) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Belum ada pengajuan pilihan untuk periode ini.'], 404);
            }
            abort(404, 'Belum ada pengajuan pilihan untuk periode ini.');
        }

        $request->validate([
            'dokumen_wali' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $path = $request->file('dokumen_wali')
            ->store("dokumen_wali/{$siswa->id}", 'public');

        $pendaftaran->update(['dokumen_wali_path' => $path]);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Dokumen wali berhasil diunggah.',
            'data' => ['dokumen_wali_path' => $path],
        ]);
    }

    /**
     * FR-59: Siswa batalkan pengajuan (dalam periode pertukaran aktif).
     */
    public function destroy(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            abort(401, 'Unauthenticated.');
        }

        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_mulai_pertukaran', '<=', $now)
            ->where('tanggal_selesai_pertukaran', '>=', $now)
            ->first();

        if (! $periodeAktif) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembatalan hanya dapat dilakukan dalam periode pertukaran aktif.',
                ], 422);
            }
            abort(422, 'Pembatalan hanya dapat dilakukan dalam periode pertukaran aktif.');
        }

        $pendaftaran = PendaftaranPilihan::with('detailPendaftaran')
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();

        if (! $pendaftaran) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Tidak ada pengajuan yang ditemukan.'], 404);
            }
            abort(404, 'Tidak ada pengajuan yang ditemukan.');
        }

        DB::transaction(function () use ($pendaftaran) {
            foreach ($pendaftaran->detailPendaftaran as $detail) {
                PaketMenuPilihan::where('id', $detail->paket_menu_pilihan_id)->decrement('kuota_terisi');
            }
            $pendaftaran->delete();
        });

        return $this->handleWriteResponse($request, ['success' => true, 'message' => 'Pengajuan berhasil dibatalkan.']);
    }

    private function publishedChoicesResponse(Request $request)
    {
        return $this->handleWriteResponse($request, [
            'success' => false,
            'message' => 'Pilihan kelas tidak dapat diubah setelah hasil penjurusan dipublikasikan.',
        ], 422);
    }

    /**
     * Simpan / perbarui pilihan kelas siswa untuk periode tertentu (/siswa/periode/{id}/pilih-kelas).
     */
    private function storeForPeriode(Request $request, string $id)
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
        $periode = PeriodePendaftaran::where('id', $id)->first();

        if (! $periode) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Periode pendaftaran tidak ditemukan atau tidak tersedia.',
                ], 404);
            }

            return redirect()->back()->with('error', 'Periode pendaftaran tidak ditemukan atau tidak tersedia.');
        }

        if ($periode->isPengumumanDibuka()) {
            return $this->publishedChoicesResponse($request);
        }

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
}
