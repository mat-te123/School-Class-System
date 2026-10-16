<?php

namespace Tests\Feature;

use App\Models\KelasAsal;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_siswa_can_access_dashboard(): void
    {
        $kelas = KelasAsal::create([
            'nama_kelas' => 'X-A',
            'tingkat' => '10',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $siswa = Siswa::create([
            'nisn' => '1234567890',
            'nis' => '1001',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_asal_id' => $kelas->id,
            'is_active' => true,
            'password' => 'password123',
        ]);

        $response = $this->actingAs($siswa, 'siswa')
            ->get('/siswa/dashboard');

        $response->assertOk()
            ->assertViewIs('siswa.dashboard.index')
            ->assertViewHas('siswa', fn (Siswa $viewSiswa): bool => $viewSiswa->is($siswa));
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get('/siswa/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_json_request_receives_unauthorized(): void
    {
        $response = $this->getJson('/siswa/dashboard');

        $response->assertUnauthorized();
    }
}
