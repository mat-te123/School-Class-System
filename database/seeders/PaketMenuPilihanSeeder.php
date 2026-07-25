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
        $menus = [
            ['kode_menu' => 1, 'nama_menu' => 'Menu 1 (P1)', 'rumpun' => 'eksakta', 'kuota_kapasitas' => 36],
            ['kode_menu' => 2, 'nama_menu' => 'Menu 2 (P2)', 'rumpun' => 'eksakta', 'kuota_kapasitas' => 72],
            ['kode_menu' => 3, 'nama_menu' => 'Menu 3 (P3)', 'rumpun' => 'eksakta', 'kuota_kapasitas' => 72],
            ['kode_menu' => 4, 'nama_menu' => 'Menu 4 (P4)', 'rumpun' => 'sosial', 'kuota_kapasitas' => 36],
            ['kode_menu' => 5, 'nama_menu' => 'Menu 5 (P5)', 'rumpun' => 'sosial', 'kuota_kapasitas' => 36],
        ];

        foreach ($menus as $m) {
            DB::table('paket_menu_pilihan')->updateOrInsert(
                ['kode_menu' => $m['kode_menu']],
                [
                    'id' => (string) Str::uuid(),
                    'nama_menu' => $m['nama_menu'],
                    'rumpun' => $m['rumpun'],
                    'kuota_kapasitas' => $m['kuota_kapasitas'],
                    'kuota_terisi' => 0,
                    'is_active' => true,
                ]
            );
        }
    }
}
