<?php

namespace Tests\Feature;

use App\Models\KelasAsal;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiswaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected KelasAsal $kelas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_siswa',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X A',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 15; $i++) {
            Siswa::create([
                'nisn' => sprintf('12345678%02d', $i),
                'nis' => sprintf('%04d', $i),
                'nama_lengkap' => "Siswa Tes Ke-{$i}",
                'kelas_asal_id' => $this->kelas->id,
                'kelas_asal' => 'X A',
                'is_active' => true,
            ]);
        }
    }

    /** Paginasi server-side: per_page & meta paginator benar */
    public function test_admin_can_get_paginated_siswa_json(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/siswa?per_page=5&page=1');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(5, 'data.data');

        $this->assertEquals(15, $response->json('data.total'));
        $this->assertEquals(5, $response->json('data.per_page'));
        $this->assertEquals(3, $response->json('data.last_page'));
    }

    /** Pencarian berdasarkan substring nama_lengkap */
    public function test_admin_can_search_siswa_by_nama(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/siswa?search=Siswa Tes Ke-1');

        // Cocok dengan: Ke-1, Ke-10, Ke-11, Ke-12, Ke-13, Ke-14, Ke-15 = 7 data
        $response->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(7, $response->json('data.total'));
    }

    /** Pencarian berdasarkan nisn */
    public function test_admin_can_search_siswa_by_nisn(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/siswa?search=1234567801');

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('1234567801', $response->json('data.data.0.nisn'));
    }

    /** Request browser reguler merender view siswa.index */
    public function test_admin_can_view_siswa_index_blade(): void
    {
        $response = $this->actingAs($this->admin, 'web')->get('/siswa');

        $response->assertOk();
        $response->assertViewIs('siswa.index');
        $response->assertViewHas('siswa');
        $response->assertViewHas('kelasAsal');
    }

    /** Guard auth:web menolak user yang belum login (JSON => 401) */
    public function test_unauthenticated_user_cannot_access_siswa_index(): void
    {
        $this->getJson('/siswa')->assertStatus(401);
    }

    /** Request browser reguler merender view siswa.show */
    public function test_admin_can_view_siswa_show_blade(): void
    {
        $siswa = Siswa::create([
            'nisn' => '9999999999',
            'nis' => '9999',
            'nama_lengkap' => 'Siswa Detail',
            'kelas_asal_id' => $this->kelas->id,
            'kelas_asal' => 'X A',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get('/siswa/' . $siswa->id);

        $response->assertOk();
        $response->assertViewIs('siswa.show');
        $response->assertViewHas('siswa');
    }
}
