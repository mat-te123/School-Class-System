<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeriodePendaftaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('periode_pendaftaran')->updateOrInsert(
            ['nama_periode' => 'Pemilihan Mapel Fase F 2026/2027'],
            [
                'id' => (string) Str::uuid(),
                'tahun_ajaran' => '2026/2027',
                'gelombang' => 'Utama',
                'max_pilihan_siswa' => 3,
                'tanggal_buka' => '2026-07-01 08:00:00',
                'tanggal_tutup' => '2026-07-15 23:59:59',
                'status_pengumuman' => 'AKTIF',
                'is_active' => true,
                'created_at' => now(),
            ]
        );
    }
}
