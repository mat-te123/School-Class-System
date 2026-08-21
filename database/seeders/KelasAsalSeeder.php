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
            ['nama_kelas' => 'X A'],
            ['nama_kelas' => 'X B'],
            ['nama_kelas' => 'X C'],
            ['nama_kelas' => 'X D'],
            ['nama_kelas' => 'X E'],
            ['nama_kelas' => 'X F'],
            ['nama_kelas' => 'X G'],
        ];

        foreach ($kelases as $k) {
            DB::table('kelas_asal')->updateOrInsert(
                ['nama_kelas' => $k['nama_kelas']],
                [
                    'id' => (string) Str::uuid(),
                    'tingkat' => 'X',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
