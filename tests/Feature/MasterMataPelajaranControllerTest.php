<?php

namespace Tests\Feature;

use App\Models\MasterMataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MasterMataPelajaranControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_mapel',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_can_create_master_mata_pelajaran(): void
    {
        $payload = [
            'kode_mapel' => 'MAT_W',
            'nama_mapel' => 'Matematika Wajib',
            'kelompok_mapel' => 'umum',
            'is_tiebreaker_default' => true,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'web')->postJson('/master-mata-pelajaran', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menambahkan Master Mata Pelajaran baru.',
                'data' => [
                    'kode_mapel' => 'MAT_W',
                    'nama_mapel' => 'Matematika Wajib',
                    'kelompok_mapel' => 'umum',
                    'is_tiebreaker_default' => true,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('master_mata_pelajaran', [
            'kode_mapel' => 'MAT_W',
            'nama_mapel' => 'Matematika Wajib',
            'is_tiebreaker_default' => true,
        ]);
    }

    public function test_create_mapel_fails_validation_for_duplicate_kode_mapel(): void
    {
        MasterMataPelajaran::create([
            'id' => (string) Str::uuid(),
            'kode_mapel' => 'MAT_W',
            'nama_mapel' => 'Matematika Wajib Lama',
            'kelompok_mapel' => 'umum',
            'is_tiebreaker_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'web')->postJson('/master-mata-pelajaran', [
            'kode_mapel' => 'MAT_W',
            'nama_mapel' => 'Matematika Wajib Baru',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['kode_mapel']]);
    }

    public function test_can_get_all_master_mata_pelajaran(): void
    {
        MasterMataPelajaran::create([
            'id' => (string) Str::uuid(),
            'kode_mapel' => 'MAT_U',
            'nama_mapel' => 'Matematika Umum',
            'kelompok_mapel' => 'umum',
            'is_tiebreaker_default' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'web')->getJson('/master-mata-pelajaran');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data.data');

        $this->assertEquals(1, $response->json('data.total'));
        $response->assertJsonPath('data.data.0.kode_mapel', 'MAT_U');
    }

    public function test_can_update_master_mata_pelajaran(): void
    {
        $mapel = MasterMataPelajaran::create([
            'id' => (string) Str::uuid(),
            'kode_mapel' => 'BIO_1',
            'nama_mapel' => 'Biologi',
            'kelompok_mapel' => 'pilihan',
            'is_tiebreaker_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'web')->putJson('/master-mata-pelajaran/' . $mapel->id, [
            'nama_mapel' => 'Biologi Terapan',
            'is_tiebreaker_default' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama_mapel' => 'Biologi Terapan',
                    'is_tiebreaker_default' => true,
                ],
            ]);
    }

    public function test_can_update_is_tiebreaker_default_to_false_and_update_by_kode_mapel(): void
    {
        $mapel = MasterMataPelajaran::create([
            'id' => (string) Str::uuid(),
            'kode_mapel' => 'MAT_U',
            'nama_mapel' => 'Matematika Umum',
            'kelompok_mapel' => 'umum',
            'is_tiebreaker_default' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'web')->putJson('/master-mata-pelajaran/MAT_U', [
            'is_tiebreaker_default' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'kode_mapel' => 'MAT_U',
                    'is_tiebreaker_default' => false,
                ],
            ]);

        $this->assertDatabaseHas('master_mata_pelajaran', [
            'id' => $mapel->id,
            'is_tiebreaker_default' => false,
        ]);
    }

    public function test_can_delete_master_mata_pelajaran(): void
    {
        $mapel = MasterMataPelajaran::create([
            'id' => (string) Str::uuid(),
            'kode_mapel' => 'KIM_1',
            'nama_mapel' => 'Kimia',
            'kelompok_mapel' => 'pilihan',
            'is_tiebreaker_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'web')->deleteJson('/master-mata-pelajaran/' . $mapel->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menghapus data Master Mata Pelajaran.',
            ]);

        $this->assertSoftDeleted('master_mata_pelajaran', [
            'id' => $mapel->id,
        ]);
    }

    public function test_create_mapel_with_soft_deleted_code_returns_409_conflict(): void
    {
        $mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'FIS_1',
            'nama_mapel' => 'Fisika Dasar',
            'kelompok_mapel' => 'pilihan',
            'is_tiebreaker_default' => false,
            'is_active' => true,
        ]);
        $mapel->delete();

        $response = $this->actingAs($this->user, 'web')->postJson('/master-mata-pelajaran', [
            'kode_mapel' => 'FIS_1',
            'nama_mapel' => 'Fisika Lanjutan',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'is_trashed' => true,
            ])
            ->assertJsonStructure(['message', 'options' => ['restore', 'overwrite']]);
    }

    public function test_create_mapel_with_action_restore_restores_and_updates(): void
    {
        $mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'EKO_1',
            'nama_mapel' => 'Ekonomi Lama',
            'kelompok_mapel' => 'pilihan',
            'is_tiebreaker_default' => false,
            'is_active' => false,
        ]);
        $oldId = $mapel->id;
        $mapel->delete();

        $response = $this->actingAs($this->user, 'web')->postJson('/master-mata-pelajaran', [
            'kode_mapel' => 'EKO_1',
            'nama_mapel' => 'Ekonomi Terapan',
            'kelompok_mapel' => 'pilihan',
            'is_tiebreaker_default' => true,
            'is_active' => true,
            'action' => 'restore',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "Mata pelajaran 'EKO_1' yang sebelumnya terhapus berhasil dipulihkan dan diperbarui.",
                'data' => [
                    'id' => $oldId,
                    'kode_mapel' => 'EKO_1',
                    'nama_mapel' => 'Ekonomi Terapan',
                    'is_tiebreaker_default' => true,
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('master_mata_pelajaran', [
            'id' => $oldId,
            'deleted_at' => null,
            'nama_mapel' => 'Ekonomi Terapan',
        ]);
    }

    public function test_create_mapel_with_action_overwrite_force_deletes_and_creates_new(): void
    {
        $mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'SOS_1',
            'nama_mapel' => 'Sosiologi Lama',
            'kelompok_mapel' => 'pilihan',
            'is_tiebreaker_default' => false,
            'is_active' => true,
        ]);
        $oldId = $mapel->id;
        $mapel->delete();

        $response = $this->actingAs($this->user, 'web')->postJson('/master-mata-pelajaran', [
            'kode_mapel' => 'SOS_1',
            'nama_mapel' => 'Sosiologi Baru',
            'kelompok_mapel' => 'pilihan',
            'action' => 'overwrite',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => "Mata pelajaran 'SOS_1' lama telah dihapus permanen dan data mata pelajaran baru berhasil dibuat.",
            ]);

        $newId = $response->json('data.id');
        $this->assertNotEquals($oldId, $newId);
        $this->assertDatabaseMissing('master_mata_pelajaran', ['id' => $oldId]);
        $this->assertDatabaseHas('master_mata_pelajaran', ['id' => $newId, 'kode_mapel' => 'SOS_1', 'nama_mapel' => 'Sosiologi Baru']);
    }
}
