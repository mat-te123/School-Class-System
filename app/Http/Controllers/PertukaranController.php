<?php

namespace App\Http\Controllers;

use App\Models\HasilSeleksi;
use App\Models\PaketMenuPilihan;
use App\Models\PengajuanPertukaran;
use App\Models\PeriodePendaftaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PertukaranController extends Controller
{
    /**
     * Admin melihat daftar seluruh pengajuan pertukaran siswa.
     */
    public function index(Request $request)
    {
        $this->ensureAdmin('pengajuan pertukaran');

        $validated = $request->validate([
            'status' => 'nullable|string|in:menunggu,disetujui,ditolak',
            'search' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PengajuanPertukaran::with([
            'siswa',
            'periodePendaftaran',
            'paketAsal',
            'paketTujuan',
            'peninjau',
        ]);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $search = mb_strtolower(trim($validated['search']));
            $query->where(function ($q) use ($search) {
                $q->whereHas('siswa', function ($s) use ($search) {
                    $s->whereRaw('LOWER(nama_lengkap) like ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(nisn) like ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(nis) like ?', ["%{$search}%"]);
                });
            });
        }

        $pengajuan = $query->latest('created_at')->paginate((int) $request->input('per_page', 10));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $pengajuan,
            ]);
        }

        return view('auth.laporan.pergantian-kelas.index', compact('pengajuan'));
    }

    /**
     * Admin melihat detail 1 pengajuan pertukaran.
     */
    public function show(string $id)
    {
        $this->ensureAdmin('pengajuan pertukaran');

        $pengajuan = PengajuanPertukaran::with([
            'siswa',
            'periodePendaftaran',
            'paketAsal',
            'paketTujuan',
            'peninjau',
        ])->findOrFail($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $pengajuan,
            ]);
        }

        return view('admin-pertukaran.show', compact('pengajuan'));
    }

    /**
     * Admin mengunduh dokumen persetujuan wali dari pengajuan pertukaran.
     */
    public function downloadDokumen(string $id): StreamedResponse|JsonResponse
    {
        $this->ensureAdmin('pengajuan pertukaran');

        $pengajuan = PengajuanPertukaran::findOrFail($id);

        if (! $pengajuan->dokumen_persetujuan_path) {
            return response()->json([
                'success' => false,
                'message' => 'Dokumen persetujuan wali belum diunggah oleh siswa.',
            ], 404);
        }

        if (! Storage::disk('public')->exists($pengajuan->dokumen_persetujuan_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File dokumen tidak ditemukan di penyimpanan.',
            ], 404);
        }

        $filename = 'dokumen_pertukaran_'.$pengajuan->siswa->nisn
            .'.'.pathinfo($pengajuan->dokumen_persetujuan_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($pengajuan->dokumen_persetujuan_path, $filename);
    }

    /**
     * Admin menyetujui pengajuan pertukaran.
     * Saat disetujui, hasil_seleksi siswa diperbarui ke paket tujuan.
     */
    public function approve(Request $request, string $id)
    {
        $this->ensureAdmin('pengajuan pertukaran');

        $pengajuan = PengajuanPertukaran::findOrFail($id);

        if ($pengajuan->status !== 'menunggu') {
            return $this->handleWriteResponse($request, [
                'success' => false,
                'message' => 'Pengajuan sudah diproses sebelumnya.',
            ], 422);
        }

        if ($pengajuan->paketTujuan?->periode_id !== $pengajuan->periode_pendaftaran_id || $pengajuan->paketTujuan->trashed()) {
            return $this->handleWriteResponse($request, [
                'success' => false,
                'message' => 'Paket tujuan tidak tersedia pada periode pertukaran ini.',
            ], 422);
        }

        DB::transaction(function () use ($pengajuan, $request) {
            $pengajuan->update([
                'status' => 'disetujui',
                'catatan_admin' => $request->input('catatan_admin'),
                'ditinjau_oleh' => Auth::guard('web')->id(),
                'tanggal_tinjauan' => now(),
            ]);

            $hasil = HasilSeleksi::where('siswa_id', $pengajuan->siswa_id)
                ->where('periode_pendaftaran_id', $pengajuan->periode_pendaftaran_id)
                ->first();
            if ($hasil) {
                $hasil->update([
                    'paket_menu_pilihan_id' => $pengajuan->paket_tujuan_id,
                    'is_manual_override' => true,
                    'catatan_perubahan' => 'Persetujuan pertukaran: dari '
                        .($pengajuan->paketAsal?->nama_menu ?? 'paket sebelumnya')
                        .' ke '.($pengajuan->paketTujuan?->nama_menu ?? 'paket baru'),
                    'diubah_oleh' => Auth::guard('web')->id(),
                    'tanggal_perubahan' => now(),
                    'mekanisme' => 'Pelimpahan Kompetensi',
                ]);
            }
        });

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Pengajuan pertukaran berhasil disetujui. Paket kelas siswa telah diperbarui.',
            'data' => $pengajuan->fresh(['siswa', 'paketAsal', 'paketTujuan', 'peninjau']),
        ]);
    }

    /**
     * Admin menolak pengajuan pertukaran dengan catatan wajib.
     */
    public function reject(Request $request, string $id)
    {
        $this->ensureAdmin('pengajuan pertukaran');

        $validated = $request->validate([
            'catatan_admin' => 'required|string|max:1000',
        ]);

        $pengajuan = PengajuanPertukaran::findOrFail($id);

        if ($pengajuan->status !== 'menunggu') {
            return $this->handleWriteResponse($request, [
                'success' => false,
                'message' => 'Pengajuan sudah diproses sebelumnya.',
            ], 422);
        }

        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_admin' => $validated['catatan_admin'],
            'ditinjau_oleh' => Auth::guard('web')->id(),
            'tanggal_tinjauan' => now(),
        ]);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Pengajuan pertukaran berhasil ditolak.',
            'data' => $pengajuan->fresh(['siswa', 'paketAsal', 'paketTujuan', 'peninjau']),
        ]);
    }

    /**
     * Siswa melihat pengajuan pertukaran miliknya.
     */
    public function siswaIndex(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $periode_data = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_mulai_pertukaran', '<=', now())
            ->where('tanggal_selesai_pertukaran', '>=', now())
            ->first();

        $hasilSeleksi = HasilSeleksi::where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periode_data?->id)
            ->value('paket_menu_pilihan_id');

        $paket_list = PaketMenuPilihan::where('periode_id', $periode_data?->id)
            ->when($hasilSeleksi, function ($query, $hasilSeleksi) {
                return $query->where('id', '!=', $hasilSeleksi);
            })
            ->select('id', 'nama_menu')
            ->get();

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

        return view('siswa.penukaran.index', compact('pengajuan', 'paket_list'));
    }

    /**
     * Siswa mengajukan pertukaran paket kelas.
     */
    public function store(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_mulai_pertukaran', '<=', $now)
            ->where('tanggal_selesai_pertukaran', '>=', $now)
            ->first();

        if (! $periodeAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan pertukaran ditolak. Masa pertukaran belum dibuka atau telah berakhir.',
            ], 422);
        }

        $hasilSeleksi = HasilSeleksi::where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();
        if ($hasilSeleksi?->paketMenuPilihan?->periode_id !== $periodeAktif->id) {
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
            return $this->handleWriteResponse($request, [
                'success' => false,
                'message' => 'Anda sudah memiliki pengajuan pertukaran yang sedang diproses atau telah disetujui.',
            ], 422);
        }

        $validated = $request->validate([
            'paket_tujuan_id' => [
                'required',
                'uuid',
                Rule::exists('paket_menu_pilihan', 'id')->where('periode_id', $periodeAktif->id)->whereNull('deleted_at'),
            ],
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

    /**
     * Siswa membatalkan pengajuan pertukaran yang masih menunggu.
     */
    public function destroy(Request $request, string $id)
    {
        $siswa = Auth::guard('siswa')->user();
        if (! $siswa) {
            abort(401, 'Unauthenticated.');
        }

        $pengajuan = PengajuanPertukaran::where('id', $id)
            ->where('siswa_id', $siswa->id)
            ->first();

        if (! $pengajuan) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan pertukaran tidak ditemukan.',
            ], 404);
        }

        if ($pengajuan->status !== 'menunggu') {
            return $this->handleWriteResponse($request, [
                'success' => false,
                'message' => 'Pengajuan pertukaran tidak dapat dibatalkan karena sudah diproses.',
            ], 422);
        }

        $pengajuan->delete();

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Pengajuan pertukaran berhasil dibatalkan.',
        ]);
    }
}
