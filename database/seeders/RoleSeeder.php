<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'label' => 'Administrator',
                'description' => 'Akses penuh ke seluruh sistem, konfigurasi, dan manajemen pengguna',
            ],
            [
                'name' => 'guru_bk',
                'label' => 'Guru BK',
                'description' => 'Mengelola leger, kriteria bobot, dan mengeksekusi hasil seleksi siswa',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'id' => (string) Str::uuid(),
                    'label' => $role['label'],
                    'description' => $role['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
