<?php

namespace App\Http\Controllers;

use App\Models\PeriodePendaftaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View as ViewFacade;

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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil daftar periode pendaftaran aktif yang sedang berjalan.',
                'data' => $periodes,
            ]);
        }

        if (ViewFacade::exists('siswa.periode.index')) {
            return view('siswa.periode.index', compact('periodes'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar periode pendaftaran aktif yang sedang berjalan.',
            'data' => $periodes,
        ]);
    }

    /**
     * Menampilkan detail periode tertentu untuk siswa.
     */
    public function show(Request $request, string $id)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $periode = PeriodePendaftaran::where('id', $id)
            ->where('is_active', true)
            ->with(['paketMenuPilihans' => function ($q) {
                $q->where('is_active', true)->with('kriteriaBobots.mataPelajaran');
            }])
            ->firstOrFail();

        $now = now();
        $isBuka = ($periode->tanggal_buka && $periode->tanggal_tutup)
            ? ($now->gte($periode->tanggal_buka) && $now->lte($periode->tanggal_tutup))
            : false;

        $data = $periode->toArray();
        $data['is_buka'] = $isBuka;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        if (ViewFacade::exists('siswa.periode.show')) {
            return view('siswa.periode.show', compact('periode', 'isBuka'));
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
