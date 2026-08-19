<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test User (Admin / Guru BK) berhasil login dan data tersimpan di session.
     */
    public function test_user_can_login_and_store_session(): void
    {
        $role = Role::create([
            'id' => (string) Str::uuid(),
            'name' => 'admin',
            'label' => 'Administrator',
            'description' => 'Akses penuh',
        ]);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_test',
            'password' => 'password123',
            'role_id' => $role->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->postJson('/login', [
            'username' => 'admin_test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil login sebagai Administrator.',
                'data' => [
                    'user' => [
                        'username' => 'admin_test',
                        'role' => 'admin',
                    ],
                ],
            ]);

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertEquals($user->id, session('user_id'));
        $this->assertEquals('admin_test', session('username'));
        $this->assertEquals('admin', session('user_role'));
    }

    /**
     * Test User gagal login jika password salah.
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_test',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->postJson('/login', [
            'username' => 'admin_test',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Password salah.',
            ]);

        $this->assertGuest('web');
    }

    /**
     * Test User dapat mengambil data profil dari session / me endpoint.
     */
    public function test_authenticated_user_can_get_me_profile(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_test',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'web')->getJson('/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'admin_test',
                    ],
                ],
            ]);
    }

    /**
     * Test User berhasil logout dan hapus session.
     */
    public function test_user_can_logout(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_test',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'web')->postJson('/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil logout dari sistem.',
            ]);

        $this->assertGuest('web');
    }

    /** Request browser reguler merender view auth.me */
    public function test_user_can_view_me_blade(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_blade',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'web')->get('/me');

        $response->assertOk();
        $response->assertViewIs('auth.me');
        $response->assertViewHas('user');
    }
}
