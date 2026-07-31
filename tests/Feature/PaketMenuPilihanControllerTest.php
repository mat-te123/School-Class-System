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
        PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'kode_menu' => 1,
            'nama_menu' => 'Menu 1 (P1)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 10,
            'is_active' => true,
        ]);

        PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'kode_menu' => 4,
            'nama_menu' => 'Menu 4 (P4)',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 5,
            'is_active' => true,
        ]);

        $response = $this->getJson('/paket-menu-pilihan');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 2,
            ])
            ->assertJsonPath('data.0.kode_menu', 1)
            ->assertJsonPath('data.0.kuota_tersisa', 26)
            ->assertJsonPath('data.1.kode_menu', 4)
            ->assertJsonPath('data.1.kuota_tersisa', 31);
    }

    /**
     * Test filter Paket Menu Pilihan berdasarkan rumpun (eksakta/sosial).
     */
    public function test_can_filter_paket_menu_pilihan_by_rumpun(): void
    {
        PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'kode_menu' => 1,
            'nama_menu' => 'Menu 1 (P1)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'kode_menu' => 4,
            'nama_menu' => 'Menu 4 (P4)',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $response = $this->getJson('/paket-menu-pilihan?rumpun=sosial');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 1,
            ])
            ->assertJsonPath('data.0.rumpun', 'sosial')
            ->assertJsonPath('data.0.kode_menu', 4);
    }

    /**
     * Test detail Paket Menu Pilihan berdasarkan ID atau Kode Menu.
     */
    public function test_can_get_detail_paket_menu_pilihan_by_kode_or_id(): void
    {
        $paketMenu = PaketMenuPilihan::create([
            'kode_menu' => 2,
            'nama_menu' => 'Menu 2 (P2)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 72,
            'kuota_terisi' => 20,
            'is_active' => true,
        ]);

        // Cek via kode_menu
        $responseKode = $this->getJson('/paket-menu-pilihan/2');
        $responseKode->assertStatus(200)
            ->assertJsonPath('data.nama_menu', 'Menu 2 (P2)')
            ->assertJsonPath('data.kuota_tersisa', 52);

        // Cek via UUID id
        $responseId = $this->getJson('/paket-menu-pilihan/' . $paketMenu->id);
        $responseId->assertStatus(200)
            ->assertJsonPath('data.kode_menu', 2);
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
            'kode_menu' => 6,
            'nama_menu' => 'Menu 6 (P6)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 40,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menambahkan Paket Menu Pilihan baru.',
                'data' => [
                    'kode_menu' => 6,
                    'nama_menu' => 'Menu 6 (P6)',
                    'rumpun' => 'eksakta',
                    'kuota_kapasitas' => 40,
                ],
            ]);

        $this->assertDatabaseHas('paket_menu_pilihan', [
            'kode_menu' => 6,
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
            'kode_menu' => 7,
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
            'kode_menu' => 1,
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
            'kode_menu' => 99,
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

        $this->assertDatabaseMissing('paket_menu_pilihan', [
            'id' => $paketMenu->id,
        ]);
    }
}
