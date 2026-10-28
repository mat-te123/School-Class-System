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
        $periodeName = 'Pemilihan Mapel Fase F 2026/2027';
        $existing = DB::table('periode_pendaftaran')->where('nama_periode', $periodeName)->first();

        if ($existing) {
            DB::table('periode_pendaftaran')
                ->where('id', $existing->id)
                ->update([
                    'tahun_ajaran' => '2026/2027',
                    'gelombang' => 'Utama',
                    'max_pilihan_siswa' => 3,
                    'tanggal_buka' => now()->subDay(),
                    'tanggal_tutup' => now()->addMonth(),
                    'status_pengumuman' => 'NON-AKTIF',
                    'is_active' => true,
                ]);
        } else {
            DB::table('periode_pendaftaran')->insert([
                'id' => (string) Str::uuid(),
                'nama_periode' => $periodeName,
                'tahun_ajaran' => '2026/2027',
                'gelombang' => 'Utama',
                'max_pilihan_siswa' => 3,
                'tanggal_buka' => now()->subDay(),
                'tanggal_tutup' => now()->addMonth(),
                'status_pengumuman' => 'NON-AKTIF',
                'is_active' => true,
                'created_at' => now(),
            ]);
        }
    }
}
