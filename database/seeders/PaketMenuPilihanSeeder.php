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
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->value('id');

        if (! $periodeId) {
            throw new \RuntimeException('Buat atau aktifkan periode sebelum menjalankan seeder kelas.');
        }

        $menus = [
            ['nama_menu' => 'Menu 1 (P1)', 'rumpun' => 'eksakta', 'kuota_kapasitas' => 36],
            ['nama_menu' => 'Menu 2 (P2)', 'rumpun' => 'eksakta', 'kuota_kapasitas' => 72],
            ['nama_menu' => 'Menu 3 (P3)', 'rumpun' => 'eksakta', 'kuota_kapasitas' => 72],
            ['nama_menu' => 'Menu 4 (P4)', 'rumpun' => 'sosial', 'kuota_kapasitas' => 36],
            ['nama_menu' => 'Menu 5 (P5)', 'rumpun' => 'sosial', 'kuota_kapasitas' => 36],
        ];

        $now = now();
        DB::table('paket_menu_pilihan')->insertOrIgnore(array_map(fn (array $menu) => [
            ...$menu,
            'id' => (string) Str::uuid(),
            'periode_id' => $periodeId,
            'kuota_terisi' => 0,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $menus));
    }
}
