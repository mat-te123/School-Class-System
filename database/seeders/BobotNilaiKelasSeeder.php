<?php

namespace Database\Seeders;

use App\Models\BobotNilaiKelas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BobotNilaiKelasSeeder extends Seeder
{
    /**
     * Seed dummy subject weights for every class and active subject.
     */
    public function run(): void
    {
        $kelas = DB::table('kelas_asal')->whereNull('deleted_at')->orderBy('nama_kelas')->get(['id']);
        $mapel = DB::table('master_mata_pelajaran')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('kode_mapel')
            ->get(['id']);

        if ($kelas->isEmpty() || $mapel->isEmpty()) {
            return;
        }

        $basePoints = intdiv(10000, $mapel->count());
        $remainder = 10000 % $mapel->count();

        foreach ($kelas as $kelasIndex => $kelasItem) {
            foreach ($mapel as $mapelIndex => $mapelItem) {
                $rotatedIndex = ($mapelIndex - $kelasIndex + $mapel->count()) % $mapel->count();
                $points = $basePoints + ($rotatedIndex < $remainder ? 1 : 0);

                BobotNilaiKelas::updateOrCreate(
                    [
                        'kelas_asal_id' => $kelasItem->id,
                        'master_mata_pelajaran_id' => $mapelItem->id,
                    ],
                    ['bobot_persen' => $points / 100]
                );
            }
        }
    }
}
