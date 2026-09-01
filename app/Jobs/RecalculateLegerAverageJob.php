<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RecalculateLegerAverageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $legerIds;

    public function __construct(array $legerIds)
    {
        $this->legerIds = $legerIds;
    }

    public function handle(): void
    {
        $this->calculateLegerAverageBatch($this->legerIds);
    }

    private function calculateLegerAverageBatch(array $legerIds): void
    {
        if (empty($legerIds)) {
            return;
        }

        // 5 mapel utama untuk rata_6_mapel
        $mapelUtama = ['B.IND', 'MTK-U', 'IPA', 'IPS', 'B.ING'];

        // Get averages per leger ID
        $averages = DB::table('detail_nilai_siswa')
            ->select('nilai_leger_siswa_id', DB::raw('AVG(nilai_angka) as average'))
            ->whereIn('nilai_leger_siswa_id', $legerIds)
            ->groupBy('nilai_leger_siswa_id')
            ->pluck('average', 'nilai_leger_siswa_id');

        // Get averages for 5 mapel utama only
        $averages6Mapel = DB::table('detail_nilai_siswa')
            ->join('master_mata_pelajaran', 'detail_nilai_siswa.master_mata_pelajaran_id', '=', 'master_mata_pelajaran.id')
            ->select('nilai_leger_siswa_id', DB::raw('AVG(nilai_angka) as average'))
            ->whereIn('nilai_leger_siswa_id', $legerIds)
            ->whereIn('master_mata_pelajaran.nama_mapel', $mapelUtama)
            ->groupBy('nilai_leger_siswa_id')
            ->pluck('average', 'nilai_leger_siswa_id');

        // Fetch details to build nilai_json per leger ID
        $details = DB::table('detail_nilai_siswa')
            ->join('master_mata_pelajaran', 'detail_nilai_siswa.master_mata_pelajaran_id', '=', 'master_mata_pelajaran.id')
            ->select('detail_nilai_siswa.nilai_leger_siswa_id', 'master_mata_pelajaran.nama_mapel', 'detail_nilai_siswa.nilai_angka')
            ->whereIn('detail_nilai_siswa.nilai_leger_siswa_id', $legerIds)
            ->get()
            ->groupBy('nilai_leger_siswa_id');

        foreach ($legerIds as $legerId) {
            $rataKeseluruhan = round((float) ($averages->get($legerId) ?? 0), 2);
            $rata6Mapel = round((float) ($averages6Mapel->get($legerId) ?? 0), 2);
            $legerDetails = $details->get($legerId, collect());

            $nilaiJson = [];
            foreach ($legerDetails as $d) {
                $nilaiJson[$d->nama_mapel] = (float) $d->nilai_angka;
            }

            DB::table('nilai_leger_siswa')
                ->where('id', $legerId)
                ->update([
                    'rata_6_mapel'     => $rata6Mapel,
                    'rata_keseluruhan' => $rataKeseluruhan,
                    'nilai_json'       => json_encode($nilaiJson),
                ]);
        }
    }
}
