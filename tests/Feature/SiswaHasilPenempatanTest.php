<?php

namespace Tests\Feature;

use App\Models\DetailPendaftaranPilihan;
use App\Models\HasilSeleksi;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use App\Models\KelasAsal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiswaHasilPenempatanTest extends TestCase
{
    use RefreshDatabase;

    private Siswa $siswa;
    private PeriodePendaftaran $periode;
    private PaketMenuPilihan $paket1;
    private PaketMenuPilihan $paket2;
    private PaketMenuPilihan $paket3;
    private PendaftaranPilihan $pendaftaran;

    protected function setUp(): void
    {
        parent::setUp();

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XII MIPA 1',
        ]);

        $this->siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '8888888888',
            'nis' => '88888',
            'nama_lengkap' => 'Siswa Hasil',
            'kelas_asal_id' => $kelas->id,
            'jenis_kelamin' => 'P',
            'angkatan' => '2024',
            'is_active' => true,
        ]);

        $this->periode = PeriodePendaftaran::create([
            'id' => (string) Str::uuid(),
            'nama_periode' => 'Periode Hasil',
            'tahun_ajaran' => '2024/2025',
            'gelombang' => 'Utama',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDays(1),
            'status_pengumuman' => 'AKTIF',
            'is_active' => true,
        ]);

        $this->paket1 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket A',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 1,
            'is_active' => true,
        ]);

        $this->paket2 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket B',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 1,
            'is_active' => true,
        ]);

        $this->paket3 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket C',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 1,
            'is_active' => true,
        ]);

        $this->pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'menunggu',
        ]);
    }

    public function test_siswa_can_update_pilihan_during_registration_period(): void
    {
        // Set periode buka kembali
        $this->periode->update([
            'tanggal_buka' => now()->subDay(),
            'tanggal_tutup' => now()->addDays(2),
        ]);

        $payload = [
            'pilihan' => [
                $this->paket2->id,
                $this->paket1->id,
                $this->paket3->id,
            ]
        ];

        $response = $this->actingAs($this->siswa, 'siswa')
            ->putJson('/siswa/pendaftaran-pilihan', $payload);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('detail_pendaftaran_pilihan', [
            'pendaftaran_pilihan_id' => $this->pendaftaran->id,
            'paket_menu_pilihan_id' => $this->paket2->id,
            'urutan_pilihan' => 1,
        ]);
    }

    public function test_siswa_can_view_hasil_penempatan_when_pengumuman_aktif(): void
    {
        HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 5,
            'skor_penempatan' => 88.50,
            'rata_6_mapel' => 85.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->getJson('/siswa/hasil-penempatan');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pilihan_ke_diterima' => 1,
                    'mekanisme' => 'Pilihan 1',
                    'skor_penempatan' => 88.50,
                ]
            ]);
    }

    public function test_siswa_cannot_view_hasil_penempatan_when_pengumuman_non_aktif(): void
    {
        $this->periode->update(['status_pengumuman' => 'NON-AKTIF']);

        HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 5,
            'skor_penempatan' => 88.50,
            'rata_6_mapel' => 85.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->getJson('/siswa/hasil-penempatan');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Hasil penempatan belum dipublikasikan.',
            ]);
    }

    public function test_siswa_can_view_hasil_penempatan_when_tanggal_pengumuman_has_passed(): void
    {
        $this->periode->update([
            'status_pengumuman' => 'NON-AKTIF',
            'tanggal_pengumuman' => now()->subMinute(),
        ]);

        HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 5,
            'skor_penempatan' => 88.50,
            'rata_6_mapel' => 85.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->getJson('/siswa/hasil-penempatan');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pilihan_ke_diterima' => 1,
                    'mekanisme' => 'Pilihan 1',
                ],
            ]);
    }

    public function test_siswa_cannot_view_hasil_penempatan_when_tanggal_pengumuman_is_in_future(): void
    {
        $this->periode->update([
            'status_pengumuman' => 'NON-AKTIF',
            'tanggal_pengumuman' => now()->addDay(),
        ]);

        HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 5,
            'skor_penempatan' => 88.50,
            'rata_6_mapel' => 85.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->getJson('/siswa/hasil-penempatan');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Hasil penempatan belum dipublikasikan.',
            ]);
    }
}
