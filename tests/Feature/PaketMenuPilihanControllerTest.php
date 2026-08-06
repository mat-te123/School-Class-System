<?php

namespace Tests\Feature;

use App\Models\PaketMenuPilihan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaketMenuPilihanControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test mengambil semua daftar Paket Menu Pilihan (index).
     */
    public function test_can_get_all_active_paket_menu_pilihan(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'test_user_paket',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Menu 1 (P1)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 10,
            'is_active' => true,
        ]);

        PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Menu 4 (P4)',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/paket-menu-pilihan');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 2,
            ])
            ->assertJsonPath('data.0.nama_menu', 'Menu 1 (P1)')
            ->assertJsonPath('data.0.kuota_tersisa', 26)
            ->assertJsonPath('data.1.nama_menu', 'Menu 4 (P4)')
            ->assertJsonPath('data.1.kuota_tersisa', 31);
    }

    /**
     * Test filter Paket Menu Pilihan berdasarkan rumpun (eksakta/sosial).
     */
    public function test_can_filter_paket_menu_pilihan_by_rumpun(): void
    {
        $siswa = \App\Models\Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '0011223344',
            'nis' => '12346',
            'nama_lengkap' => 'Siswa Test Paket',
            'password' => 'password123',
            'is_active' => true,
        ]);

        PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Menu 1 (P1)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Menu 4 (P4)',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($siswa, 'siswa')->getJson('/paket-menu-pilihan?rumpun=sosial');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 1,
            ])
            ->assertJsonPath('data.0.rumpun', 'sosial')
            ->assertJsonPath('data.0.nama_menu', 'Menu 4 (P4)');
    }

    /**
     * Test detail Paket Menu Pilihan berdasarkan ID atau Nama Menu.
     */
    public function test_can_get_detail_paket_menu_pilihan_by_nama_or_id(): void
    {
        $user = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'test_user_paket_detail',
            'password' => 'password123',
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $paketMenu = PaketMenuPilihan::create([
            'nama_menu' => 'Menu 2 (P2)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 72,
            'kuota_terisi' => 20,
            'is_active' => true,
        ]);

        // Cek via nama_menu
        $responseNama = $this->actingAs($user, 'web')->getJson('/paket-menu-pilihan/Menu 2 (P2)');
        $responseNama->assertStatus(200)
            ->assertJsonPath('data.nama_menu', 'Menu 2 (P2)')
            ->assertJsonPath('data.kuota_tersisa', 52);

        // Cek via UUID id
        $responseId = $this->actingAs($user, 'web')->getJson('/paket-menu-pilihan/' . $paketMenu->id);
        $responseId->assertStatus(200)
            ->assertJsonPath('data.nama_menu', 'Menu 2 (P2)');
    }

    public function test_admin_can_create_paket_menu_pilihan(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_menu',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->postJson('/paket-menu-pilihan', [
            'nama_menu' => 'Menu 6 (P6)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 40,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menambahkan Paket Menu Pilihan baru.',
                'data' => [
                    'nama_menu' => 'Menu 6 (P6)',
                    'rumpun' => 'eksakta',
                    'kuota_kapasitas' => 40,
                ],
            ]);

        $this->assertDatabaseHas('paket_menu_pilihan', [
            'nama_menu' => 'Menu 6 (P6)',
        ]);
    }

    public function test_non_admin_cannot_create_paket_menu_pilihan(): void
    {
        $guruBk = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'guru_bk_menu',
            'password' => 'password123',
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $response = $this->actingAs($guruBk, 'web')->postJson('/paket-menu-pilihan', [
            'nama_menu' => 'Menu 7 (P7)',
            'rumpun' => 'sosial',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_paket_menu_pilihan(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_menu2',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paketMenu = PaketMenuPilihan::create([
            'nama_menu' => 'Menu 1 (P1)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->putJson('/paket-menu-pilihan/' . $paketMenu->id, [
            'nama_menu' => 'Menu 1 Revised',
            'kuota_kapasitas' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nama_menu' => 'Menu 1 Revised',
                    'kuota_kapasitas' => 50,
                ],
            ]);
    }

    public function test_admin_can_delete_paket_menu_pilihan(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_menu3',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paketMenu = PaketMenuPilihan::create([
            'nama_menu' => 'Menu Temp',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'web')->deleteJson('/paket-menu-pilihan/' . $paketMenu->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menghapus Paket Menu Pilihan.',
            ]);

        $this->assertSoftDeleted('paket_menu_pilihan', [
            'id' => $paketMenu->id,
        ]);
    }

    public function test_create_paket_menu_with_soft_deleted_name_returns_409_conflict(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_menu_conflict_1',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paketMenu = PaketMenuPilihan::create([
            'nama_menu' => 'Menu 10',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'is_active' => true,
        ]);
        $paketMenu->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/paket-menu-pilihan', [
            'nama_menu' => 'Menu 10',
            'rumpun' => 'eksakta',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'is_trashed' => true,
            ])
            ->assertJsonStructure(['message', 'options' => ['restore', 'overwrite']]);
    }

    public function test_create_paket_menu_with_action_restore_restores_and_updates(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_menu_conflict_2',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paketMenu = PaketMenuPilihan::create([
            'nama_menu' => 'Menu 11 Lama',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 30,
            'is_active' => false,
        ]);
        $oldId = $paketMenu->id;
        $paketMenu->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/paket-menu-pilihan', [
            'nama_menu' => 'Menu 11 Lama',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 45,
            'is_active' => true,
            'action' => 'restore',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $oldId,
                    'nama_menu' => 'Menu 11 Lama',
                    'kuota_kapasitas' => 45,
                ],
            ]);

        $this->assertDatabaseHas('paket_menu_pilihan', [
            'id' => $oldId,
            'deleted_at' => null,
            'kuota_kapasitas' => 45,
        ]);
    }

    public function test_create_paket_menu_with_action_overwrite_force_deletes_and_creates_new(): void
    {
        $admin = \App\Models\User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_menu_conflict_3',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paketMenu = PaketMenuPilihan::create([
            'nama_menu' => 'Menu 12 Lama',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 30,
            'is_active' => true,
        ]);
        $oldId = $paketMenu->id;
        $paketMenu->delete();

        $response = $this->actingAs($admin, 'web')->postJson('/paket-menu-pilihan', [
            'nama_menu' => 'Menu 12 Lama',
            'rumpun' => 'eksakta',
            'action' => 'overwrite',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $newId = $response->json('data.id');
        $this->assertNotEquals($oldId, $newId);
        $this->assertDatabaseMissing('paket_menu_pilihan', ['id' => $oldId]);
        $this->assertDatabaseHas('paket_menu_pilihan', ['id' => $newId, 'nama_menu' => 'Menu 12 Lama']);
    }
}
