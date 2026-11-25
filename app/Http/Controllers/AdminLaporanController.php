<?php

namespace App\Http\Controllers;

use App\Models\HasilSeleksi;
use App\Models\KelasAsal;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminLaporanController extends Controller
{
    /**
     * Laporan Hasil Penjurusan Siswa
     */
    public function hasilPenjurusan(Request $request)
    {
        $this->ensureAdmin('laporan');

        $periodeList = PeriodePendaftaran::orderByDesc('tanggal_tutup')->get(['id', 'nama_periode']);

        $periodeId = $request->filled('periode_id')
        ? $request->input('periode_id')
        : $periodeList->first()?->id;

        $request->merge(['periode_id' => $periodeId]);

        $validated = $request->validate([
            'periode_id' => ['nullable', 'uuid', Rule::exists('periode_pendaftaran', 'id')->withoutTrashed()],
            'paket_id' => 'nullable|uuid|exists:paket_menu_pilihan,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'mekanisme' => 'nullable|string|max:50',
            'search' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = HasilSeleksi::with(['siswa.kelasAsalRelation', 'paketMenuPilihan', 'pengubah'])
            ->where('periode_pendaftaran_id', $periodeId);

        if (! empty($validated['paket_id'])) {
            $query->where('paket_menu_pilihan_id', $validated['paket_id']);
        }

        if (! empty($validated['kelas_asal_id'])) {
            $query->whereHas('siswa', function ($q) use ($validated) {
                $q->where('kelas_asal_id', $validated['kelas_asal_id']);
            });
        }

        if (! empty($validated['mekanisme'])) {
            $query->where('mekanisme', $validated['mekanisme']);
        }

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->whereRaw('LOWER(nama_lengkap) LIKE ?', ["%{$search}%"])
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

        $periodePaketList = $periodeId === null ? collect() : PaketMenuPilihan::getKelasNameByPeriod($periodeId);

        return view('auth.laporan.hasil-penjurusan.index', compact('results', 'periodeList', 'periodePaketList'));
    }

    /**
     * Laporan Pilihan Minat Siswa
     */
    public function minatSiswa(Request $request)
    {
        $this->ensureAdmin('laporan');

        $periodeList = PeriodePendaftaran::orderByDesc('tanggal_tutup')->get(['id', 'nama_periode', 'max_pilihan_siswa']);

        $periodeId = $request->filled('periode_id')
            ? $request->input('periode_id')
            : $periodeList->first()?->id;

        $request->merge(['periode_id' => $periodeId]);

        $validated = $request->validate([
            'periode_id' => ['nullable', 'uuid', Rule::exists('periode_pendaftaran', 'id')->withoutTrashed()],
            'paket_id' => 'nullable|uuid|exists:paket_menu_pilihan,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'search' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PendaftaranPilihan::with([
            'siswa.kelasAsalRelation',
            'detailPendaftaran.paketMenuPilihan',
        ])->where('periode_pendaftaran_id', $periodeId);

        if (! empty($validated['paket_id'])) {
            $query->whereHas('detailPendaftaran', function ($q) use ($validated) {
                $q->where('paket_menu_pilihan_id', $validated['paket_id']);
            });
        }

        if (! empty($validated['kelas_asal_id'])) {
            $query->whereHas('siswa', function ($q) use ($validated) {
                $q->where('kelas_asal_id', $validated['kelas_asal_id']);
            });
        }

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $results = $query->latest('tanggal_submit')
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        }

        $periodePaketList = $periodeId === null ? collect() : PaketMenuPilihan::getKelasNameByPeriod($periodeId);
        $kelasAsalList = KelasAsal::orderBy('nama_kelas')->get(['id', 'nama_kelas']);
        $selectedPeriode = $periodeList->firstWhere('id', $periodeId);

        return view('auth.laporan.pemantauan-kelas.index', compact(
            'results',
            'periodeList',
            'periodePaketList',
            'kelasAsalList',
            'selectedPeriode'
        ));
    }

    /**
     * Rekap Analisis Peminat vs Kuota
     */
    public function peminatVsKuota(Request $request)
    {
        $this->ensureAdmin('laporan');

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
        ]);

        $rekap = PaketMenuPilihan::where('is_active', true)->get()->map(function ($paket) use ($validated) {
            // Count selections per priority
            $pilihan1 = PendaftaranPilihan::where('periode_pendaftaran_id', $validated['periode_id'])
                ->whereHas('detailPendaftaran', function ($q) use ($paket) {
                    $q->where('paket_menu_pilihan_id', $paket->id)->where('urutan_pilihan', 1);
                })->count();

            $pilihan2 = PendaftaranPilihan::where('periode_pendaftaran_id', $validated['periode_id'])
                ->whereHas('detailPendaftaran', function ($q) use ($paket) {
                    $q->where('paket_menu_pilihan_id', $paket->id)->where('urutan_pilihan', 2);
                })->count();

            $pilihan3 = PendaftaranPilihan::where('periode_pendaftaran_id', $validated['periode_id'])
                ->whereHas('detailPendaftaran', function ($q) use ($paket) {
                    $q->where('paket_menu_pilihan_id', $paket->id)->where('urutan_pilihan', 3);
                })->count();

            $terisi = HasilSeleksi::where('periode_pendaftaran_id', $validated['periode_id'])
                ->where('paket_menu_pilihan_id', $paket->id)->count();

            return [
                'id' => $paket->id,
                'nama_menu' => $paket->nama_menu,
                'rumpun' => $paket->rumpun,
                'pilihan_1' => $pilihan1,
                'pilihan_2' => $pilihan2,
                'pilihan_3' => $pilihan3,
                'total_peminat' => $pilihan1 + $pilihan2 + $pilihan3,
                'kuota_kapasitas' => $paket->kuota_kapasitas,
                'terisi' => $terisi,
                'sisa_kuota' => max(0, $paket->kuota_kapasitas - $terisi),
            ];
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $rekap,
            ]);
        }

        return view('admin-laporan.peminat-vs-kuota', compact('rekap'));
    }

    /**
     * Ekspor Hasil Penjurusan (XLSX/CSV & PDF)
     */
    public function exportHasilPenjurusan(Request $request)
    {
        $this->ensureAdmin('laporan');

        $periodeId = $request->filled('periode_id')
        ? $request->input('periode_id')
        : PeriodePendaftaran::latest('tanggal_tutup')->value('id');

        $request->merge(['periode_id' => $periodeId]);

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
            'paket_id' => 'nullable|uuid|exists:paket_menu_pilihan,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'mekanisme' => 'nullable|string|max:50',
            'format' => 'required|string|in:xlsx,csv,pdf',
        ]);

        $query = HasilSeleksi::with(['siswa.kelasAsalRelation', 'paketMenuPilihan'])
            ->where('periode_pendaftaran_id', $validated['periode_id']);

        if (! empty($validated['paket_id'])) {
            $query->where('paket_menu_pilihan_id', $validated['paket_id']);
        }

        if (! empty($validated['kelas_asal_id'])) {
            $query->whereHas('siswa', function ($q) use ($validated) {
                $q->where('kelas_asal_id', $validated['kelas_asal_id']);
            });
        }

        if (! empty($validated['mekanisme'])) {
            $query->where('mekanisme', $validated['mekanisme']);
        }

        if (! empty($validated['search'])) {
            $search = mb_strtolower(trim($validated['search']));
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->whereRaw('LOWER(nama_lengkap) like ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(nisn) like ?', ["%{$search}%"]);
            });
        }

        $data = $query->orderByDesc('skor_penempatan')->get();

        if ($validated['format'] === 'pdf') {
            return $this->renderPdfResponse('Laporan Hasil Penjurusan', 'auth.laporan.hasil-penjurusan.pdf.admin-laporan-pdf-hasil', compact('data'));
        }

        return $this->renderCsvResponse('laporan_hasil_penjurusan_'.date('Ymd_His').'.csv', [
            'NISN', 'Nama Lengkap', 'Kelas Asal', 'Paket Penempatan', 'Skor Penempatan', 'Rata-Rata 6 Mapel', 'Mekanisme', 'Status Override',
        ], $data->map(function ($row) {
            return [
                $row->siswa?->nisn,
                $row->siswa?->nama_lengkap,
                $row->siswa?->kelasAsalRelation?->nama_kelas,
                $row->paketMenuPilihan?->nama_menu,
                $row->skor_penempatan,
                $row->rata_6_mapel,
                $row->mekanisme,
                $row->is_manual_override ? 'Ya' : 'Tidak',
            ];
        })->toArray());
    }

    /**
     * Ekspor Laporan Minat Siswa (XLSX/CSV & PDF)
     */
    public function exportMinatSiswa(Request $request)
    {
        $this->ensureAdmin('laporan');

        $periodeId = $request->filled('periode_id')
        ? $request->input('periode_id')
        : PeriodePendaftaran::latest('tanggal_tutup')->value('id');

        $request->merge(['periode_id' => $periodeId]);

        $validated = $request->validate([
            'periode_id' => 'required|uuid|exists:periode_pendaftaran,id',
            'paket_id' => 'nullable|uuid|exists:paket_menu_pilihan,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'search' => 'nullable|string|max:50',
            'format' => 'required|string|in:xlsx,csv,pdf',
        ]);

        $query = PendaftaranPilihan::with([
            'siswa.kelasAsalRelation',
            'detailPendaftaran.paketMenuPilihan',
        ])->where('periode_pendaftaran_id', $validated['periode_id']);

        if (! empty($validated['paket_id'])) {
            $query->whereHas('detailPendaftaran', function ($q) use ($validated) {
                $q->where('paket_menu_pilihan_id', $validated['paket_id']);
            });
        }

        if (! empty($validated['kelas_asal_id'])) {
            $query->whereHas('siswa', function ($q) use ($validated) {
                $q->where('kelas_asal_id', $validated['kelas_asal_id']);
            });
        }

        if (! empty($validated['search'])) {
            $search = mb_strtolower(trim($validated['search']));
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->whereRaw('LOWER(nama_lengkap) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(nisn) LIKE ?', ["%{$search}%"]);
            });
        }

        $data = $query->latest('tanggal_submit')->get();
        $maxPilihan = (int) PeriodePendaftaran::whereKey($validated['periode_id'])
            ->value('max_pilihan_siswa');

        if ($validated['format'] === 'pdf') {
            return $this->renderPdfResponse(
                'Laporan Minat Siswa',
                'auth.laporan.pemantauan-kelas.pdf.admin-laporan-pdf-minat',
                compact('data', 'maxPilihan')
            );
        }

        $headers = ['NISN', 'Nama Lengkap', 'Kelas Asal'];
        for ($i = 1; $i <= $maxPilihan; $i++) {
            $headers[] = 'Pilihan '.$i;
        }
        $headers = array_merge($headers, ['Tanggal Submit', 'Status Pendaftaran']);

        return $this->renderCsvResponse('laporan_minat_siswa_'.date('Ymd_His').'.csv', $headers, $data->map(function ($row) use ($maxPilihan) {
            $pilihan = [];
            foreach ($row->detailPendaftaran as $d) {
                $pilihan[$d->urutan_pilihan] = $d->paketMenuPilihan?->nama_menu;
            }

            $values = [
                $row->siswa?->nisn,
                $row->siswa?->nama_lengkap,
                $row->siswa?->kelasAsalRelation?->nama_kelas,
            ];
            for ($i = 1; $i <= $maxPilihan; $i++) {
                $values[] = $pilihan[$i] ?? '-';
            }

            return array_merge($values, [
                $row->tanggal_submit?->format('Y-m-d H:i:s'),
                ucfirst($row->status),
            ]);
        })->toArray());
    }

    private function renderCsvResponse(string $filename, array $headers, array $rows)
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            // Write BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function renderPdfResponse(string $title, string $view, array $data)
    {
        // Printable HTML template view response
        $html = view($view, array_merge($data, ['title' => $title]))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);    }

    /**
     * Pemantauan minat: siswa yang sudah/belum mengisi pilihan kelas.
     */
    public function pemantauanMinat(Request $request)
    {
        $this->ensureAdmin();

        $validate = $request->validate([
            'periode_id' => 'nullable|uuid|exists:periode_pendaftaran,id',
            'search' => 'nullable|string|max:150',
            'status' => 'nullable|in:sudah,belum',
            'kelas_pilihan' => 'nullable|uuid|exists:paket_menu_pilihan,id',
        ]);

        $search = isset($validate['search']) ? mb_strtolower(trim($validate['search'])) : null;
        $status = $validate['status'] ?? 'sudah';
        $kelas = $validate['kelas_pilihan'] ?? null;

        $cari = fn ($q) => $q->when($search, fn ($q, $s) => $q->where(fn ($q) => $q
            ->whereRaw('LOWER(nama_lengkap) like ?', ["%{$s}%"])
            ->orWhereRaw('LOWER(nisn) like ?', ["%{$s}%"])
            ->orWhereRaw('LOWER(nis) like ?', ["%{$s}%"])));

        $periodeId = $validate['periode_id'] ?? null;

        // Jika tidak ada filter, ambil periode aktif
        if (! $periodeId) {
            $activePeriode = PeriodePendaftaran::where('is_active', true)->first();
            if (! $activePeriode) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Tidak ada periode pendaftaran yang aktif saat ini.'], 400);
                }
                abort(400, 'Tidak ada periode pendaftaran yang aktif saat ini.');
            }
            $periodeId = $activePeriode->id;
        }

        $siswaQuery = Siswa::query();

        $siswaSudahMemilih = (clone $siswaQuery)
            ->tap($cari)
            ->whereHas('pendaftaranPilihan', function ($q) use ($periodeId, $kelas) {
                $q->where('periode_pendaftaran_id', $periodeId)
                ->when($kelas, fn ($q, $k) => $q->whereHas('detailPendaftaran', fn ($q) => $q->where('paket_menu_pilihan_id', $k)));
            })
            ->with(['pendaftaranPilihan' => function ($q) use ($periodeId) {
                $q->where('periode_pendaftaran_id', $periodeId)
                    ->with('detailPendaftaran.paketMenuPilihan');
            }])
            ->paginate((int) $request->input('per_page_sudah', 10), ['*'], 'page_sudah')
            ->withQueryString()
            ->appends(['periode_id' => $periodeId]);

        $siswaBelumMemilih = (clone $siswaQuery)
            ->tap($cari)
            ->whereDoesntHave('pendaftaranPilihan', function ($q) use ($periodeId) {
                $q->where('periode_pendaftaran_id', $periodeId);
            })
            ->paginate((int) $request->input('per_page_belum', 10), ['*'], 'page_belum')
            ->withQueryString()
            ->appends(['periode_id' => $periodeId]);

        // Hitung total keseluruhan
        $totalSudah = (clone $siswaQuery)->whereHas('pendaftaranPilihan', function ($q) use ($periodeId) {
            $q->where('periode_pendaftaran_id', $periodeId);
        })->count();

        $totalBelum = (clone $siswaQuery)->whereDoesntHave('pendaftaranPilihan', function ($q) use ($periodeId) {
            $q->where('periode_pendaftaran_id', $periodeId);
        })->count();

        // Rincian peminatan per paket menu
        $detailPilihan = DB::table('detail_pendaftaran_pilihan')
            ->join('pendaftaran_pilihan', 'pendaftaran_pilihan.id', '=', 'detail_pendaftaran_pilihan.pendaftaran_pilihan_id')
            ->join('paket_menu_pilihan', 'paket_menu_pilihan.id', '=', 'detail_pendaftaran_pilihan.paket_menu_pilihan_id')
            ->where('pendaftaran_pilihan.periode_pendaftaran_id', $periodeId)
            ->select('paket_menu_pilihan.id', 'paket_menu_pilihan.nama_menu', DB::raw('count(detail_pendaftaran_pilihan.id) as total'))
            ->groupBy('paket_menu_pilihan.id', 'paket_menu_pilihan.nama_menu')
            ->orderBy('paket_menu_pilihan.nama_menu', 'asc')
            ->get();

        $paketMenu = PaketMenuPilihan::where(function ($q) use ($periodeId) {
            $q->where('periode_id', $periodeId)
                ->orWhereNull('periode_id');
        })->orderBy('nama_menu', 'asc')->get(['id', 'nama_menu']);

        $currentPeriodeSelectionValue = PeriodePendaftaran::where('id', $periodeId)->value('max_pilihan_siswa');

        $summary = [
            'total_siswa' => $totalSudah + $totalBelum,
            'sudah_memilih' => $totalSudah,
            'belum_memilih' => $totalBelum,
            'detail_paket' => $detailPilihan,
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'siswa_sudah_memilih' => $siswaSudahMemilih,
                'siswa_belum_memilih' => $siswaBelumMemilih,
                'paket_menu' => $paketMenu,
                'currentPeriodeSelectionValue' => $currentPeriodeSelectionValue,
            ]);
        }

        return view('auth.pemantauan-kelas.index', compact(
            'summary',
            'siswaSudahMemilih',
            'siswaBelumMemilih',
            'paketMenu',
            'status',
            'periodeId',
            'currentPeriodeSelectionValue'
        ));
    }
}
