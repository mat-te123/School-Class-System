<?php

namespace Tests\Feature;

use App\Models\PeriodePendaftaran;
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
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data.data');

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

    public function test_only_one_active_periode_allowed_at_db_level(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Partial unique index hanya diuji pada PostgreSQL.');
        }

        User::factory()->create(['role' => 'admin']);

        // Buat periode pertama (aktif)
        \App\Models\PeriodePendaftaran::create([
            'nama_periode' => 'Periode A',
            'tahun_ajaran' => '2024/2025',
            'tanggal_buka' => '2026-08-01 08:00:00',
            'tanggal_tutup' => '2026-08-20 23:59:59',
            'is_active' => true,
        ]);

        // Coba buat periode kedua juga aktif — harus gagal dengan integrity error
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\PeriodePendaftaran::create([
            'nama_periode' => 'Periode B',
            'tahun_ajaran' => '2025/2026',
            'tanggal_buka' => '2026-09-01 08:00:00',
            'tanggal_tutup' => '2026-09-20 23:59:59',
            'is_active' => true,
        ]);
    }

    public function test_creating_active_periode_auto_deactivates_existing_active(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $periodeA = \App\Models\PeriodePendaftaran::create([
            'nama_periode' => 'Periode A',
            'tahun_ajaran' => '2024/2025',
            'tanggal_buka' => '2026-07-01 08:00:00',
            'tanggal_tutup' => '2026-07-31 23:59:59',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson('/periode-penjurusan', [
            'nama_periode'  => 'Periode B',
            'tahun_ajaran'  => '2025/2026',
            'tanggal_buka'  => '2026-08-01 08:00:00',
            'tanggal_tutup' => '2026-08-31 23:59:59',
            'is_active'     => true,
        ]);

        $response->assertCreated()->assertJson(['success' => true]);

        $this->assertFalse($periodeA->fresh()->is_active);
        $this->assertTrue(
            \App\Models\PeriodePendaftaran::find($response->json('data.id'))->is_active
        );
    }

    public function test_updating_periode_to_active_auto_deactivates_existing_active(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $periodeA = \App\Models\PeriodePendaftaran::create([
            'nama_periode' => 'Periode A',
            'tahun_ajaran' => '2024/2025',
            'tanggal_buka' => '2026-07-01 08:00:00',
            'tanggal_tutup' => '2026-07-31 23:59:59',
            'is_active' => true,
        ]);

        $periodeB = \App\Models\PeriodePendaftaran::create([
            'nama_periode' => 'Periode B',
            'tahun_ajaran' => '2025/2026',
            'tanggal_buka' => '2026-08-01 08:00:00',
            'tanggal_tutup' => '2026-08-31 23:59:59',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->putJson('/periode-penjurusan/' . $periodeB->id, [
            'is_active' => true,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertFalse($periodeA->fresh()->is_active);
        $this->assertTrue($periodeB->fresh()->is_active);
    }

    public function test_setting_current_active_periode_to_active_again_is_idempotent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $periodeA = \App\Models\PeriodePendaftaran::create([
            'nama_periode' => 'Periode A',
            'tahun_ajaran' => '2024/2025',
            'tanggal_buka' => '2026-07-01 08:00:00',
            'tanggal_tutup' => '2026-07-31 23:59:59',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->putJson('/periode-penjurusan/' . $periodeA->id, [
            'is_active' => true,
        ]);

        $response->assertOk();
        $this->assertTrue($periodeA->fresh()->is_active);
    }

    /** Paginasi server-side: per_page & meta paginator benar */
    public function test_admin_can_get_paginated_periode_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 1; $i <= 8; $i++) {
            PeriodePendaftaran::create([
                'nama_periode' => "Periode Tes Ke-{$i}",
                'tahun_ajaran' => '2026/2027',
                'tanggal_buka' => '2026-08-01 08:00:00',
                'tanggal_tutup' => '2026-08-31 23:59:59',
            ]);
        }

        $response = $this->actingAs($admin)->getJson('/periode-penjurusan?per_page=3&page=1');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data.data');

        $this->assertEquals(8, $response->json('data.total'));
        $this->assertEquals(3, $response->json('data.per_page'));
        $this->assertEquals(3, $response->json('data.last_page'));
    }

    /** Pencarian berdasarkan substring nama_periode */
    public function test_admin_can_search_periode_by_nama(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        PeriodePendaftaran::create([
            'nama_periode' => 'Penjurusan 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => '2026-08-01 08:00:00',
            'tanggal_tutup' => '2026-08-31 23:59:59',
        ]);
        PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Khusus',
            'tahun_ajaran' => '2025/2026',
            'tanggal_buka' => '2026-09-01 08:00:00',
            'tanggal_tutup' => '2026-09-30 23:59:59',
        ]);

        $response = $this->actingAs($admin)->getJson('/periode-penjurusan?search=Penjurusan');

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('Penjurusan 2026/2027', $response->json('data.data.0.nama_periode'));
    }

    /** Request browser reguler merender view periode-penjurusan.index */
    public function test_authenticated_user_can_view_periode_index_blade(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/periode-penjurusan');

        $response->assertOk();
        $response->assertViewIs('periode-penjurusan.index');
        $response->assertViewHas('periode');
    }

    /** Route auth.any: user yang belum login ditolak (JSON => 401) */
    public function test_unauthenticated_user_cannot_access_periode_index(): void
    {
        $this->getJson('/periode-penjurusan')->assertStatus(401);
    }
}