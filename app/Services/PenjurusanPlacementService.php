<?php

namespace App\Services;

use App\Models\HasilSeleksi;
use App\Models\KriteriaBobotMenu;
use App\Models\NilaiLegerSiswa;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PenjurusanPlacementService
{
    /**
     * Calculate placement for all students in the given period.
     * Returns array with placement statistics.
     */
    public function calculatePlacement(string $periodeId): array
    {
        $periode = PeriodePendaftaran::findOrFail($periodeId);

        // Get all approved pendaftaran for this period
        $pendaftaranList = PendaftaranPilihan::with(['detailPendaftaran.paketMenuPilihan', 'siswa'])
            ->where('periode_pendaftaran_id', $periodeId)
            ->where('status', 'disetujui')
            ->get();

        if ($pendaftaranList->isEmpty()) {
            return [
                'success' => true,
                'message' => 'Tidak ada pendaftaran yang disetujui untuk diproses.',
                'processed' => 0,
            ];
        }

        // Get all nilai leger for students
        $siswaIds = $pendaftaranList->pluck('siswa_id')->unique();
        $nilaiLeger = NilaiLegerSiswa::whereIn('siswa_id', $siswaIds)
            ->get()
            ->keyBy('siswa_id');

        // Get all kriteria bobot for all paket menu
        $paketIds = $pendaftaranList->flatMap(fn ($p) => $p->detailPendaftaran->pluck('paket_menu_pilihan_id'))->unique();
        $kriteriaBobot = KriteriaBobotMenu::with('mataPelajaran')
            ->whereIn('paket_menu_pilihan_id', $paketIds)
            ->get()
            ->groupBy('paket_menu_pilihan_id');

        // Track kuota per paket (inisialisasi terisi = 0 untuk penempatan periode ini)
        $paketModels = PaketMenuPilihan::whereIn('id', $paketIds)->get();
        $paketNames = $paketModels->pluck('nama_menu', 'id')->toArray();
        $kuota = $paketModels->mapWithKeys(fn ($p) => [
                $p->id => [
                    'kapasitas' => (int) $p->kuota_kapasitas,
                    'terisi' => 0,
                ],
            ])
            ->all();

        $studentLogs = [];

        // Calculate scores for each student for each of their choices, grouped by urutan_pilihan
        $choicesByRound = [];
        $maxRound = (int) ($periode->max_pilihan_siswa ?? 3);
        $tiebreakerMapels = \App\Models\MasterMataPelajaran::where('is_tiebreaker_default', true)->pluck('nama_mapel')->toArray();

        foreach ($pendaftaranList as $pendaftaran) {
            $siswaId = $pendaftaran->siswa_id;
            $leger = $nilaiLeger->get($siswaId);

            $tiebreakerScore = 0;
            $nilaiJson = $leger->nilai_json ?? [];
            foreach ($tiebreakerMapels as $mapel) {
                $tiebreakerScore += (float) ($nilaiJson[$mapel] ?? 0);
            }

            foreach ($pendaftaran->detailPendaftaran as $detail) {
                $paketId = $detail->paket_menu_pilihan_id;
                $skor = $this->calculateScore($leger, $kriteriaBobot->get($paketId, collect()));
                $round = (int) $detail->urutan_pilihan;

                $choicesByRound[$round][] = [
                    'siswa_id' => $siswaId,
                    'paket_id' => $paketId,
                    'urutan_pilihan' => $round,
                    'skor_penempatan' => $skor,
                    'tiebreaker_score' => $tiebreakerScore,
                    'rata_6_mapel' => $leger?->rata_6_mapel ?? 0,
                    'tanggal_submit' => $pendaftaran->tanggal_submit,
                ];
            }
        }

        // Place students in tiered rounds (Pilihan 1 -> Pilihan 2 -> Pilihan 3)
        $results = [];
        $placedStudents = [];
        $rankPerPaket = [];

        for ($round = 1; $round <= $maxRound; $round++) {
            if (empty($choicesByRound[$round])) {
                continue;
            }

            // Ambil kandidat pilihan round ini yang belum diterima di round sebelumnya
            $roundCandidates = array_filter(
                $choicesByRound[$round],
                fn ($c) => !isset($placedStudents[$c['siswa_id']])
            );

            // Urutkan kandidat pada round ini dengan urutan tiebreaker yang deterministik
            usort($roundCandidates, function ($a, $b) {
                // 1. Skor Penempatan (Tertinggi menang)
                if ($a['skor_penempatan'] !== $b['skor_penempatan']) {
                    return $b['skor_penempatan'] <=> $a['skor_penempatan'];
                }
                // 2. Skor Mapel Tie-Breaker (Tertinggi menang)
                if ($a['tiebreaker_score'] !== $b['tiebreaker_score']) {
                    return $b['tiebreaker_score'] <=> $a['tiebreaker_score'];
                }
                // 3. Rata-rata 6 Mapel (Tertinggi menang)
                if ($a['rata_6_mapel'] !== $b['rata_6_mapel']) {
                    return $b['rata_6_mapel'] <=> $a['rata_6_mapel'];
                }
                // 4. Waktu Daftar/Submit (Tercepat/Lebih awal menang)
                if ($a['tanggal_submit'] !== $b['tanggal_submit']) {
                    return $a['tanggal_submit'] <=> $b['tanggal_submit'];
                }
                // 5. Deterministik murni (Fallback terakhir)
                return $a['siswa_id'] <=> $b['siswa_id'];
            });

            // Alokasikan siswa pada round ini ke kuota paket yang masih tersedia
            foreach ($roundCandidates as $candidate) {
                $siswaId = $candidate['siswa_id'];
                $paketId = $candidate['paket_id'];
                $namaPaket = $paketNames[$paketId] ?? 'Paket';

                if (!isset($studentLogs[$siswaId])) {
                    $studentLogs[$siswaId] = [];
                }

                if (isset($placedStudents[$siswaId])) {
                    continue;
                }

                $kuotaData = $kuota[$paketId] ?? null;

                if (!$kuotaData) {
                    continue;
                }

                // Check if quota available
                if ($kuotaData['terisi'] < $kuotaData['kapasitas']) {
                    $rankPerPaket[$paketId] = ($rankPerPaket[$paketId] ?? 0) + 1;
                    $rank = $rankPerPaket[$paketId];

                    $studentLogs[$siswaId][] = "Evaluasi Pilihan {$candidate['urutan_pilihan']} ({$namaPaket}): Skor Utama {$candidate['skor_penempatan']}. Berhasil menempati peringkat {$rank} dari kuota {$kuotaData['kapasitas']}. Status: DITERIMA.";

                    $results[] = [
                        'siswa_id' => $siswaId,
                        'paket_menu_pilihan_id' => $paketId,
                        'pilihan_ke_diterima' => $candidate['urutan_pilihan'],
                        'rank_pada_pilihan' => $rank,
                        'skor_penempatan' => $candidate['skor_penempatan'],
                        'rata_6_mapel' => $candidate['rata_6_mapel'],
                        'mekanisme' => "Pilihan {$candidate['urutan_pilihan']}",
                        'riwayat_proses' => json_encode($studentLogs[$siswaId]),
                    ];

                    $placedStudents[$siswaId] = true;
                    $kuota[$paketId]['terisi']++;
                } else {
                    $studentLogs[$siswaId][] = "Evaluasi Pilihan {$candidate['urutan_pilihan']} ({$namaPaket}): Skor Utama {$candidate['skor_penempatan']}. Kuota maksimal {$kuotaData['kapasitas']} telah terisi penuh oleh pendaftar dengan kriteria lebih tinggi. Status: TERGESER.";
                }
            }
        }

        // Handle unplaced students (Kuota Penuh or Pelimpahan Kompetensi)
        foreach ($pendaftaranList as $pendaftaran) {
            $siswaId = $pendaftaran->siswa_id;
            if (!isset($placedStudents[$siswaId])) {
                $leger = $nilaiLeger->get($siswaId);
                $firstChoice = $pendaftaran->detailPendaftaran->first();
                $studentLogs[$siswaId][] = "Siswa tidak berhasil lolos di semua pilihan prioritas. Status: TERLEMPAR / KUOTA PENUH.";

                if ($firstChoice) {
                    $results[] = [
                        'siswa_id' => $siswaId,
                        'paket_menu_pilihan_id' => null,
                        'pilihan_ke_diterima' => null,
                        'rank_pada_pilihan' => null,
                        'skor_penempatan' => $this->calculateScore($leger, $kriteriaBobot->get($firstChoice->paket_menu_pilihan_id, collect())),
                        'rata_6_mapel' => $leger?->rata_6_mapel ?? 0,
                        'mekanisme' => 'Kuota Penuh',
                        'riwayat_proses' => json_encode($studentLogs[$siswaId] ?? []),
                    ];
                }
            }
        }

        // Save to database
        $now = now();
        DB::transaction(function () use ($results, $now) {
            // Clear existing results
            $siswaIds = collect($results)->pluck('siswa_id')->unique();
            HasilSeleksi::whereIn('siswa_id', $siswaIds)->delete();

            // Insert new results
            foreach ($results as $result) {
                HasilSeleksi::create([
                    'id' => (string) Str::uuid(),
                    ...$result,
                    'tanggal_diproses' => $now,
                    'is_manual_override' => false,
                ]);
            }
        });

        return [
            'success' => true,
            'message' => 'Proses penempatan berhasil dijalankan.',
            'processed' => count($results),
            'placed' => count($placedStudents),
            'unplaced' => count($results) - count($placedStudents),
        ];
    }

    /**
     * Calculate placement score based on kriteria bobot.
     */
    private function calculateScore(?NilaiLegerSiswa $leger, $kriteriaBobot): float
    {
        if (!$leger || $kriteriaBobot->isEmpty()) {
            return 0;
        }

        $nilaiJson = $leger->nilai_json ?? [];
        $totalScore = 0;
        $totalBobot = 0;

        foreach ($kriteriaBobot as $kb) {
            $mapelNama = $kb->mataPelajaran?->nama_mapel;
            $nilai = $nilaiJson[$mapelNama] ?? 0;
            $bobot = $kb->bobot_persen ?? 0;

            $totalScore += ($nilai * $bobot / 100);
            $totalBobot += $bobot;
        }

        // Normalize if total bobot != 100
        if ($totalBobot > 0 && $totalBobot != 100) {
            $totalScore = ($totalScore / $totalBobot) * 100;
        }

        return round($totalScore, 2);
    }
}
