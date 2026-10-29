<?php

namespace App\Http\Controllers;

use App\Models\PaketMenuPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use Illuminate\Http\Request;
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
        if (! $user || $user->role !== 'admin') {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengakses halaman ini.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $validate = $request->validate([
            'search' => 'nullable|string|max:150',
            'status' => 'nullable|in:sudah,belum',
            'kelas_pilihan' => 'nullable|uuid|exists:paket_menu_pilihan,id',
        ]);

        $search = $validate['search'] ?? null;
        $status = $validate['status'] ?? 'sudah';
        $kelas = $validate['kelas_pilihan'] ?? null;

        $cari = fn ($q) => $q->when($search, fn ($q, $s) => $q->where(fn ($q) => $q
            ->where('nama_lengkap', 'like', "%{$s}%")
            ->orWhere('nisn', 'like', "%{$s}%")
            ->orWhere('nis', 'like', "%{$s}%")));

        $periodeId = $request->periode_id;

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
            ->withQueryString();

        $siswaBelumMemilih = (clone $siswaQuery)
            ->tap($cari)
            ->whereDoesntHave('pendaftaranPilihan', function ($q) use ($periodeId) {
                $q->where('periode_pendaftaran_id', $periodeId);
            })
            ->paginate((int) $request->input('per_page_belum', 10), ['*'], 'page_belum')
            ->withQueryString();

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

        // Data periode untuk dropdown filter periode
        $periodes = PeriodePendaftaran::select('id', 'tahun_ajaran')->orderBy('tahun_ajaran', 'desc')->get();
        
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
                'periodes' => $periodes,
            ]);
        }

        return view('auth.pemantauan-kelas.index', compact(
            'summary',
            'siswaSudahMemilih',
            'siswaBelumMemilih',
            'paketMenu',
            'periodes',
            'status'
        ));
    }
}
