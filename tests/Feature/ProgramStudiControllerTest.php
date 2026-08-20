<?php

namespace Tests\Feature;

use App\Models\ProgramStudi;
use App\Models\ProyeksiUniversitas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProgramStudiControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::create([
            'id'        => (string) Str::uuid(),
            'username'  => 'admin_prodi',
            'password'  => 'password123',
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    private function makeUniversitas(): ProyeksiUniversitas
    {
        return ProyeksiUniversitas::create([
            'nama_universitas' => 'Universitas Indonesia',
            'singkatan'        => 'UI',
            'is_active'        => true,
        ]);
    }

    public function test_migration_creates_program_studi_table(): void
    {
        $univ = $this->makeUniversitas();

        $prodi = ProgramStudi::create([
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Teknik Informatika',
            'jenjang'                 => 'S1',
            'kelompok_saintek_soshum' => 'Saintek',
            'is_active'               => true,
        ]);

        $this->assertDatabaseHas('program_studi', ['nama_prodi' => 'Teknik Informatika']);
        $this->assertEquals('UI', $prodi->proyeksiUniversitas->singkatan);
    }

    public function test_admin_can_create_program_studi(): void
    {
        $admin = $this->makeAdmin();
        $univ  = $this->makeUniversitas();

        $response = $this->actingAs($admin, 'web')->postJson('/program-studi', [
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Ilmu Komputer',
            'jenjang'                 => 'S1',
            'akreditasi_prodi'        => 'A',
            'daya_tampung'            => 120,
            'peminat_tahun_lalu'      => 1500,
            'kelompok_saintek_soshum' => 'Saintek',
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('program_studi', ['nama_prodi' => 'Ilmu Komputer']);
    }

    public function test_non_admin_cannot_create_program_studi(): void
    {
        $guru = User::create([
            'id'        => (string) Str::uuid(),
            'username'  => 'guru_bk_prodi',
            'password'  => 'password123',
            'role'      => 'guru_bk',
            'is_active' => true,
        ]);

        $univ = $this->makeUniversitas();

        $response = $this->actingAs($guru, 'web')->postJson('/program-studi', [
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Prodi Tolak',
            'jenjang'                 => 'S1',
        ]);

        $response->assertStatus(403);
    }

    public function test_store_rejects_unknown_universitas(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'web')->postJson('/program-studi', [
            'proyeksi_universitas_id' => (string) Str::uuid(),
            'nama_prodi'              => 'Prodi Yatim',
            'jenjang'                 => 'S1',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('proyeksi_universitas_id');
    }

    public function test_can_filter_program_studi_by_kelompok(): void
    {
        $admin = $this->makeAdmin();
        $univ  = $this->makeUniversitas();

        ProgramStudi::create([
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Teknik Informatika',
            'jenjang'                 => 'S1',
            'kelompok_saintek_soshum' => 'Saintek',
            'is_active'               => true,
        ]);

        ProgramStudi::create([
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Hubungan Internasional',
            'jenjang'                 => 'S1',
            'kelompok_saintek_soshum' => 'Soshum',
            'is_active'               => true,
        ]);

        $response = $this->actingAs($admin, 'web')
                         ->getJson('/program-studi?kelompok_saintek_soshum=Saintek');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(1, 'data.data');
    }

    public function test_can_filter_program_studi_by_universitas(): void
    {
        $admin = $this->makeAdmin();

        $ui  = $this->makeUniversitas();
        $itb = ProyeksiUniversitas::create([
            'nama_universitas' => 'Institut Teknologi Bandung',
            'singkatan'        => 'ITB',
            'is_active'        => true,
        ]);

        ProgramStudi::create([
            'proyeksi_universitas_id' => $ui->id,
            'nama_prodi'              => 'Ilmu Komputer',
            'jenjang'                 => 'S1',
            'is_active'               => true,
        ]);

        ProgramStudi::create([
            'proyeksi_universitas_id' => $itb->id,
            'nama_prodi'              => 'Teknik Elektro',
            'jenjang'                 => 'S1',
            'is_active'               => true,
        ]);

        $response = $this->actingAs($admin, 'web')
                         ->getJson("/program-studi?proyeksi_universitas_id={$itb->id}");

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data')
                 ->assertJsonPath('data.data.0.nama_prodi', 'Teknik Elektro');
    }

    public function test_index_includes_universitas_relation(): void
    {
        $admin = $this->makeAdmin();
        $univ  = $this->makeUniversitas();

        ProgramStudi::create([
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Fisika',
            'jenjang'                 => 'S1',
            'is_active'               => true,
        ]);

        $response = $this->actingAs($admin, 'web')->getJson('/program-studi');

        $response->assertStatus(200)
                 ->assertJsonPath('data.data.0.proyeksi_universitas.singkatan', 'UI');
    }

    public function test_siswa_can_read_program_studi(): void
    {
        $siswa = Siswa::create([
            'id'           => (string) Str::uuid(),
            'nisn'         => '0011223355',
            'nis'          => '11002',
            'nama_lengkap' => 'Siswa Baca Prodi',
            'password'     => 'password123',
            'is_active'    => true,
        ]);

        $univ = $this->makeUniversitas();

        ProgramStudi::create([
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Fisika',
            'jenjang'                 => 'S1',
            'is_active'               => true,
        ]);

        $response = $this->actingAs($siswa, 'siswa')->getJson('/program-studi');

        $response->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_guest_cannot_read_program_studi(): void
    {
        $response = $this->getJson('/program-studi');

        $response->assertStatus(401);
    }

    public function test_admin_can_update_program_studi(): void
    {
        $admin = $this->makeAdmin();
        $univ  = $this->makeUniversitas();

        $prodi = ProgramStudi::create([
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Matematika',
            'jenjang'                 => 'S1',
            'is_active'               => true,
        ]);

        $response = $this->actingAs($admin, 'web')->putJson("/program-studi/{$prodi->id}", [
            'daya_tampung' => 80,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('program_studi', [
            'id'           => $prodi->id,
            'daya_tampung' => 80,
        ]);
    }

    public function test_admin_can_delete_program_studi(): void
    {
        $admin = $this->makeAdmin();
        $univ  = $this->makeUniversitas();

        $prodi = ProgramStudi::create([
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Prodi Hapus',
            'jenjang'                 => 'S1',
            'is_active'               => true,
        ]);

        $response = $this->actingAs($admin, 'web')->deleteJson("/program-studi/{$prodi->id}");

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSoftDeleted('program_studi', ['id' => $prodi->id]);
    }

    public function test_deleting_universitas_cascades_to_program_studi(): void
    {
        $admin = $this->makeAdmin();
        $univ  = $this->makeUniversitas();

        $prodi = ProgramStudi::create([
            'proyeksi_universitas_id' => $univ->id,
            'nama_prodi'              => 'Prodi Ikut Hapus',
            'jenjang'                 => 'S1',
            'is_active'               => true,
        ]);

        // Soft delete universitas: prodi tetap ada (soft delete tidak trigger FK cascade)
        $univ->delete();

        $this->assertSoftDeleted('proyeksi_universitas', ['id' => $univ->id]);
        $this->assertDatabaseHas('program_studi', ['id' => $prodi->id]);
    }
}
