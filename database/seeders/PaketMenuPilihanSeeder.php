<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaketMenuPilihanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periodeId = DB::table('periode_pendaftaran')
            ->where('nama_periode', 'Pemilihan Mapel Fase F 2026/2027')
            ->value('id');

        if (!$periodeId) {
            throw new \RuntimeException('Periode pendaftaran untuk paket menu belum tersedia.');
        }

        $menus = [
            ['nama_menu' => 'Menu 1 (P1)', 'rumpun' => 'eksakta', 'kuota_kapasitas' => 36],
            ['nama_menu' => 'Menu 2 (P2)', 'rumpun' => 'eksakta', 'kuota_kapasitas' => 72],
            ['nama_menu' => 'Menu 3 (P3)', 'rumpun' => 'eksakta', 'kuota_kapasitas' => 72],
            ['nama_menu' => 'Menu 4 (P4)', 'rumpun' => 'sosial', 'kuota_kapasitas' => 36],
            ['nama_menu' => 'Menu 5 (P5)', 'rumpun' => 'sosial', 'kuota_kapasitas' => 36],
        ];

        foreach ($menus as $m) {
            DB::table('paket_menu_pilihan')->updateOrInsert(
                [
                    'nama_menu' => $m['nama_menu'],
                    'periode_id' => $periodeId,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'rumpun' => $m['rumpun'],
                    'kuota_kapasitas' => $m['kuota_kapasitas'],
                    'kuota_terisi' => 0,
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
