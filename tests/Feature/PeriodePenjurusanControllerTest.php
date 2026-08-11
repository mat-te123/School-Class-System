<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodePenjurusanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/periode-penjurusan', [
            'nama_periode' => 'Penjurusan 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => '2026-08-11 08:00:00',
            'tanggal_tutup' => '2026-08-20 23:59:59',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nama_periode', 'Penjurusan 2026/2027');

        $this->assertDatabaseHas('periode_pendaftaran', [
            'nama_periode' => 'Penjurusan 2026/2027',
        ]);
    }

    public function test_admin_cannot_create_a_period_with_an_invalid_schedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/periode-penjurusan', [
            'nama_periode' => 'Penjurusan 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => '2026-08-20 08:00:00',
            'tanggal_tutup' => '2026-08-20 08:00:00',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('tanggal_tutup');
    }

    public function test_authenticated_user_can_view_periods_and_period_detail(): void
    {
        $user = User::factory()->create();
        $periode = \App\Models\PeriodePendaftaran::create([
            'nama_periode' => 'Penjurusan 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => '2026-08-11 08:00:00',
            'tanggal_tutup' => '2026-08-20 23:59:59',
        ]);

        $this->actingAs($user)->getJson('/periode-penjurusan')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($user)->getJson('/periode-penjurusan/' . $periode->id)
            ->assertOk()
            ->assertJsonPath('data.id', $periode->id);
    }

    public function test_admin_can_update_period_information_and_minat_schedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $periode = \App\Models\PeriodePendaftaran::create([
            'nama_periode' => 'Penjurusan Lama',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => '2026-08-11 08:00:00',
            'tanggal_tutup' => '2026-08-20 23:59:59',
        ]);

        $response = $this->actingAs($admin)->putJson('/periode-penjurusan/' . $periode->id, [
            'nama_periode' => 'Penjurusan Baru',
            'tanggal_buka' => '2026-08-12 08:00:00',
            'tanggal_tutup' => '2026-08-25 23:59:59',
        ]);

        $response->assertOk()->assertJsonPath('data.nama_periode', 'Penjurusan Baru');
        $this->assertDatabaseHas('periode_pendaftaran', [
            'id' => $periode->id,
            'nama_periode' => 'Penjurusan Baru',
            'tanggal_buka' => '2026-08-12 08:00:00',
            'tanggal_tutup' => '2026-08-25 23:59:59',
        ]);
    }

    public function test_admin_cannot_update_period_with_invalid_minat_schedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $periode = \App\Models\PeriodePendaftaran::create([
            'nama_periode' => 'Penjurusan 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => '2026-08-11 08:00:00',
            'tanggal_tutup' => '2026-08-20 23:59:59',
        ]);

        $this->actingAs($admin)->putJson('/periode-penjurusan/' . $periode->id, [
            'tanggal_tutup' => '2026-08-10 08:00:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('tanggal_tutup');
    }
}