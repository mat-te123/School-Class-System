<?php

namespace Tests\Feature;

use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiswaControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Siswa berhasil login via API JSON dengan NISN dan password yang valid.
     */
    public function test_siswa_can_login_with_valid_nisn_and_password(): void
    {
        // 1. Arrange: Buat Siswa dummy dengan password
        $siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '1234567890',
            'nis' => '1001',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_asal' => 'X A',
            'jenis_kelamin' => 'L',
            'is_active' => true,
            'password' => 'password123',
        ]);

        // 2. Act: Kirim request login JSON dengan NISN dan Password
        $response = $this->postJson('/login/siswa', [
            'nisn' => '1234567890',
            'password' => 'password123',
        ]);

        // 3. Assert: Cek status HTTP & struktur JSON response
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil login sebagai siswa.',
                'data' => [
                    'siswa' => [
                        'nisn' => '1234567890',
                        'nama_lengkap' => 'Budi Santoso',
                    ],
                ],
            ]);

        $this->assertAuthenticatedAs($siswa, 'siswa');
    }

    /**
     * Test Siswa gagal login jika password salah.
     */
    public function test_siswa_cannot_login_with_invalid_password(): void
    {
        Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '1234567890',
            'nis' => '1001',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_asal' => 'X A',
            'jenis_kelamin' => 'L',
            'is_active' => true,
            'password' => 'password123',
        ]);

        $response = $this->postJson('/login/siswa', [
            'nisn' => '1234567890',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Password salah.',
            ]);

        $this->assertGuest('siswa');
    }

    /**
     * Test Siswa gagal login jika NISN tidak terdaftar.
     */
    public function test_siswa_cannot_login_with_invalid_nisn(): void
    {
        $response = $this->postJson('/login/siswa', [
            'nisn' => '9999999999',
            'password' => 'password123',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'NISN tidak terdaftar.',
            ]);

        $this->assertGuest('siswa');
    }

    /**
     * Test Siswa tidak bisa login jika akun dinonaktifkan (is_active = false).
     */
    public function test_siswa_cannot_login_if_account_is_inactive(): void
    {
        Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '1234567890',
            'nis' => '1001',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_asal' => 'X A',
            'jenis_kelamin' => 'L',
            'is_active' => false, // Non-aktif
            'password' => 'password123',
        ]);

        $response = $this->postJson('/login/siswa', [
            'nisn' => '1234567890',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun Anda sedang dinonaktifkan.',
            ]);

        $this->assertGuest('siswa');
    }

    /**
     * Test Siswa yang sudah login dapat mengambil data profilnya.
     */
    public function test_authenticated_siswa_can_get_profile(): void
    {
        $siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '1234567890',
            'nis' => '1001',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_asal' => 'X A',
            'jenis_kelamin' => 'L',
            'is_active' => true,
        ]);

        $response = $this->actingAs($siswa, 'siswa')->getJson('/siswa/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'siswa' => [
                        'id' => $siswa->id,
                        'nisn' => '1234567890',
                        'nama_lengkap' => 'Budi Santoso',
                    ],
                ],
            ]);
    }

    /**
     * Test Siswa berhasil logout.
     */
    public function test_siswa_can_logout(): void
    {
        $siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '1234567890',
            'nis' => '1001',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_asal' => 'X A',
            'jenis_kelamin' => 'L',
            'is_active' => true,
        ]);

        $response = $this->actingAs($siswa, 'siswa')->postJson('/logout/siswa');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil logout.',
            ]);

        $this->assertGuest('siswa');
    }
}
