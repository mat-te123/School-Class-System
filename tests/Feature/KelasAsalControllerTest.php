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
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'test_user_kelas',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

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

        $response = $this->actingAs($user, 'web')->getJson('/kelas-asal');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 2,
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_detail_kelas_x_by_id_or_nama(): void
    {
        $siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '0098765432',
            'nis' => '12345',
            'nama_lengkap' => 'Siswa Test',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $response = $this->actingAs($siswa, 'siswa')->getJson('/kelas-asal/' . $kelas->id);

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

        $this->assertSoftDeleted('kelas_asal', [
            'id' => $kelas->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_kelas_asal(): void
    {
        $response = $this->getJson('/kelas-asal');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated. Silakan login terlebih dahulu (sebagai Siswa atau Admin/Guru BK).',
            ]);
    }

    public function test_create_kelas_with_soft_deleted_name_returns_409_conflict(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_conflict_1',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X D',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);
        $kelas->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X D',
            'kapasitas' => 40,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'is_trashed' => true,
            ])
            ->assertJsonStructure(['message', 'options' => ['restore', 'overwrite']]);
    }

    public function test_create_kelas_with_action_restore_restores_and_updates(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_conflict_2',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'nama_kelas' => 'X E',
            'tingkat' => 'X',
            'kapasitas' => 30,
            'is_active' => false,
        ]);
        $oldId = $kelas->id;
        $kelas->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X E',
            'kapasitas' => 36,
            'is_active' => true,
            'action' => 'restore',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "Kelas 'X E' yang sebelumnya terhapus berhasil dipulihkan dan diperbarui.",
                'data' => [
                    'id' => $oldId,
                    'nama_kelas' => 'X E',
                    'kapasitas' => 36,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('kelas_asal', [
            'id' => $oldId,
            'deleted_at' => null,
            'kapasitas' => 36,
        ]);
    }

    public function test_create_kelas_with_action_overwrite_force_deletes_and_creates_new(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_conflict_3',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $oldId = (string) Str::uuid();
        $kelas = KelasAsal::create([
            'id' => $oldId,
            'nama_kelas' => 'X F',
            'tingkat' => 'X',
            'kapasitas' => 30,
            'is_active' => true,
        ]);
        $kelas->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X F',
            'kapasitas' => 36,
            'is_active' => true,
            'action' => 'overwrite',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => "Kelas 'X F' lama telah dihapus permanen dan data kelas baru berhasil dibuat.",
            ]);

        $newId = $response->json('data.id');
        $this->assertNotEquals($oldId, $newId);
        $this->assertDatabaseMissing('kelas_asal', ['id' => $oldId]);
        $this->assertDatabaseHas('kelas_asal', ['id' => $newId, 'nama_kelas' => 'X F']);
    }
}
