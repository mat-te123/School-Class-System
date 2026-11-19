<?php

namespace App\Http\Controllers;

use App\Models\PeriodePendaftaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiswaDashboardController extends Controller
{
    /**
     * Dashboard siswa: mengambil data profil dan periode aktif, kemudian merender view atau response JSON.
     */
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $siswa = Auth::guard('siswa')->user();

        if (! $siswa) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated / Akses ditolak.',
                ], 401);
            }

            return redirect('/login/siswa');
        }

        // 1. Ambil data profil lengkap dan relasi kelas asal
        $siswa->load('kelasAsalRelation');

        // 2. Ambil data periode aktif saat ini (hanya jika tanggal_buka <= now <= tanggal_tutup)
        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        $isperiodeAktif = $periodeAktif !== null;

        if (! $isperiodeAktif) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada periode pendaftaran yang aktif saat ini.',
                ], 400);
            }

            return view('siswa.dashboard.index', compact('siswa', 'isperiodeAktif'));
        }

        $isPertukaranAvailable = $periodeAktif->tanggal_mulai_pertukaran != null && $periodeAktif->tanggal_selesai_pertukaran != null;
        $isFinalAndPublished = $periodeAktif->status_pengumuman == 'AKTIF' && $periodeAktif->is_hasil_final;

        if ($isPertukaranAvailable && $isFinalAndPublished) {
            if ($now->between($periodeAktif->tanggal_mulai_pertukaran, $periodeAktif->tanggal_selesai_pertukaran)) {
                $pertukaranDate = $periodeAktif->select('tanggal_mulai_pertukaran', 'tanggal_selesai_pertukaran')->first();
            } else {
                $pertukaranDate = null;
            }
        } else {
            $pertukaranDate = null;
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'siswa' => $siswa,
                    'Pertukaran_date' => $pertukaranDate,
                    'periode_aktif' => $isperiodeAktif,
                ],
            ]);
        }

        return view('siswa.dashboard.index', compact('siswa', 'isperiodeAktif', 'pertukaranDate'));
    }

    /**
     * Alias method untuk dashboard.
     */
    public function dashboard(Request $request): View|JsonResponse|RedirectResponse
    {
        return $this->index($request);
    }
}
