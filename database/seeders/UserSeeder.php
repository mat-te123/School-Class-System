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
    }
}
