<?php

namespace App\Http\Controllers;

use App\Models\HasilSeleksi;
use App\Models\PaketMenuPilihan;
use App\Models\PengajuanPertukaran;
use App\Models\PeriodePendaftaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SiswaPertukaranController extends Controller
{
    public function index(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $pengajuan = PengajuanPertukaran::with(['paketAsal', 'paketTujuan', 'peninjau'])
            ->where('siswa_id', $siswa->id)
            ->latest()
            ->first();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $pengajuan,
            ]);
        }

        return view('siswa.pertukaran.index', compact('pengajuan'));
    }

    public function store(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_mulai_pertukaran', '<=', $now)
            ->where('tanggal_selesai_pertukaran', '>=', $now)
            ->first();

        if (!$periodeAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan pertukaran ditolak. Masa pertukaran belum dibuka atau telah berakhir.',
            ], 422);
        }

        $hasilSeleksi = HasilSeleksi::where('siswa_id', $siswa->id)->first();
        if (!$hasilSeleksi || !$hasilSeleksi->paket_menu_pilihan_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum terdaftar dalam paket kelas penempatan.',
            ], 422);
        }

        $existingPengajuan = PengajuanPertukaran::where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->first();

        if ($existingPengajuan) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki pengajuan pertukaran yang aktif.',
            ], 409);
        }

        $validated = $request->validate([
            'paket_tujuan_id' => 'required|uuid|exists:paket_menu_pilihan,id',
            'alasan' => 'required|string|max:1000',
            'dokumen_persetujuan' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validated['paket_tujuan_id'] === $hasilSeleksi->paket_menu_pilihan_id) {
            return response()->json([
                'success' => false,
                'message' => 'Paket tujuan tidak boleh sama dengan paket penempatan Anda saat ini.',
            ], 422);
        }

        $dokumenPath = null;
        if ($request->hasFile('dokumen_persetujuan')) {
            $dokumenPath = $request->file('dokumen_persetujuan')
                ->store("dokumen_pertukaran/{$siswa->id}", 'public');
        }

        $pengajuan = PengajuanPertukaran::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $siswa->id,
            'periode_pendaftaran_id' => $periodeAktif->id,
            'paket_asal_id' => $hasilSeleksi->paket_menu_pilihan_id,
            'paket_tujuan_id' => $validated['paket_tujuan_id'],
            'alasan' => $validated['alasan'],
            'dokumen_persetujuan_path' => $dokumenPath,
            'status' => 'menunggu',
        ]);

        $pengajuan->load(['paketAsal', 'paketTujuan']);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Pengajuan pertukaran kelas berhasil dikirimkan.',
            'data' => $pengajuan,
        ], 201);
    }

    public function cancel(Request $request, string $id)
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            abort(401, 'Unauthenticated.');
        }

        $pengajuan = PengajuanPertukaran::where('id', $id)
            ->where('siswa_id', $siswa->id)
            ->first();

        if (!$pengajuan) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan pertukaran tidak ditemukan.',
            ], 404);
        }

        if ($pengajuan->status !== 'menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan pertukaran yang sudah diproses admin tidak dapat dibatalkan.',
            ], 422);
        }

        $pengajuan->delete();

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Pengajuan pertukaran berhasil dibatalkan.',
        ]);
    }
}
