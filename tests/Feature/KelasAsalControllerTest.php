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

    public function test_can_get_all_kelas(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'test_user_kelas',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas1 = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
        ]);

        $kelas2 = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XI IPA 1',
            'tingkat' => 'XI',
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/kelas-asal');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data.data');

        $this->assertEquals(2, $response->json('data.total'));
    }

    public function test_can_filter_kelas_by_tingkat(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'test_user_kelas_filter',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
        ]);

        KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XI IPA 1',
            'tingkat' => 'XI',
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/kelas-asal?tingkat=X');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data.data');

        $this->assertEquals('X A', $response->json('data.data.0.nama_kelas'));
    }

    public function test_can_get_detail_kelas_by_id_or_nama(): void
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

    public function test_admin_can_create_kelas(): void
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
            'tingkat' => 'X',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menambahkan Kelas baru.',
                'data' => [
                    'nama_kelas' => 'X H',
                    'tingkat' => 'X',
                ],
            ]);

        $this->assertDatabaseHas('kelas_asal', [
            'nama_kelas' => 'X H',
            'tingkat' => 'X',
        ]);
    }

    public function test_non_admin_cannot_create_kelas(): void
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

    public function test_admin_can_update_kelas(): void
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
        ]);

        $response = $this->actingAs($admin, 'web')->putJson('/kelas-asal/' . $kelas->id, [
            'nama_kelas' => 'X A Unggulan',
            'tingkat' => 'X',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama_kelas' => 'X A Unggulan',
                    'tingkat' => 'X',
                ],
            ]);
    }

    public function test_admin_can_delete_kelas(): void
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
        ]);

        $response = $this->actingAs($admin, 'web')->deleteJson('/kelas-asal/' . $kelas->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menghapus data Kelas.',
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
        ]);
        $kelas->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X D',
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
        ]);
        $oldId = $kelas->id;
        $kelas->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X E',
            'tingkat' => 'X',
            'action' => 'restore',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "Kelas 'X E' yang sebelumnya terhapus berhasil dipulihkan dan diperbarui.",
                'data' => [
                    'id' => $oldId,
                    'nama_kelas' => 'X E',
                    'tingkat' => 'X',
                ],
            ]);

        $this->assertDatabaseHas('kelas_asal', [
            'id' => $oldId,
            'deleted_at' => null,
            'tingkat' => 'X',
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
        ]);
        $kelas->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/kelas-asal', [
            'nama_kelas' => 'X F',
            'tingkat' => 'X',
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
        $this->assertDatabaseHas('kelas_asal', ['id' => $newId, 'nama_kelas' => 'X F', 'tingkat' => 'X']);
    }

    /** Paginasi server-side: per_page & meta paginator benar */
    public function test_admin_can_get_paginated_kelas_json(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_paginate_kelas',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 8; $i++) {
            KelasAsal::create([
                'id' => (string) Str::uuid(),
                'nama_kelas' => "X {$i}",
                'tingkat' => 'X',
            ]);
        }

        $response = $this->actingAs($user, 'web')->getJson('/kelas-asal?per_page=3&page=1');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data.data');

        $this->assertEquals(8, $response->json('data.total'));
        $this->assertEquals(3, $response->json('data.per_page'));
        $this->assertEquals(3, $response->json('data.last_page'));
    }

    /** Pencarian berdasarkan substring nama_kelas */
    public function test_admin_can_search_kelas_by_nama(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_search_kelas',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
        ]);
        KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XI IPA 1',
            'tingkat' => 'XI',
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/kelas-asal?search=X A');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('X A', $response->json('data.data.0.nama_kelas'));
    }

    /** Request web index mengembalikan response sukses */
    public function test_authenticated_user_can_access_kelas_index(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'user_view_kelas',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'web')->get('/kelas-asal');

        $response->assertStatus(200);
    }

    /** Request browser non-JSON redirect ke halaman asal setelah store */
    public function test_browser_store_redirects_back_with_success_flash(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'user_store_kelas',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'web')
            ->from('/kelas-asal')
            ->post('/kelas-asal', [
                'nama_kelas' => 'X C',
                'tingkat' => 'X',
            ]);

        $response->assertRedirect('/kelas-asal');
        $response->assertSessionHas('success', 'Berhasil menambahkan Kelas baru.');
        $this->assertDatabaseHas('kelas_asal', ['nama_kelas' => 'X C', 'tingkat' => 'X']);
    }

    /** Request browser non-JSON redirect ke halaman asal setelah update */
    public function test_browser_update_redirects_back_with_success_flash(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'user_update_kelas',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X D',
            'tingkat' => 'X',
        ]);

        $response = $this->actingAs($user, 'web')
            ->from('/kelas-asal')
            ->put('/kelas-asal/' . $kelas->id, [
                'nama_kelas' => 'X D Updated',
                'tingkat' => 'X',
            ]);

        $response->assertRedirect('/kelas-asal');
        $response->assertSessionHas('success', 'Berhasil memperbarui data Kelas.');
        $this->assertDatabaseHas('kelas_asal', ['id' => $kelas->id, 'nama_kelas' => 'X D Updated']);
    }

    /** Request browser non-JSON redirect ke halaman asal setelah destroy */
    public function test_browser_destroy_redirects_back_with_success_flash(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'user_destroy_kelas',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X E',
            'tingkat' => 'X',
        ]);

        $response = $this->actingAs($user, 'web')
            ->from('/kelas-asal')
            ->delete('/kelas-asal/' . $kelas->id);

        $response->assertRedirect('/kelas-asal');
        $response->assertSessionHas('success', 'Berhasil menghapus data Kelas.');
        $this->assertSoftDeleted('kelas_asal', ['id' => $kelas->id]);
    }
}
