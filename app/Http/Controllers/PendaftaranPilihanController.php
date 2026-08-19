<?php

namespace App\Http\Controllers;

use App\Models\DetailPendaftaranPilihan;
use App\Models\HasilSeleksi;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PendaftaranPilihanController extends Controller
{
    /**
     * FR-52: Siswa melihat status/pilihan yang sudah diambil pada periode berjalan.
     */
    public function indexSiswa(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated / Akses ditolak.',
                ], 401);
            }
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        // Cari periode aktif saat ini
        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        if (!$periodeAktif) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada periode pendaftaran yang sedang berjalan saat ini.',
                    'data' => null,
                ]);
            }
            return view('pendaftaran-pilihan.index-siswa', ['data' => null, 'message' => 'Tidak ada periode pendaftaran yang sedang berjalan saat ini.']);
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mendapatkan data pendaftaran pilihan siswa.',
                'data' => $pendaftaran,
            ]);
        }

        return view('pendaftaran-pilihan.index-siswa', compact('pendaftaran'));
    }

    /**
     * FR-52: Siswa memilih 3 paket prioritas (submit formulir pendaftaran pilihan).
     */
    public function storeSiswa(Request $request): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated / Akses ditolak.',
            ], 401);
        }

        // 1. Cek apakah ada periode pendaftaran aktif yang sedang buka
        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        if (!$periodeAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran ditolak. Tidak ada periode pendaftaran aktif yang sedang berjalan.',
            ], 422);
        }

        // 2. Cek apakah siswa sudah pernah mendaftar pada periode ini
        $existingPendaftaran = PendaftaranPilihan::where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();

        if ($existingPendaftaran) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah pernah mengirimkan pilihan paket pada periode ini.',
            ], 409);
        }

        $maxPilihan = $periodeAktif->max_pilihan_siswa ?? 3;

        // 3. Validasi Payload Input Pilihan
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

        // 4. Pastikan tidak ada pilihan yang duplikat
        if (count($pilihanIds) !== count(array_unique($pilihanIds))) {
            return response()->json([
                'success' => false,
                'message' => 'Pilihan paket menu prioritas tidak boleh ada yang duplikat / sama.',
            ], 422);
        }

        // 5. Pastikan semua paket menu yang dipilih berstatus is_active = true
        $activePackagesCount = PaketMenuPilihan::whereIn('id', $pilihanIds)
            ->where('is_active', true)
            ->count();

        if ($activePackagesCount !== count($pilihanIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Salah satu paket menu yang Anda pilih tidak aktif.',
            ], 422);
        }

        // 6. Simpan data Pendaftaran & Detail Pilihan dalam Transaction
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

                // Increment kuota_terisi pada paket menu pilihan yang dipilih
                PaketMenuPilihan::where('id', $paketId)->increment('kuota_terisi');
            }

            return $pendaftaranRecord;
        });

        // Load relasi lengkap untuk response
        $pendaftaran->load([
            'detailPendaftaran.paketMenuPilihan',
            'periodePendaftaran',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menyimpan 3 paket menu pilihan prioritas Anda.',
            'data' => $pendaftaran,
        ], 201);
    }

    /**
     * FR-58: Siswa upload dokumen wali pada pengajuan yang sedang menunggu.
     */
    public function uploadDokumenSiswa(Request $request): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $now = now();
        // Cek periode masih buka (tanggal_buka sudah lewat, tanggal_tutup belum lewat)
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        if (!$periodeAktif) {
            return response()->json(['success' => false, 'message' => 'Tidak ada periode pendaftaran yang sedang aktif.'], 422);
        }

        $pendaftaran = PendaftaranPilihan::where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();

        if (!$pendaftaran) {
            return response()->json(['success' => false, 'message' => 'Belum ada pengajuan pilihan untuk periode ini.'], 404);
        }

        $validated = $request->validate([
            'dokumen_wali' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $path = $request->file('dokumen_wali')
            ->store("dokumen_wali/{$siswa->id}", 'public');

        $pendaftaran->update(['dokumen_wali_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen wali berhasil diunggah.',
            'data' => ['dokumen_wali_path' => $path],
        ]);
    }

    /**
     * FR-59: Siswa batalkan pengajuan (hanya status 'menunggu' & dalam periode pertukaran).
     */
    public function cancelSiswa(): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_mulai_pertukaran', '<=', $now)
            ->where('tanggal_selesai_pertukaran', '>=', $now)
            ->first();

        if (!$periodeAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Pembatalan hanya dapat dilakukan dalam periode pertukaran aktif.',
            ], 422);
        }

        $pendaftaran = PendaftaranPilihan::with('detailPendaftaran')
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();

        if (!$pendaftaran) {
            return response()->json(['success' => false, 'message' => 'Tidak ada pengajuan yang ditemukan.'], 404);
        }

        if ($pendaftaran->status !== 'menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan yang sudah disetujui atau ditolak tidak dapat dibatalkan.',
            ], 422);
        }

        DB::transaction(function () use ($pendaftaran) {
            // Decrement kuota_terisi untuk setiap paket yang dipilih
            foreach ($pendaftaran->detailPendaftaran as $detail) {
                PaketMenuPilihan::where('id', $detail->paket_menu_pilihan_id)->decrement('kuota_terisi');
            }
            $pendaftaran->delete();
        });

        return response()->json(['success' => true, 'message' => 'Pengajuan berhasil dibatalkan.']);
    }

    /**
     * FR-53: Siswa mengubah 3 paket prioritas pada periode pendaftaran yang masih buka.
     * Diizinkan selama status pengajuan masih 'menunggu'.
     */
    public function updateSiswa(Request $request): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated / Akses ditolak.',
            ], 401);
        }

        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        if (!$periodeAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Pendaftaran ditolak. Tidak ada periode pendaftaran aktif yang sedang berjalan.',
            ], 422);
        }

        $pendaftaran = PendaftaranPilihan::with('detailPendaftaran')
            ->where('siswa_id', $siswa->id)
            ->where('periode_pendaftaran_id', $periodeAktif->id)
            ->first();

        if (!$pendaftaran) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada pengajuan pilihan untuk periode ini. Silakan submit terlebih dahulu.',
            ], 404);
        }

        if ($pendaftaran->status !== 'menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Pilihan tidak dapat diubah karena pengajuan sudah direview oleh admin.',
            ], 409);
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
            return response()->json([
                'success' => false,
                'message' => 'Pilihan paket menu prioritas tidak boleh ada yang duplikat / sama.',
            ], 422);
        }

        $activePackagesCount = PaketMenuPilihan::whereIn('id', $pilihanIds)
            ->where('is_active', true)
            ->count();

        if ($activePackagesCount !== count($pilihanIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Salah satu paket menu yang Anda pilih tidak aktif.',
            ], 422);
        }

        DB::transaction(function () use ($pendaftaran, $pilihanIds) {
            // Kembalikan kuota paket lama
            foreach ($pendaftaran->detailPendaftaran as $detail) {
                PaketMenuPilihan::where('id', $detail->paket_menu_pilihan_id)->decrement('kuota_terisi');
                $detail->delete();
            }

            // Simpan pilihan baru
            foreach ($pilihanIds as $index => $paketId) {
                DetailPendaftaranPilihan::create([
                    'id' => (string) Str::uuid(),
                    'pendaftaran_pilihan_id' => $pendaftaran->id,
                    'paket_menu_pilihan_id' => $paketId,
                    'urutan_pilihan' => $index + 1,
                ]);

                PaketMenuPilihan::where('id', $paketId)->increment('kuota_terisi');
            }

            // Reset ke menunggu bila sempat ditolak (agar bisa di-review ulang)
            $pendaftaran->update([
                'status' => 'menunggu',
                'catatan_penolakan' => null,
            ]);
        });

        $pendaftaran->load([
            'detailPendaftaran.paketMenuPilihan',
            'periodePendaftaran',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pilihan paket prioritas Anda berhasil diperbarui.',
            'data' => $pendaftaran,
        ]);
    }

    /**
     * FR-54 - FR-56: Siswa melihat hasil penempatan, status diterima, dan detail skor akhir.
     * Hasil hanya tampil bila status_pengumuman periode = AKTIF.
     */
    public function hasilPenempatanSiswa(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated / Akses ditolak.',
                ], 401);
            }
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        $periode = PeriodePendaftaran::where('is_active', true)->latest('tanggal_tutup')->first();

        if (!$periode || $periode->status_pengumuman !== 'AKTIF') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hasil penempatan belum dipublikasikan.',
                ], 403);
            }
            abort(403, 'Hasil penempatan belum dipublikasikan.');
        }

        $hasil = HasilSeleksi::with('paketMenuPilihan')
            ->where('siswa_id', $siswa->id)
            ->first();

        if (!$hasil) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hasil penempatan untuk Anda belum tersedia.',
                ], 404);
            }
            abort(404, 'Hasil penempatan untuk Anda belum tersedia.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $hasil->id,
                    'pilihan_ke_diterima' => $hasil->pilihan_ke_diterima,
                    'mekanisme' => $hasil->mekanisme,
                    'status' => $hasil->pilihan_ke_diterima ? 'Diterima' : 'Belum diterima',
                    'skor_penempatan' => $hasil->skor_penempatan,
                    'rata_6_mapel' => $hasil->rata_6_mapel,
                    'rank_pada_pilihan' => $hasil->rank_pada_pilihan,
                    'paket_diterima' => $hasil->paketMenuPilihan?->nama_menu,
                    'tanggal_diproses' => $hasil->tanggal_diproses,
                ],
            ]);
        }

        return view('pendaftaran-pilihan.hasil-penempatan', compact('hasil'));
    }
}
