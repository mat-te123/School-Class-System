<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaketKriteriaBobotSeeder extends Seeder
{
    /**
     * Seed configured subject weights for each package menu.
     */
    public function run(): void
    {
        $criteria = config('seeding.paket_kriteria_bobot', []);

        if ($criteria === []) {
            throw new RuntimeException('Konfigurasi seeding.paket_kriteria_bobot belum diisi.');
        }

        foreach ($criteria as $packageName => $subjects) {
            $packageId = DB::table('paket_menu_pilihan')
                ->where('nama_menu', $packageName)
                ->value('id');

            if (! $packageId) {
                throw new RuntimeException("Paket menu {$packageName} tidak ditemukan.");
            }

            foreach ($subjects as $subjectCode => $weight) {
                $subjectId = DB::table('master_mata_pelajaran')
                    ->where('kode_mapel', $subjectCode)
                    ->value('id');

                if (! $subjectId) {
                    throw new RuntimeException("Mata pelajaran {$subjectCode} tidak ditemukan.");
                }

                DB::table('kriteria_bobot_menu')->updateOrInsert(
                    [
                        'paket_menu_pilihan_id' => $packageId,
                        'master_mata_pelajaran_id' => $subjectId,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'bobot_persen' => $weight,
                    ]
                );
            }
        }
    }
}
