<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGuardRedirectRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_to_login(): void
    {
        $this->get('admin/periode-penjurusan')->assertRedirect(route('login'));
    }

    public function test_siswa_redirected_to_siswa_periode(): void
    {
        $siswa = Siswa::factory()->create();
        $this->actingAs($siswa, 'siswa')->get('admin/periode-penjurusan')
            ->assertRedirect(route('siswa.periode.index'));
    }

    public function test_admin_gets_periode_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('admin/periode-penjurusan')->assertOk();
    }

    public function test_admin_siswa_guest_redirected_to_login(): void
    {
        $this->get('admin/siswa')->assertRedirect(route('login'));
    }

    public function test_admin_siswa_siswa_redirected_to_dashboard(): void
    {
        $siswa = Siswa::factory()->create();
        $this->actingAs($siswa, 'siswa')->get('admin/siswa')
            ->assertRedirect(route('siswa.dashboard'));
    }

    public function test_admin_siswa_admin_gets_siswa_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('admin/siswa')->assertOk();
    }
}
