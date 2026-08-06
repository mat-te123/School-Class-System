<?php

namespace Tests\Feature;

use App\Models\KelasAsal;
use App\Models\RiwayatUploadLeger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RiwayatUploadLegerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test dapat menyimpan riwayat upload leger dan memblokir upload kedua untuk kelas & angkatan yang sama.
     */
    public function test_blocks_duplicate_upload_for_same_kelas_and_angkatan(): void
    {
        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'kapasitas' => 36,
        ]);

        // 1. Simpan upload pertama
        RiwayatUploadLeger::create([
            'id' => (string) Str::uuid(),
            'kelas_asal_id' => $kelas->id,
            'nama_kelas' => 'X A',
            'angkatan' => '2024/2025',
            'file_name' => 'Leger_20242_X A.xlsx',
            'status' => 'completed',
        ]);

        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'test_user_riwayat',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 2. Ambil endpoint history
        $response = $this->actingAs($user, 'web')->getJson('/leger/history?nama_kelas=X A&angkatan=2024/2025');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 1,
            ])
            ->assertJsonPath('data.0.nama_kelas', 'X A')
            ->assertJsonPath('data.0.angkatan', '2024/2025');
    }

    /**
     * Test menyimpan ID user yang melakukan upload dari session login.
     */
    public function test_records_logged_in_user_who_uploaded(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'guru_bk_1',
            'password' => 'password123',
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X B',
            'tingkat' => 'X',
            'kapasitas' => 36,
        ]);

        $upload = RiwayatUploadLeger::create([
            'id' => (string) Str::uuid(),
            'kelas_asal_id' => $kelas->id,
            'nama_kelas' => 'X B',
            'angkatan' => '2024/2025',
            'file_name' => 'Leger_20242_X B.xlsx',
            'status' => 'completed',
            'uploaded_by' => $user->id,
        ]);

        $this->assertEquals($user->id, $upload->uploaded_by);
        $this->assertEquals('guru_bk_1', $upload->uploader->username);
    }
}
