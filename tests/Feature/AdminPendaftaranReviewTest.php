<?php

namespace Tests\Feature;

use App\Models\DetailPendaftaranPilihan;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use App\Models\KelasAsal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPendaftaranReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Siswa $siswa;
    private PeriodePendaftaran $periode;
    private PendaftaranPilihan $pendaftaran;
    private PaketMenuPilihan $paket1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XII MIPA 1',
            'tingkat' => 'X',
        ]);

        $this->siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '9999999999',
            'nis' => '99999',
            'nama_lengkap' => 'Siswa Review',
            'kelas_asal_id' => $kelas->id,
            'jenis_kelamin' => 'L',
            'angkatan' => '2024',
            'is_active' => true,
        ]);

        $this->periode = PeriodePendaftaran::create([
            'id' => (string) Str::uuid(),
            'nama_periode' => 'Periode Review',
            'tahun_ajaran' => '2024/2025',
            'gelombang' => 'Utama',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDays(2),
            'tanggal_tutup' => now()->addDays(5),
            'status_pengumuman' => 'AKTIF',
            'is_active' => true,
        ]);

        $this->paket1 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket A Review',
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
            'dokumen_wali_path' => 'dokumen_wali/test.pdf',
        ]);

        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $this->pendaftaran->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'urutan_pilihan' => 1,
        ]);
    }

    public function test_admin_can_list_pendaftaran_pilihan(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/pendaftaran-pilihan');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_admin_can_view_detail_pendaftaran(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/pendaftaran-pilihan/' . $this->pendaftaran->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->pendaftaran->id,
                    'status' => 'menunggu',
                ]
            ]);
    }

    public function test_admin_can_approve_pendaftaran(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->putJson('/admin/pendaftaran-pilihan/' . $this->pendaftaran->id . '/approve');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('disetujui', $this->pendaftaran->fresh()->status);
        $this->assertEquals($this->admin->id, $this->pendaftaran->fresh()->ditinjau_oleh);
    }

    public function test_admin_can_reject_pendaftaran_with_catatan(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->putJson('/admin/pendaftaran-pilihan/' . $this->pendaftaran->id . '/reject', [
                'catatan_penolakan' => 'Dokumen wali tidak terbaca.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('ditolak', $this->pendaftaran->fresh()->status);
        $this->assertEquals('Dokumen wali tidak terbaca.', $this->pendaftaran->fresh()->catatan_penolakan);
    }

    public function test_admin_can_download_dokumen_wali(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('surat_wali.pdf', 200, 'application/pdf');
        $path = $file->store('dokumen_wali', 'public');

        $this->pendaftaran->update(['dokumen_wali_path' => $path]);

        $response = $this->actingAs($this->admin, 'web')
            ->get('/admin/pendaftaran-pilihan/' . $this->pendaftaran->id . '/dokumen');

        $response->assertStatus(200);
    }

    /** Request browser non-JSON redirect setelah approve */
    public function test_browser_approve_redirects_back_with_success_flash(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->from('/admin/pendaftaran-pilihan')
            ->put('/admin/pendaftaran-pilihan/' . $this->pendaftaran->id . '/approve');

        $response->assertRedirect('/admin/pendaftaran-pilihan');
        $response->assertSessionHas('success', 'Pengajuan pilihan paket berhasil disetujui.');
        $this->assertEquals('disetujui', $this->pendaftaran->fresh()->status);
    }

    /** Request browser non-JSON redirect setelah reject */
    public function test_browser_reject_redirects_back_with_success_flash(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->from('/admin/pendaftaran-pilihan')
            ->put('/admin/pendaftaran-pilihan/' . $this->pendaftaran->id . '/reject', [
                'catatan_penolakan' => 'Dokumen wali tidak terbaca.',
            ]);

        $response->assertRedirect('/admin/pendaftaran-pilihan');
        $response->assertSessionHas('success', 'Pengajuan pilihan paket berhasil ditolak.');
        $this->assertEquals('ditolak', $this->pendaftaran->fresh()->status);
    }
}
