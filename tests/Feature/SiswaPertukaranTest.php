<?php

namespace Tests\Feature;

use App\Models\HasilSeleksi;
use App\Models\KelasAsal;
use App\Models\PaketMenuPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiswaPertukaranTest extends TestCase
{
    use RefreshDatabase;

    private function setUpData(): array
    {
        $now = now();

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XII MIPA 1',
        ]);

        $siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '8888888888',
            'nis' => '88888',
            'nama_lengkap' => 'Siswa Pertukaran',
            'kelas_asal_id' => $kelas->id,
            'jenis_kelamin' => 'P',
            'angkatan' => '2024',
            'is_active' => true,
        ]);

        $periode = PeriodePendaftaran::create([
            'id' => (string) Str::uuid(),
            'nama_periode' => 'Periode Pertukaran',
            'tahun_ajaran' => '2024/2025',
            'gelombang' => 'Utama',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDays(10),
            'tanggal_tutup' => now()->subDays(2),
            'tanggal_mulai_pertukaran' => now()->subHour(),
            'tanggal_selesai_pertukaran' => now()->addDays(5),
            'status_pengumuman' => 'AKTIF',
            'is_active' => true,
        ]);

        $paketAsal = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket A',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 1,
            'is_active' => true,
        ]);

        $paketTujuan = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket B',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 1,
            'is_active' => true,
        ]);

        HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $siswa->id,
            'paket_menu_pilihan_id' => $paketAsal->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 5,
            'skor_penempatan' => 85.50,
            'rata_6_mapel' => 88.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => $now,
        ]);

        return [
            'siswa' => $siswa,
            'periode' => $periode,
            'paketAsal' => $paketAsal,
            'paketTujuan' => $paketTujuan,
        ];
    }

    public function test_siswa_can_submit_and_view_pertukaran(): void
    {
        $data = $this->setUpData();
        $siswa = $data['siswa'];
        $paketTujuan = $data['paketTujuan'];

        // Submit pengajuan pertukaran
        $response = $this->actingAs($siswa, 'siswa')->postJson('/siswa/pertukaran', [
            'paket_tujuan_id' => $paketTujuan->id,
            'alasan' => 'Ingin pindah ke jurusan sesuai minat karir',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        // View status pertukaran
        $indexResp = $this->actingAs($siswa, 'siswa')->getJson('/siswa/pertukaran');
        $indexResp->assertStatus(200)
            ->assertJsonPath('data.status', 'menunggu')
            ->assertJsonPath('data.alasan', 'Ingin pindah ke jurusan sesuai minat karir');

        // Cancel pengajuan pertukaran
        $pengajuanId = $indexResp->json('data.id');
        $cancelResp = $this->actingAs($siswa, 'siswa')->deleteJson("/siswa/pertukaran/{$pengajuanId}");
        $cancelResp->assertStatus(200);

        $this->assertDatabaseMissing('pengajuan_pertukaran', ['id' => $pengajuanId]);
    }

    public function test_siswa_cannot_submit_pertukaran_outside_period(): void
    {
        $data = $this->setUpData();
        $siswa = $data['siswa'];
        $paketTujuan = $data['paketTujuan'];

        // Tutup periode pertukaran
        $data['periode']->update([
            'tanggal_mulai_pertukaran' => now()->subDays(10),
            'tanggal_selesai_pertukaran' => now()->subDays(5),
        ]);

        $response = $this->actingAs($siswa, 'siswa')->postJson('/siswa/pertukaran', [
            'paket_tujuan_id' => $paketTujuan->id,
            'alasan' => 'Coba diluar periode',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
