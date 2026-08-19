<?php

namespace Tests\Feature;

use App\Models\DetailPendaftaranPilihan;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use App\Models\KelasAsal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_siswa_can_upload_dokumen_wali(): void
    {
        $pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'menunggu',
        ]);

        Storage::fake('public');
        $file = UploadedFile::fake()->create('surat_wali.pdf', 200, 'application/pdf');

        // PENTING: putJson() tidak bisa upload file binary. Gunakan ->call() dengan FILES array:
        $response = $this->actingAs($this->siswa, 'siswa')
            ->call('PUT', '/siswa/pendaftaran-pilihan/dokumen', [], [], ['dokumen_wali' => $file], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotNull($data['data']['dokumen_wali_path']);

        $this->assertNotNull(
            PendaftaranPilihan::find($pendaftaran->id)->dokumen_wali_path
        );
    }

    public function test_siswa_can_cancel_pending_submission(): void
    {
        // Set periode pertukaran aktif
        $this->periode->update([
            'tanggal_mulai_pertukaran' => now()->subHour(),
            'tanggal_selesai_pertukaran' => now()->addHour(),
        ]);

        $pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'menunggu',
        ]);

        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pendaftaran->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'urutan_pilihan' => 1,
        ]);
        $this->paket1->update(['kuota_terisi' => 1]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->deleteJson('/siswa/pendaftaran-pilihan');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseCount('pendaftaran_pilihan', 0);
        $this->assertEquals(0, $this->paket1->fresh()->kuota_terisi);
    }

    public function test_siswa_cannot_cancel_approved_submission(): void
    {
        $this->periode->update([
            'tanggal_mulai_pertukaran' => now()->subHour(),
            'tanggal_selesai_pertukaran' => now()->addHour(),
        ]);

        PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->deleteJson('/siswa/pendaftaran-pilihan');

        $response->assertStatus(422);
    }

    /** Request browser non-JSON redirect setelah storeSiswa */
    public function test_browser_store_siswa_redirects_back_with_success_flash(): void
    {
        $response = $this->actingAs($this->siswa, 'siswa')
            ->from('/siswa/pendaftaran-pilihan')
            ->post('/siswa/pendaftaran-pilihan', [
                'pilihan' => [
                    $this->paket1->id,
                    $this->paket2->id,
                    $this->paket3->id,
                ],
            ]);

        $response->assertRedirect('/siswa/pendaftaran-pilihan');
        $response->assertSessionHas('success', 'Berhasil menyimpan 3 paket menu pilihan prioritas Anda.');
        $this->assertDatabaseHas('pendaftaran_pilihan', ['siswa_id' => $this->siswa->id]);
    }

    /** Request browser non-JSON redirect setelah updateSiswa */
    public function test_browser_update_siswa_redirects_back_with_success_flash(): void
    {
        $pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'menunggu',
        ]);

        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pendaftaran->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'urutan_pilihan' => 1,
        ]);
        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pendaftaran->id,
            'paket_menu_pilihan_id' => $this->paket2->id,
            'urutan_pilihan' => 2,
        ]);
        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pendaftaran->id,
            'paket_menu_pilihan_id' => $this->paket3->id,
            'urutan_pilihan' => 3,
        ]);
        $this->paket1->update(['kuota_terisi' => 1]);
        $this->paket2->update(['kuota_terisi' => 1]);
        $this->paket3->update(['kuota_terisi' => 1]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->from('/siswa/pendaftaran-pilihan')
            ->put('/siswa/pendaftaran-pilihan', [
                'pilihan' => [
                    $this->paket1->id,
                    $this->paket2->id,
                    $this->paket3->id,
                ],
            ]);

        $response->assertRedirect('/siswa/pendaftaran-pilihan');
        $response->assertSessionHas('success', 'Pilihan paket prioritas Anda berhasil diperbarui.');
    }

    /** Request browser non-JSON redirect setelah uploadDokumenSiswa */
    public function test_browser_upload_dokumen_siswa_redirects_back_with_success_flash(): void
    {
        Storage::fake('public');

        $pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'menunggu',
        ]);

        $file = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->siswa, 'siswa')
            ->from('/siswa/pendaftaran-pilihan')
            ->put('/siswa/pendaftaran-pilihan/dokumen', [
                'dokumen_wali' => $file,
            ]);

        $response->assertRedirect('/siswa/pendaftaran-pilihan');
        $response->assertSessionHas('success', 'Dokumen wali berhasil diunggah.');
        $this->assertNotNull($pendaftaran->fresh()->dokumen_wali_path);
    }

    /** Request browser non-JSON redirect setelah cancelSiswa */
    public function test_browser_cancel_siswa_redirects_back_with_success_flash(): void
    {
        $this->periode->update([
            'tanggal_mulai_pertukaran' => now()->subHour(),
            'tanggal_selesai_pertukaran' => now()->addHour(),
        ]);

        $pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'menunggu',
        ]);

        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pendaftaran->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'urutan_pilihan' => 1,
        ]);
        $this->paket1->update(['kuota_terisi' => 1]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->from('/siswa/pendaftaran-pilihan')
            ->delete('/siswa/pendaftaran-pilihan');

        $response->assertRedirect('/siswa/pendaftaran-pilihan');
        $response->assertSessionHas('success', 'Pengajuan berhasil dibatalkan.');
        $this->assertDatabaseCount('pendaftaran_pilihan', 0);
    }
}
