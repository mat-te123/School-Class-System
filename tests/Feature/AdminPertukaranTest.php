<?php

namespace Tests\Feature;

use App\Models\HasilSeleksi;
use App\Models\KelasAsal;
use App\Models\PaketMenuPilihan;
use App\Models\PengajuanPertukaran;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPertukaranTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $nonAdmin;
    private Siswa $siswa;
    private PeriodePendaftaran $periode;
    private PaketMenuPilihan $paketAsal;
    private PaketMenuPilihan $paketTujuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_pertukaran',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->nonAdmin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'guru_bk_pertukaran',
            'password' => bcrypt('password'),
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XII IPA 1',
        ]);

        $this->siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '1234567890',
            'nis' => '12345',
            'nama_lengkap' => 'Siswa Pertukaran',
            'kelas_asal_id' => $kelas->id,
            'jenis_kelamin' => 'L',
            'angkatan' => '2024',
            'is_active' => true,
        ]);

        $this->periode = PeriodePendaftaran::create([
            'id' => (string) Str::uuid(),
            'nama_periode' => 'Periode Pertukaran',
            'tahun_ajaran' => '2024/2025',
            'gelombang' => 'Utama',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDays(10),
            'tanggal_tutup' => now()->subDay(),
            'status_pengumuman' => 'AKTIF',
            'is_active' => true,
        ]);

        $this->paketAsal = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket IPA',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 1,
            'is_active' => true,
        ]);

        $this->paketTujuan = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket IPS',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 1,
            'is_active' => true,
        ]);

        HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paketAsal->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 5,
            'skor_penempatan' => 85.50,
            'rata_6_mapel' => 88.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);
    }

    private function createPengajuan(string $status = 'menunggu'): PengajuanPertukaran
    {
        return PengajuanPertukaran::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'paket_asal_id' => $this->paketAsal->id,
            'paket_tujuan_id' => $this->paketTujuan->id,
            'alasan' => 'Ingin pindah sesuai minat karir',
            'dokumen_persetujuan_path' => 'dokumen_pertukaran/test.pdf',
            'status' => $status,
        ]);
    }

    public function test_admin_can_list_and_filter_pertukaran(): void
    {
        $p = $this->createPengajuan('menunggu');

        $resp = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/pertukaran?status=menunggu');
        $resp->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->admin->refresh();
    }

    public function test_admin_can_view_pertukaran_detail(): void
    {
        $p = $this->createPengajuan();

        $resp = $this->actingAs($this->admin, 'web')
            ->getJson("/admin/pertukaran/{$p->id}");
        $resp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $p->id)
            ->assertJsonPath('data.status', 'menunggu');
    }

    public function test_admin_can_download_dokumen(): void
    {
        $p = $this->createPengajuan();

        // Seed a file in public disk
        $path = $p->dokumen_persetujuan_path;
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, 'dummy-pdf-content');

        $resp = $this->actingAs($this->admin, 'web')
            ->get("/admin/pertukaran/{$p->id}/dokumen");
        $resp->assertStatus(200);
    }

    public function test_approve_pertukaran_updates_hasil_seleksi(): void
    {
        $p = $this->createPengajuan();

        $resp = $this->actingAs($this->admin, 'web')
            ->putJson("/admin/pertukaran/{$p->id}/approve", [
                'catatan_admin' => 'Disetujui berdasarkan kuota tersedia.',
            ]);

        $resp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'disetujui');

        // HasilSeleksi updated to target paket
        $this->assertDatabaseHas('hasil_seleksi', [
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paketTujuan->id,
            'is_manual_override' => true,
        ]);

        $this->assertDatabaseHas('pengajuan_pertukaran', [
            'id' => $p->id,
            'status' => 'disetujui',
            'ditinjau_oleh' => $this->admin->id,
        ]);
    }

    public function test_reject_pertukaran_requires_reason(): void
    {
        $p = $this->createPengajuan();

        $resp = $this->actingAs($this->admin, 'web')
            ->putJson("/admin/pertukaran/{$p->id}/reject", []);

        $resp->assertStatus(422);

        // HasilSeleksi unchanged
        $this->assertDatabaseHas('hasil_seleksi', [
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paketAsal->id,
            'is_manual_override' => false,
        ]);
    }

    public function test_reject_pertukaran_success(): void
    {
        $p = $this->createPengajuan();

        $resp = $this->actingAs($this->admin, 'web')
            ->putJson("/admin/pertukaran/{$p->id}/reject", [
                'catatan_admin' => 'Dokumen wali belum dilengkapi.',
            ]);

        $resp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ditolak');

        // HasilSeleksi unchanged
        $this->assertDatabaseHas('hasil_seleksi', [
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paketAsal->id,
            'is_manual_override' => false,
        ]);
    }

    public function test_non_admin_cannot_access_pertukaran_endpoints(): void
    {
        $p = $this->createPengajuan();

        $this->actingAs($this->nonAdmin, 'web')
            ->getJson('/admin/pertukaran')
            ->assertStatus(403);

        $this->actingAs($this->nonAdmin, 'web')
            ->putJson("/admin/pertukaran/{$p->id}/approve")
            ->assertStatus(403);

        $this->actingAs($this->nonAdmin, 'web')
            ->putJson("/admin/pertukaran/{$p->id}/reject", ['catatan_admin' => 'x'])
            ->assertStatus(403);
    }
}
