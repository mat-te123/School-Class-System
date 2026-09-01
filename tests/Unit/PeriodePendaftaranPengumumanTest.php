<?php

namespace Tests\Unit;

use App\Models\PeriodePendaftaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodePendaftaranPengumumanTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_pengumuman_dibuka_returns_true_when_status_aktif(): void
    {
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Periode 2026',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDay(),
            'status_pengumuman' => 'AKTIF',
            'tanggal_pengumuman' => null,
            'is_active' => true,
        ]);

        $this->assertTrue($periode->isPengumumanDibuka());
    }

    public function test_is_pengumuman_dibuka_returns_true_when_tanggal_pengumuman_in_past(): void
    {
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Periode 2026',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDays(2),
            'status_pengumuman' => 'NON-AKTIF',
            'tanggal_pengumuman' => now()->subHour(),
            'is_active' => true,
        ]);

        $this->assertTrue($periode->isPengumumanDibuka());
    }

    public function test_is_pengumuman_dibuka_returns_false_when_future_date_and_non_aktif(): void
    {
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Periode 2026',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDays(2),
            'status_pengumuman' => 'NON-AKTIF',
            'tanggal_pengumuman' => now()->addDay(),
            'is_active' => true,
        ]);

        $this->assertFalse($periode->isPengumumanDibuka());
    }

    public function test_scope_pengumuman_dibuka_filters_correctly(): void
    {
        $periodeAktif = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Aktif Manual',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDays(2),
            'status_pengumuman' => 'AKTIF',
            'is_active' => true,
        ]);

        $periodeAuto = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Auto Buka',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDays(2),
            'status_pengumuman' => 'NON-AKTIF',
            'tanggal_pengumuman' => now()->subMinutes(10),
            'is_active' => false,
        ]);

        $periodeTutup = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Belum Buka',
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDays(2),
            'status_pengumuman' => 'NON-AKTIF',
            'tanggal_pengumuman' => now()->addHour(),
            'is_active' => false,
        ]);

        $openedPeriods = PeriodePendaftaran::pengumumanDibuka()->pluck('id')->all();

        $this->assertContains($periodeAktif->id, $openedPeriods);
        $this->assertContains($periodeAuto->id, $openedPeriods);
        $this->assertNotContains($periodeTutup->id, $openedPeriods);
    }
}
