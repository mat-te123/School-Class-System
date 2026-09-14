<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\PeriodePendaftaran;
use App\Models\PaketMenuPilihan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PemantauanMinatController extends Controller
{
    /**
     * Pastikan hanya Admin yang dapat mengakses.
     */
    protected function ensureAdmin()
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses halaman ini.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $periodeId = $request->periode_id;
        
        // Jika tidak ada filter, ambil periode aktif
        if (!$periodeId) {
            $activePeriode = PeriodePendaftaran::where('is_active', true)->first();
            if (!$activePeriode) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Tidak ada periode pendaftaran yang aktif saat ini.'], 400);
                }
                abort(400, 'Tidak ada periode pendaftaran yang aktif saat ini.');
            }
            $periodeId = $activePeriode->id;
        }

        $siswaQuery = Siswa::query(); 

        $siswaSudahMemilih = (clone $siswaQuery)
            ->whereHas('pendaftaranPilihan', function($q) use ($periodeId) {
                $q->where('periode_pendaftaran_id', $periodeId);
            })
            ->with(['pendaftaranPilihan' => function($q) use ($periodeId) {
                $q->where('periode_pendaftaran_id', $periodeId)
                  ->with('detailPendaftaranPilihan.paketMenuPilihan');
            }])
            ->paginate((int) $request->input('per_page_sudah', 10), ['*'], 'page_sudah');

        $siswaBelumMemilih = (clone $siswaQuery)
            ->whereDoesntHave('pendaftaranPilihan', function($q) use ($periodeId) {
                $q->where('periode_pendaftaran_id', $periodeId);
            })
            ->paginate((int) $request->input('per_page_belum', 10), ['*'], 'page_belum');

        // Hitung total keseluruhan
        $totalSudah = (clone $siswaQuery)->whereHas('pendaftaranPilihan', function($q) use ($periodeId) {
                $q->where('periode_pendaftaran_id', $periodeId);
            })->count();
            
        $totalBelum = (clone $siswaQuery)->whereDoesntHave('pendaftaranPilihan', function($q) use ($periodeId) {
                $q->where('periode_pendaftaran_id', $periodeId);
            })->count();

        // Rincian peminatan per paket menu
        $detailPilihan = DB::table('detail_pendaftaran_pilihan')
            ->join('pendaftaran_pilihan', 'pendaftaran_pilihan.id', '=', 'detail_pendaftaran_pilihan.pendaftaran_pilihan_id')
            ->join('paket_menu_pilihan', 'paket_menu_pilihan.id', '=', 'detail_pendaftaran_pilihan.paket_menu_pilihan_id')
            ->where('pendaftaran_pilihan.periode_pendaftaran_id', $periodeId)
            ->whereNull('pendaftaran_pilihan.deleted_at')
            ->select('paket_menu_pilihan.id', 'paket_menu_pilihan.nama_menu', DB::raw('count(detail_pendaftaran_pilihan.id) as total'))
            ->groupBy('paket_menu_pilihan.id', 'paket_menu_pilihan.nama_menu')
            ->get();

        $paketMenu = PaketMenuPilihan::where('periode_id', $periodeId)->orWhereNull('periode_id')->get(['id', 'nama_menu']);
        
        // Data periode untuk dropdown filter periode
        $periodes = PeriodePendaftaran::select('id', 'tahun_ajaran')->orderBy('tahun_ajaran', 'desc')->get();

        $summary = [
            'total_siswa' => $totalSudah + $totalBelum,
            'sudah_memilih' => $totalSudah,
            'belum_memilih' => $totalBelum,
            'detail_paket' => $detailPilihan
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'siswa_sudah_memilih' => $siswaSudahMemilih,
                'siswa_belum_memilih' => $siswaBelumMemilih,
                'paket_menu' => $paketMenu,
                'periodes' => $periodes
            ]);
        }

        return view('admin.pemantauan-minat.index', compact(
            'summary', 
            'siswaSudahMemilih', 
            'siswaBelumMemilih', 
            'paketMenu',
            'periodes'
        ));
    }
}
