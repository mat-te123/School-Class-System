<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KelasAsalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelases = [
            ['nama_kelas' => 'X A', 'tingkat' => 'X', 'kapasitas' => 36],
            ['nama_kelas' => 'X B', 'tingkat' => 'X', 'kapasitas' => 36],
            ['nama_kelas' => 'X C', 'tingkat' => 'X', 'kapasitas' => 36],
            ['nama_kelas' => 'X D', 'tingkat' => 'X', 'kapasitas' => 36],
            ['nama_kelas' => 'X E', 'tingkat' => 'X', 'kapasitas' => 36],
            ['nama_kelas' => 'X F', 'tingkat' => 'X', 'kapasitas' => 36],
            ['nama_kelas' => 'X G', 'tingkat' => 'X', 'kapasitas' => 36],
        ];

        foreach ($kelases as $k) {
            DB::table('kelas_asal')->updateOrInsert(
                ['nama_kelas' => $k['nama_kelas']],
                [
                    'id' => (string) Str::uuid(),
                    'tingkat' => $k['tingkat'],
                    'kapasitas' => $k['kapasitas'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
