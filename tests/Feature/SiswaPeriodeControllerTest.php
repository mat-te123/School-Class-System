<?php

namespace Tests\Feature;

use App\Models\KelasAsal;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaPeriodeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Siswa $siswa;

    protected PeriodePendaftaran $periodeAktif;

    protected PeriodePendaftaran $periodeTutup;

    protected PeriodePendaftaran $periodeBelumBuka;

    protected function setUp(): void
    {
        parent::setUp();

        $kelas = KelasAsal::create([
            'nama_kelas' => 'X-A',
            'tingkat' => '10',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $this->siswa = Siswa::create([
            'nisn' => '1234567890',
            'nis' => '1001',
            'nama_lengkap' => 'Budi Siswa Test',
            'kelas_asal_id' => $kelas->id,
            'is_active' => true,
            'password' => 'password123',
        ]);

        // Periode 1: Aktif & sedang buka saat ini (now di antara buka dan tutup)
        $this->periodeAktif = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Pendaftaran Aktif 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'gelombang' => '1',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDays(2),
            'tanggal_tutup' => now()->addDays(5),
            'status_pengumuman' => 'NON-AKTIF',
            'is_active' => true,
        ]);

        // Periode 2: Aktif tapi tanggal tutup sudah lewat (sudah ditutup)
        $this->periodeTutup = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Lalu (Tutup)',
            'tahun_ajaran' => '2025/2026',
            'gelombang' => '1',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDays(20),
            'tanggal_tutup' => now()->subDays(10),
            'status_pengumuman' => 'AKTIF',
            'is_active' => true,
        ]);

        // Periode 3: Aktif tapi tanggal buka masih di masa depan (belum dibuka)
        $this->periodeBelumBuka = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Depan (Belum Buka)',
            'tahun_ajaran' => '2027/2028',
            'gelombang' => '1',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->addDays(5),
            'tanggal_tutup' => now()->addDays(15),
            'status_pengumuman' => 'NON-AKTIF',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_siswa_periode(): void
    {
        $response = $this->getJson('/siswa/periode');
        $response->assertUnauthorized();
    }

    public function test_siswa_can_view_only_active_and_open_periods(): void
    {
        $response = $this->actingAs($this->siswa, 'siswa')
            ->getJson('/siswa/periode');

        $response->assertOk()
            ->assertJsonPath('success', true)
            // Hanya 1 periode yang lolos (periodeAktif), periodeTutup & periodeBelumBuka difilter keluar
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->periodeAktif->id);
    }

    public function test_siswa_can_view_period_detail(): void
    {
        $response = $this->actingAs($this->siswa, 'siswa')
            ->getJson("/siswa/periode/{$this->periodeAktif->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->periodeAktif->id)
            ->assertJsonPath('data.is_buka', true);
    }
}
