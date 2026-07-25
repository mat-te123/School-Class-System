<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Admin
        $adminId = (string) Str::uuid();
        DB::table('users')->updateOrInsert(
            ['username' => 'admin'],
            [
                'id' => $adminId,
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Seed Guru BK
        $guruId = (string) Str::uuid();
        DB::table('users')->updateOrInsert(
            ['username' => 'guru_bk'],
            [
                'id' => $guruId,
                'password' => Hash::make('password'),
                'role' => 'guru_bk',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. Seed Sample Siswa 1
        $siswaUser1Id = (string) Str::uuid();
        DB::table('users')->updateOrInsert(
            ['username' => '1234567890'],
            [
                'id' => $siswaUser1Id,
                'password' => Hash::make('password'),
                'role' => 'siswa',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('siswa')->updateOrInsert(
            ['nisn' => '1234567890'],
            [
                'id' => (string) Str::uuid(),
                'users_id' => $siswaUser1Id,
                'nis' => '1001',
                'nama_lengkap' => 'Ahmad Fauzi',
                'kelas_asal' => 'X A',
                'jenis_kelamin' => 'L',
                'created_at' => now(),
            ]
        );

        // 4. Seed Sample Siswa 2
        $siswaUser2Id = (string) Str::uuid();
        DB::table('users')->updateOrInsert(
            ['username' => '0987654321'],
            [
                'id' => $siswaUser2Id,
                'password' => Hash::make('password'),
                'role' => 'siswa',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('siswa')->updateOrInsert(
            ['nisn' => '0987654321'],
            [
                'id' => (string) Str::uuid(),
                'users_id' => $siswaUser2Id,
                'nis' => '1002',
                'nama_lengkap' => 'Siti Aminah',
                'kelas_asal' => 'X B',
                'jenis_kelamin' => 'P',
                'created_at' => now(),
            ]
        );
    }
}
