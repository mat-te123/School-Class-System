<?php

namespace Tests\Unit;

use App\Models\KelasAsal;
use App\Models\PaketMenuPilihan;
use App\Models\PengajuanPertukaran;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PengajuanPertukaranModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_pengajuan_pertukaran_record_with_relations(): void
    {
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
            'tanggal_buka' => now()->subDays(5),
            'tanggal_tutup' => now()->subDays(1),
            'status_pengumuman' => 'AKTIF',
            'is_active' => true,
        ]);

        $paket1 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket A',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 1,
            'is_active' => true,
        ]);

        $paket2 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket B',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 1,
            'is_active' => true,
        ]);

        $pengajuan = PengajuanPertukaran::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $siswa->id,
            'periode_pendaftaran_id' => $periode->id,
            'paket_asal_id' => $paket1->id,
            'paket_tujuan_id' => $paket2->id,
            'alasan' => 'Alasan pertukaran jarak tempat tinggal',
            'status' => 'menunggu',
        ]);

        $this->assertDatabaseHas('pengajuan_pertukaran', [
            'id' => $pengajuan->id,
            'status' => 'menunggu',
        ]);
        $this->assertEquals($siswa->id, $pengajuan->siswa->id);
        $this->assertEquals($paket1->id, $pengajuan->paketAsal->id);
        $this->assertEquals($paket2->id, $pengajuan->paketTujuan->id);
        $this->assertEquals($periode->id, $pengajuan->periodePendaftaran->id);
    }
}
