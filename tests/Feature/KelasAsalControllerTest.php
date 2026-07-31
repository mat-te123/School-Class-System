<?php

namespace Tests\Feature;

use App\Models\KelasAsal;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KelasAsalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_kelas_x(): void
    {
        $kelasX1 = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $kelasX2 = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X B',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        // Kelas XI (should be ignored)
        KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XI IPA 1',
            'tingkat' => 'XI',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $response = $this->getJson('/kelas-asal');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 2,
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_detail_kelas_x_by_id_or_nama(): void
    {
        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $response = $this->getJson('/kelas-asal/' . $kelas->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $kelas->id,
                    'nama_kelas' => 'X A',
                    'tingkat' => 'X',
                ],
            ]);
    }

    public function test_admin_can_create_kelas_x(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_user',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X H',
            'kapasitas' => 32,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menambahkan Kelas X baru.',
                'data' => [
                    'nama_kelas' => 'X H',
                    'tingkat' => 'X',
                    'kapasitas' => 32,
                ],
            ]);

        $this->assertDatabaseHas('kelas_asal', [
            'nama_kelas' => 'X H',
            'tingkat' => 'X',
        ]);
    }

    public function test_non_admin_cannot_create_kelas_x(): void
    {
        $guruBk = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'guru_bk_user',
            'password' => 'password123',
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $response = $this->actingAs($guruBk, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X I',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_kelas_x(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_user2',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->putJson('/kelas-asal/' . $kelas->id, [
            'nama_kelas' => 'X A Unggulan',
            'kapasitas' => 40,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama_kelas' => 'X A Unggulan',
                    'kapasitas' => 40,
                ],
            ]);
    }

    public function test_admin_can_delete_kelas_x(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_user3',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X Z',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->deleteJson('/kelas-asal/' . $kelas->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menghapus data Kelas X.',
            ]);

        $this->assertDatabaseMissing('kelas_asal', [
            'id' => $kelas->id,
        ]);
    }
}
