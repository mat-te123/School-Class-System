<?php

namespace Tests\Feature;

use App\Models\PeriodePendaftaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncStatusPengumumanCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_status_pengumuman_updates_eligible_periods(): void
    {
        $periodePast = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Waktunya Pengumuman',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDays(2),
            'status_pengumuman' => 'NON-AKTIF',
            'tanggal_pengumuman' => now()->subMinute(),
            'is_active' => true,
        ]);

        $periodeFuture = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Belum Waktunya',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDays(2),
            'status_pengumuman' => 'NON-AKTIF',
            'tanggal_pengumuman' => now()->addDay(),
            'is_active' => false,
        ]);

        $this->artisan('periode:sync-pengumuman')
            ->assertSuccessful();

        $this->assertEquals('AKTIF', $periodePast->fresh()->status_pengumuman);
        $this->assertEquals('NON-AKTIF', $periodeFuture->fresh()->status_pengumuman);
    }
}
