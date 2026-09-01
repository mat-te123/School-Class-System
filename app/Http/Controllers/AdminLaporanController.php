<?php

namespace App\Http\Controllers;

use App\Models\HasilSeleksi;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLaporanController extends Controller
{
    /**
     * Laporan Hasil Penjurusan Siswa
     */
    public function hasilPenjurusan(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id'    => 'required|uuid|exists:periode_pendaftaran,id',
            'paket_id'      => 'nullable|uuid|exists:paket_menu_pilihan,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'mekanisme'     => 'nullable|string|max:50',
            'search'        => 'nullable|string|max:50',
            'per_page'      => 'nullable|integer|min:1|max:100',
        ]);

        $query = HasilSeleksi::with(['siswa.kelasAsalRelation', 'paketMenuPilihan', 'pengubah'])
            ->whereHas('siswa', function ($q) use ($validated) {
                $q->whereHas('pendaftaranPilihan', function ($pq) use ($validated) {
                    $pq->where('periode_pendaftaran_id', $validated['periode_id']);
                });
            });

        if (!empty($validated['paket_id'])) {
            $query->where('paket_menu_pilihan_id', $validated['paket_id']);
        }

        if (!empty($validated['kelas_asal_id'])) {
            $query->whereHas('siswa', function ($q) use ($validated) {
                $q->where('kelas_asal_id', $validated['kelas_asal_id']);
            });
        }

        if (!empty($validated['mekanisme'])) {
            $query->where('mekanisme', $validated['mekanisme']);
        }

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
                'data'    => $results,
            ]);
        }

        return view('admin-laporan.hasil-penjurusan', compact('results'));
    }

    /**
     * Laporan Pilihan Minat Siswa
     */
    public function minatSiswa(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id'    => 'required|uuid|exists:periode_pendaftaran,id',
            'paket_id'      => 'nullable|uuid|exists:paket_menu_pilihan,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'status'        => 'nullable|string|in:menunggu,disetujui,ditolak',
            'search'        => 'nullable|string|max:50',
            'per_page'      => 'nullable|integer|min:1|max:100',
        ]);

        $query = PendaftaranPilihan::with([
            'siswa.kelasAsalRelation',
            'detailPendaftaran.paketMenuPilihan',
        ])->where('periode_pendaftaran_id', $validated['periode_id']);

        if (!empty($validated['paket_id'])) {
            $query->whereHas('detailPendaftaran', function ($q) use ($validated) {
                $q->where('paket_menu_pilihan_id', $validated['paket_id']);
            });
        }

        if (!empty($validated['kelas_asal_id'])) {
            $query->whereHas('siswa', function ($q) use ($validated) {
                $q->where('kelas_asal_id', $validated['kelas_asal_id']);
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->whereHas('siswa', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $results = $query->latest('tanggal_submit')
            ->paginate((int) $request->input('per_page', 20));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $results,
            ]);
        }

        return view('admin-laporan.minat-siswa', compact('results'));
    }

    /**
     * Rekap Analisis Peminat vs Kuota
     */
    public function peminatVsKuota(Request $request)
    {
        $this->ensureAdmin();

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

            $terisi = HasilSeleksi::whereHas('siswa.pendaftaranPilihan', function ($q) use ($validated) {
                $q->where('periode_pendaftaran_id', $validated['periode_id']);
            })->where('paket_menu_pilihan_id', $paket->id)->count();

            return [
                'id'              => $paket->id,
                'nama_menu'       => $paket->nama_menu,
                'rumpun'          => $paket->rumpun,
                'pilihan_1'       => $pilihan1,
                'pilihan_2'       => $pilihan2,
                'pilihan_3'       => $pilihan3,
                'total_peminat'   => $pilihan1 + $pilihan2 + $pilihan3,
                'kuota_kapasitas' => $paket->kuota_kapasitas,
                'terisi'          => $terisi,
                'sisa_kuota'      => max(0, $paket->kuota_kapasitas - $terisi),
            ];
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data'    => $rekap,
            ]);
        }

        return view('admin-laporan.peminat-vs-kuota', compact('rekap'));
    }

    /**
     * Ekspor Hasil Penjurusan (XLSX/CSV & PDF)
     */
    public function exportHasilPenjurusan(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id'    => 'required|uuid|exists:periode_pendaftaran,id',
            'paket_id'      => 'nullable|uuid|exists:paket_menu_pilihan,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'mekanisme'     => 'nullable|string|max:50',
            'format'        => 'required|string|in:xlsx,csv,pdf',
        ]);

        $query = HasilSeleksi::with(['siswa.kelasAsalRelation', 'paketMenuPilihan'])
            ->whereHas('siswa', function ($q) use ($validated) {
                $q->whereHas('pendaftaranPilihan', function ($pq) use ($validated) {
                    $pq->where('periode_pendaftaran_id', $validated['periode_id']);
                });
            });

        if (!empty($validated['paket_id'])) {
            $query->where('paket_menu_pilihan_id', $validated['paket_id']);
        }

        if (!empty($validated['kelas_asal_id'])) {
            $query->whereHas('siswa', function ($q) use ($validated) {
                $q->where('kelas_asal_id', $validated['kelas_asal_id']);
            });
        }

        if (!empty($validated['mekanisme'])) {
            $query->where('mekanisme', $validated['mekanisme']);
        }

        $data = $query->orderByDesc('skor_penempatan')->get();

        if ($validated['format'] === 'pdf') {
            return $this->renderPdfResponse('Laporan Hasil Penjurusan', 'admin-laporan.pdf-hasil', compact('data'));
        }

        return $this->renderCsvResponse('laporan_hasil_penjurusan_' . date('Ymd_His') . '.csv', [
            'NISN', 'Nama Lengkap', 'Kelas Asal', 'Paket Penempatan', 'Skor Penempatan', 'Rata-Rata 6 Mapel', 'Mekanisme', 'Status Override'
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
        $this->ensureAdmin();

        $validated = $request->validate([
            'periode_id'    => 'required|uuid|exists:periode_pendaftaran,id',
            'paket_id'      => 'nullable|uuid|exists:paket_menu_pilihan,id',
            'kelas_asal_id' => 'nullable|uuid|exists:kelas_asal,id',
            'status'        => 'nullable|string|in:menunggu,disetujui,ditolak',
            'format'        => 'required|string|in:xlsx,csv,pdf',
        ]);

        $query = PendaftaranPilihan::with([
            'siswa.kelasAsalRelation',
            'detailPendaftaran.paketMenuPilihan',
        ])->where('periode_pendaftaran_id', $validated['periode_id']);

        if (!empty($validated['paket_id'])) {
            $query->whereHas('detailPendaftaran', function ($q) use ($validated) {
                $q->where('paket_menu_pilihan_id', $validated['paket_id']);
            });
        }

        if (!empty($validated['kelas_asal_id'])) {
            $query->whereHas('siswa', function ($q) use ($validated) {
                $q->where('kelas_asal_id', $validated['kelas_asal_id']);
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $data = $query->latest('tanggal_submit')->get();

        if ($validated['format'] === 'pdf') {
            return $this->renderPdfResponse('Laporan Minat Siswa', 'admin-laporan.pdf-minat', compact('data'));
        }

        return $this->renderCsvResponse('laporan_minat_siswa_' . date('Ymd_His') . '.csv', [
            'NISN', 'Nama Lengkap', 'Kelas Asal', 'Pilihan 1', 'Pilihan 2', 'Pilihan 3', 'Tanggal Submit', 'Status Pendaftaran'
        ], $data->map(function ($row) {
            $pilihan = [];
            foreach ($row->detailPendaftaran as $d) {
                $pilihan[$d->urutan_pilihan] = $d->paketMenuPilihan?->nama_menu;
            }
            return [
                $row->siswa?->nisn,
                $row->siswa?->nama_lengkap,
                $row->siswa?->kelasAsalRelation?->nama_kelas,
                $pilihan[1] ?? '-',
                $pilihan[2] ?? '-',
                $pilihan[3] ?? '-',
                $row->tanggal_submit?->format('Y-m-d H:i:s'),
                ucfirst($row->status),
            ];
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
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function renderPdfResponse(string $title, string $view, array $data)
    {
        // Printable HTML template view response
        $html = view($view, array_merge($data, ['title' => $title]))->render();
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    private function ensureAdmin(): void
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'admin') {
            if (request()->wantsJson() || request()->ajax()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Admin yang dapat mengelola laporan.',
                ], 403));
            }
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengelola laporan.');
        }
    }
}
