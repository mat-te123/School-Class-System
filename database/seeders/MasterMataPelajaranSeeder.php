<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterMataPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            ['kode_mapel' => 'MAT_U', 'nama_mapel' => 'Matematika Utama', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => true],
            ['kode_mapel' => 'IPA', 'nama_mapel' => 'Ilmu Pengetahuan Alam', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => true],
            ['kode_mapel' => 'INFOR', 'nama_mapel' => 'Informatika', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => true],
            ['kode_mapel' => 'IPS', 'nama_mapel' => 'Ilmu Pengetahuan Sosial', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => true],
            ['kode_mapel' => 'BING', 'nama_mapel' => 'Bahasa Inggris', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => true],
            ['kode_mapel' => 'EKO', 'nama_mapel' => 'Ekonomi', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => true],
            ['kode_mapel' => 'PAIBP', 'nama_mapel' => 'Pendidikan Agama dan Budi Pekerti', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => false],
            ['kode_mapel' => 'PKN', 'nama_mapel' => 'Pendidikan Pancasila dan Kewarganegaraan', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => false],
            ['kode_mapel' => 'BIND', 'nama_mapel' => 'Bahasa Indonesia', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => false],
            ['kode_mapel' => 'PJOK', 'nama_mapel' => 'PJOK', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => false],
            ['kode_mapel' => 'SENBUD', 'nama_mapel' => 'Seni dan Budaya', 'kelompok_mapel' => 'umum', 'is_tiebreaker_default' => false],
            ['kode_mapel' => 'MULOK', 'nama_mapel' => 'Bahasa Jawa / Mulok', 'kelompok_mapel' => 'muatan_lokal', 'is_tiebreaker_default' => false],
        ];

        foreach ($subjects as $subj) {
            DB::table('master_mata_pelajaran')->updateOrInsert(
                ['kode_mapel' => $subj['kode_mapel']],
                [
                    'id' => (string) Str::uuid(),
                    'nama_mapel' => $subj['nama_mapel'],
                    'kelompok_mapel' => $subj['kelompok_mapel'],
                    'is_tiebreaker_default' => $subj['is_tiebreaker_default'],
                    'is_active' => true,
                    'created_at' => now(),
                ]
            );
        }
    }
}
