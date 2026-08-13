<?php

namespace Tests\Feature;

use App\Models\DetailPendaftaranPilihan;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use App\Models\KelasAsal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PendaftaranPilihanControllerTest extends TestCase
{
    use RefreshDatabase;

    private Siswa $siswa;
    private PaketMenuPilihan $paket1;
    private PaketMenuPilihan $paket2;
    private PaketMenuPilihan $paket3;
    private PeriodePendaftaran $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XII MIPA 1',
            'tingkat' => 'X',
        ]);

        $this->siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '1234567890',
            'nis' => '12345',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_asal_id' => $kelas->id,
            'jenis_kelamin' => 'L',
            'angkatan' => '2024',
            'is_active' => true,
        ]);

        $this->periode = PeriodePendaftaran::create([
            'id' => (string) Str::uuid(),
            'nama_periode' => 'Periode Test',
            'tahun_ajaran' => '2024/2025',
            'gelombang' => 'Utama',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDay(),
            'tanggal_tutup' => now()->addDays(5),
            'status_pengumuman' => 'AKTIF',
            'is_active' => true,
        ]);

        $this->paket1 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket A',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $this->paket2 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket B',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $this->paket3 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket C',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);
    }

    public function test_siswa_can_submit_pilihan(): void
    {
        $payload = [
            'pilihan' => [
                $this->paket1->id,
                $this->paket2->id,
                $this->paket3->id,
            ]
        ];

        $response = $this->actingAs($this->siswa, 'siswa')
            ->postJson('/siswa/pendaftaran-pilihan', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil menyimpan 3 paket menu pilihan prioritas Anda.',
            ]);

        $this->assertDatabaseHas('pendaftaran_pilihan', [
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
        ]);

        $this->assertDatabaseHas('detail_pendaftaran_pilihan', [
            'paket_menu_pilihan_id' => $this->paket1->id,
            'urutan_pilihan' => 1,
        ]);

        $this->assertDatabaseHas('detail_pendaftaran_pilihan', [
            'paket_menu_pilihan_id' => $this->paket3->id,
            'urutan_pilihan' => 3,
        ]);
    }

    public function test_siswa_cannot_submit_duplicate_packages(): void
    {
        $payload = [
            'pilihan' => [
                $this->paket1->id,
                $this->paket1->id, // DUPLICATE
                $this->paket3->id,
            ]
        ];

        $response = $this->actingAs($this->siswa, 'siswa')
            ->postJson('/siswa/pendaftaran-pilihan', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Pilihan paket menu prioritas tidak boleh ada yang duplikat / sama.',
            ]);

        $this->assertDatabaseCount('pendaftaran_pilihan', 0);
    }

    public function test_siswa_cannot_resubmit_choices(): void
    {
        // Insert existing submission
        $pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
        ]);

        $payload = [
            'pilihan' => [
                $this->paket1->id,
                $this->paket2->id,
                $this->paket3->id,
            ]
        ];

        $response = $this->actingAs($this->siswa, 'siswa')
            ->postJson('/siswa/pendaftaran-pilihan', $payload);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Anda sudah pernah mengirimkan pilihan paket pada periode ini.',
            ]);
    }

    public function test_siswa_cannot_submit_outside_registration_period(): void
    {
        // Ubah periode jadi expired
        $this->periode->update([
            'tanggal_buka' => now()->subDays(10),
            'tanggal_tutup' => now()->subDays(5),
        ]);

        $payload = [
            'pilihan' => [
                $this->paket1->id,
                $this->paket2->id,
                $this->paket3->id,
            ]
        ];

        $response = $this->actingAs($this->siswa, 'siswa')
            ->postJson('/siswa/pendaftaran-pilihan', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Pendaftaran ditolak. Tidak ada periode pendaftaran aktif yang sedang berjalan.',
            ]);
    }

    public function test_siswa_can_view_their_pendaftaran_status(): void
    {
        $pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
        ]);

        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pendaftaran->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'urutan_pilihan' => 1,
        ]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->getJson('/siswa/pendaftaran-pilihan');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $pendaftaran->id,
                    'siswa_id' => $this->siswa->id,
                ]
            ]);
        
        $this->assertEquals($this->paket1->id, $response->json('data.detail_pendaftaran.0.paket_menu_pilihan_id'));
    }
}
