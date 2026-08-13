<?php

namespace App\Http\Controllers;

use App\Models\DetailPendaftaranPilihan;
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
    public function indexSiswa(Request $request): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();
        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated / Akses ditolak.',
            ], 401);
        }

        // Cari periode aktif saat ini
        $now = now();
        $periodeAktif = PeriodePendaftaran::where('is_active', true)
            ->where('tanggal_buka', '<=', $now)
            ->where('tanggal_tutup', '>=', $now)
            ->first();

        if (!$periodeAktif) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada periode pendaftaran yang sedang berjalan saat ini.',
                'data' => null,
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

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mendapatkan data pendaftaran pilihan siswa.',
            'data' => $pendaftaran,
        ]);
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
}
