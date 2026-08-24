<?php

namespace Tests\Feature;

use App\Models\ProyeksiUniversitas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProyeksiUniversitasControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::create([
            'id'        => (string) Str::uuid(),
            'username'  => 'admin_proyeksi',
            'password'  => 'password123',
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_migration_creates_proyeksi_universitas_table(): void
    {
        ProyeksiUniversitas::create([
            'nama_universitas' => 'Universitas Indonesia',
            'singkatan'        => 'UI',
            'akreditasi'       => 'Unggul',
            'lokasi_kota'      => 'Depok',
            'lokasi_provinsi'  => 'Jawa Barat',
            'tahun_data'       => 2024,
            'is_active'        => true,
        ]);

        $this->assertDatabaseHas('proyeksi_universitas', ['singkatan' => 'UI']);
    }

    public function test_admin_can_create_proyeksi_universitas(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'web')->postJson('/proyeksi-universitas', [
            'nama_universitas' => 'Institut Teknologi Bandung',
            'singkatan'        => 'ITB',
            'akreditasi'       => 'Unggul',
            'lokasi_kota'      => 'Bandung',
            'lokasi_provinsi'  => 'Jawa Barat',
            'tahun_data'       => 2024,
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('proyeksi_universitas', ['singkatan' => 'ITB']);
    }

    public function test_non_admin_cannot_create_proyeksi_universitas(): void
    {
        $guru = User::create([
            'id'        => (string) Str::uuid(),
            'username'  => 'guru_bk_proyeksi',
            'password'  => 'password123',
            'role'      => 'guru_bk',
            'is_active' => true,
        ]);

        $response = $this->actingAs($guru, 'web')->postJson('/proyeksi-universitas', [
            'nama_universitas' => 'Universitas Tolak',
        ]);

        $response->assertStatus(403);
    }

    public function test_store_validates_required_nama_universitas(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'web')->postJson('/proyeksi-universitas', []);

        $response->assertStatus(422)->assertJsonValidationErrors('nama_universitas');
    }

    public function test_can_list_proyeksi_universitas(): void
    {
        $admin = $this->makeAdmin();

        ProyeksiUniversitas::create([
            'nama_universitas' => 'Universitas Gadjah Mada',
            'singkatan'        => 'UGM',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($admin, 'web')->getJson('/proyeksi-universitas');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(1, 'data.data');
    }

    public function test_can_search_proyeksi_universitas(): void
    {
        $admin = $this->makeAdmin();

        ProyeksiUniversitas::create([
            'nama_universitas' => 'Universitas Gadjah Mada',
            'singkatan'        => 'UGM',
            'is_active'        => true,
        ]);

        ProyeksiUniversitas::create([
            'nama_universitas' => 'Universitas Airlangga',
            'singkatan'        => 'UNAIR',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($admin, 'web')->getJson('/proyeksi-universitas?search=UGM');

        $response->assertStatus(200)->assertJsonCount(1, 'data.data');
    }

    public function test_show_includes_program_studis(): void
    {
        $admin = $this->makeAdmin();

        $univ = ProyeksiUniversitas::create([
            'nama_universitas' => 'Universitas Gadjah Mada',
            'singkatan'        => 'UGM',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($admin, 'web')->getJson("/proyeksi-universitas/{$univ->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.singkatan', 'UGM')
                 ->assertJsonStructure(['data' => ['program_studis']]);
    }

    public function test_admin_can_update_proyeksi_universitas(): void
    {
        $admin = $this->makeAdmin();

        $univ = ProyeksiUniversitas::create([
            'nama_universitas' => 'Universitas Gadjah Mada',
            'singkatan'        => 'UGM',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($admin, 'web')->putJson("/proyeksi-universitas/{$univ->id}", [
            'akreditasi' => 'Unggul',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('proyeksi_universitas', [
            'id'         => $univ->id,
            'akreditasi' => 'Unggul',
        ]);
    }

    public function test_admin_can_delete_proyeksi_universitas(): void
    {
        $admin = $this->makeAdmin();

        $univ = ProyeksiUniversitas::create([
            'nama_universitas' => 'Universitas Hapus',
            'singkatan'        => 'UH',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($admin, 'web')->deleteJson("/proyeksi-universitas/{$univ->id}");

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSoftDeleted('proyeksi_universitas', ['id' => $univ->id]);
    }

    public function test_siswa_can_read_proyeksi_universitas(): void
    {
        $siswa = Siswa::create([
            'id'           => (string) Str::uuid(),
            'nisn'         => '0011223344',
            'nis'          => '11001',
            'nama_lengkap' => 'Siswa Baca Proyeksi',
            'password'     => 'password123',
            'is_active'    => true,
        ]);

        ProyeksiUniversitas::create([
            'nama_universitas' => 'Universitas Indonesia',
            'singkatan'        => 'UI',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($siswa, 'siswa')->getJson('/proyeksi-universitas');

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_guest_cannot_read_proyeksi_universitas(): void
    {
        $response = $this->getJson('/proyeksi-universitas');

        $response->assertStatus(401);
    }
}
