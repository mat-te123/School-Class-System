<?php

namespace Tests\Feature;

use App\Models\KriteriaBobotMenu;
use App\Models\MasterMataPelajaran;
use App\Models\PaketMenuPilihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KriteriaBobotMenuControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_kriteria_bobot_menu(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'test_user_kriteria',
            'password' => 'password123',
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $paket = PaketMenuPilihan::create([
            'kode_menu' => 1,
            'nama_menu' => 'Menu 1 (P1)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
        ]);

        $mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'MAT_U',
            'nama_mapel' => 'Matematika Umum',
            'kelompok_mapel' => 'umum',
        ]);

        KriteriaBobotMenu::create([
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel->id,
            'bobot_persen' => 75.50,
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/kriteria-bobot-menu?paket_menu_pilihan_id=' . $paket->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data.data');

        $this->assertEquals(1, $response->json('data.total'));
        $response->assertJsonPath('data.data.0.bobot_persen', 75.5)
            ->assertJsonPath('data.data.0.master_mata_pelajaran.nama_mapel', 'Matematika Umum');
    }

    public function test_admin_can_set_kriteria_bobot_menu_single(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_bobot1',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paket = PaketMenuPilihan::create([
            'kode_menu' => 2,
            'nama_menu' => 'Menu 2 (P2)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 72,
        ]);

        $mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'BIO',
            'nama_mapel' => 'Biologi',
            'kelompok_mapel' => 'pilihan',
        ]);

        $response = $this->actingAs($admin, 'web')->postJson('/kriteria-bobot-menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel->id,
            'bobot_persen' => 60.00,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menyimpan kriteria bobot menu.',
            ]);

        $this->assertDatabaseHas('kriteria_bobot_menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel->id,
            'bobot_persen' => 60.00,
        ]);
    }

    public function test_admin_can_set_kriteria_bobot_menu_bulk(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_bobot2',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paket = PaketMenuPilihan::create([
            'kode_menu' => 3,
            'nama_menu' => 'Menu 3 (P3)',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 72,
        ]);

        $mapel1 = MasterMataPelajaran::create([
            'kode_mapel' => 'FIS',
            'nama_mapel' => 'Fisika',
            'kelompok_mapel' => 'pilihan',
        ]);

        $mapel2 = MasterMataPelajaran::create([
            'kode_mapel' => 'KIM',
            'nama_mapel' => 'Kimia',
            'kelompok_mapel' => 'pilihan',
        ]);

        $response = $this->actingAs($admin, 'web')->postJson('/kriteria-bobot-menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'kriteria' => [
                [
                    'master_mata_pelajaran_id' => $mapel1->id,
                    'bobot_persen' => 40.00,
                ],
                [
                    'master_mata_pelajaran_id' => $mapel2->id,
                    'bobot_persen' => 60.00,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'total' => 2,
            ]);

        $this->assertDatabaseHas('kriteria_bobot_menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel1->id,
            'bobot_persen' => 40.00,
        ]);

        $this->assertDatabaseHas('kriteria_bobot_menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel2->id,
            'bobot_persen' => 60.00,
        ]);
    }

    public function test_non_admin_cannot_set_kriteria_bobot(): void
    {
        $guruBk = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'guru_bk_bobot',
            'password' => 'password123',
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $paket = PaketMenuPilihan::create([
            'kode_menu' => 4,
            'nama_menu' => 'Menu 4',
            'rumpun' => 'sosial',
        ]);

        $mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'EKO',
            'nama_mapel' => 'Ekonomi',
        ]);

        $response = $this->actingAs($guruBk, 'web')->postJson('/kriteria-bobot-menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel->id,
            'bobot_persen' => 50.00,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_kriteria_bobot(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_bobot3',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paket = PaketMenuPilihan::create([
            'kode_menu' => 5,
            'nama_menu' => 'Menu 5',
            'rumpun' => 'sosial',
        ]);

        $mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'GEO',
            'nama_mapel' => 'Geografi',
        ]);

        $bobot = KriteriaBobotMenu::create([
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel->id,
            'bobot_persen' => 100.00,
        ]);

        $response = $this->actingAs($admin, 'web')->deleteJson('/kriteria-bobot-menu/' . $bobot->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menghapus kriteria bobot menu.',
            ]);

        $this->assertDatabaseMissing('kriteria_bobot_menu', [
            'id' => $bobot->id,
        ]);
    }

    public function test_admin_can_update_kriteria_bobot(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_bobot4',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paket = PaketMenuPilihan::create([
            'nama_menu' => 'Menu 6',
            'rumpun' => 'sosial',
        ]);

        $mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'SOS',
            'nama_mapel' => 'Sosiologi',
        ]);

        $bobot = KriteriaBobotMenu::create([
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel->id,
            'bobot_persen' => 50.00,
        ]);

        $response = $this->actingAs($admin, 'web')->putJson('/kriteria-bobot-menu/' . $bobot->id, [
            'bobot_persen' => 75.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil memperbarui kriteria bobot menu.',
                'data' => [
                    'id' => $bobot->id,
                    'bobot_persen' => 75,
                ],
            ]);

        $this->assertDatabaseHas('kriteria_bobot_menu', [
            'id' => $bobot->id,
            'bobot_persen' => 75.00,
        ]);
    }

    public function test_admin_can_update_kriteria_bobot_bulk_array_by_paket_menu_id(): void
    {
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_bobot5',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $paket = PaketMenuPilihan::create([
            'nama_menu' => 'Menu 7 (P7)',
            'rumpun' => 'eksakta',
        ]);

        $mapel1 = MasterMataPelajaran::create([
            'kode_mapel' => 'MAPEL_1',
            'nama_mapel' => 'Mapel 1',
        ]);

        $mapel2 = MasterMataPelajaran::create([
            'kode_mapel' => 'MAPEL_2',
            'nama_mapel' => 'Mapel 2',
        ]);

        $response = $this->actingAs($admin, 'web')->putJson('/kriteria-bobot-menu/' . $paket->id, [
            [
                'master_mata_pelajaran_id' => $mapel1->id,
                'bobot_persen' => 50.00,
            ],
            [
                'master_mata_pelajaran_id' => $mapel2->id,
                'bobot_persen' => 60.00,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total' => 2,
            ]);

        $this->assertDatabaseHas('kriteria_bobot_menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel1->id,
            'bobot_persen' => 50.00,
        ]);

        $this->assertDatabaseHas('kriteria_bobot_menu', [
            'paket_menu_pilihan_id' => $paket->id,
            'master_mata_pelajaran_id' => $mapel2->id,
            'bobot_persen' => 60.00,
        ]);
    }
}
