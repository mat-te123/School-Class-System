<?php

namespace Tests\Feature;

use App\Models\HasilSeleksi;
use App\Models\KelasAsal;
use App\Models\NilaiLegerSiswa;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\DetailPendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminLaporanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $nonAdmin;
    private Siswa $siswa;
    private PeriodePendaftaran $periode;
    private PaketMenuPilihan $paket1;
    private PaketMenuPilihan $paket2;
    private PaketMenuPilihan $paket3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'laporan_admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->nonAdmin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'laporan_guru',
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
            'nisn' => '5555555555',
            'nis' => '55555',
            'nama_lengkap' => 'Siswa Laporan',
            'kelas_asal_id' => $kelas->id,
            'jenis_kelamin' => 'L',
            'angkatan' => '2024',
            'is_active' => true,
        ]);

        $this->periode = PeriodePendaftaran::create([
            'id' => (string) Str::uuid(),
            'nama_periode' => 'Periode Laporan',
            'tahun_ajaran' => '2024/2025',
            'gelombang' => 'Utama',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDays(10),
            'tanggal_tutup' => now()->subDay(),
            'status_pengumuman' => 'AKTIF',
            'is_active' => true,
        ]);

        $this->paket1 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket IPA',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $this->paket2 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket IPS',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $this->paket3 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket Bahasa',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        // Pendaftaran dengan 3 pilihan
        $pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'disetujui',
        ]);

        DetailPendaftaranPilihan::insert([
            ['id' => (string) Str::uuid(), 'pendaftaran_pilihan_id' => $pendaftaran->id, 'paket_menu_pilihan_id' => $this->paket1->id, 'urutan_pilihan' => 1],
            ['id' => (string) Str::uuid(), 'pendaftaran_pilihan_id' => $pendaftaran->id, 'paket_menu_pilihan_id' => $this->paket2->id, 'urutan_pilihan' => 2],
            ['id' => (string) Str::uuid(), 'pendaftaran_pilihan_id' => $pendaftaran->id, 'paket_menu_pilihan_id' => $this->paket3->id, 'urutan_pilihan' => 3],
        ]);

        HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 1,
            'skor_penempatan' => 90.00,
            'rata_6_mapel' => 92.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);
    }

    public function test_admin_can_view_hasil_penjurusan_laporan(): void
    {
        $resp = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/laporan/hasil-penjurusan?periode_id=' . $this->periode->id);
        $resp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1);
        // Float cast issue in SQLite: use assertEquals
        $this->assertEquals(90.0, (float) $resp->json('data.data.0.skor_penempatan'));
    }

    public function test_admin_can_filter_hasil_penjurusan_by_paket(): void
    {
        $resp = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/laporan/hasil-penjurusan?periode_id=' . $this->periode->id . '&paket_id=' . $this->paket1->id);
        $resp->assertStatus(200)
            ->assertJsonPath('data.data.0.paket_menu_pilihan_id', $this->paket1->id);

        // Paket tanpa hasil -> kosong
        $resp2 = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/laporan/hasil-penjurusan?periode_id=' . $this->periode->id . '&paket_id=' . $this->paket2->id);
        $resp2->assertStatus(200)
            ->assertJsonPath('data.total', 0);
    }

    public function test_admin_can_view_minat_siswa_laporan(): void
    {
        $resp = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/laporan/minat-siswa?periode_id=' . $this->periode->id);
        $resp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1);
        // detail_pendaftaran not exposed as _count attribute without withCount()
        $this->assertCount(3, $resp->json('data.data.0.detail_pendaftaran'));
    }

    public function test_admin_can_filter_minat_siswa_by_status(): void
    {
        // Filter status = ditolak -> 0
        $resp = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/laporan/minat-siswa?periode_id=' . $this->periode->id . '&status=ditolak');
        $resp->assertStatus(200)
            ->assertJsonPath('data.total', 0);

        // Filter status = disetujui -> 1
        $resp2 = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/laporan/minat-siswa?periode_id=' . $this->periode->id . '&status=disetujui');
        $resp2->assertStatus(200)
            ->assertJsonPath('data.total', 1);
    }

    public function test_admin_can_view_peminat_vs_kuota(): void
    {
        $resp = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/laporan/peminat-vs-kuota?periode_id=' . $this->periode->id);
        $resp->assertStatus(200)
            ->assertJsonPath('success', true);

                // Paket1: pilihan1=1, pilihan2=0, pilihan3=0, total=1
        // Paket2: pilihan1=0, pilihan2=1, pilihan3=0, total=1
        // Paket3: pilihan1=0, pilihan2=0, pilihan3=1, total=1
        $rows = collect($resp->json('data'));
        $paket1 = $rows->firstWhere('id', $this->paket1->id);
        $this->assertEquals(1, $paket1['pilihan_1']);
        $this->assertEquals(0, $paket1['pilihan_2']);
        $this->assertEquals(0, $paket1['pilihan_3']);
        $this->assertEquals(1, $paket1['total_peminat']);
        $this->assertEquals(1, $paket1['terisi']);
        $this->assertEquals(35, $paket1['sisa_kuota']);

        $paket2 = $rows->firstWhere('id', $this->paket2->id);
        $this->assertEquals(0, $paket2['pilihan_1']);
        $this->assertEquals(1, $paket2['pilihan_2']);
        $this->assertEquals(0, $paket2['pilihan_3']);
        $this->assertEquals(1, $paket2['total_peminat']);
        $this->assertEquals(0, $paket2['terisi']);

        $paket3 = $rows->firstWhere('id', $this->paket3->id);
        $this->assertEquals(0, $paket3['pilihan_1']);
        $this->assertEquals(0, $paket3['pilihan_2']);
        $this->assertEquals(1, $paket3['pilihan_3']);
        $this->assertEquals(1, $paket3['total_peminat']);
    }

    public function test_admin_can_export_hasil_penjurusan_xlsx(): void
    {
        $resp = $this->actingAs($this->admin, 'web')
            ->get('/admin/laporan/export/hasil-penjurusan?periode_id=' . $this->periode->id . '&format=xlsx');

        $resp->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resp->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=', $resp->headers->get('Content-Disposition'));

        $content = $resp->streamedContent();
        $this->assertStringContainsString('Siswa Laporan', $content);
        $this->assertStringContainsString('Paket IPA', $content);
    }

    public function test_admin_can_export_minat_siswa_pdf(): void
    {
        $resp = $this->actingAs($this->admin, 'web')
            ->get('/admin/laporan/export/minat-siswa?periode_id=' . $this->periode->id . '&format=pdf');

        $resp->assertStatus(200);
        $this->assertStringContainsString('text/html', $resp->headers->get('Content-Type'));
        $this->assertStringContainsString('Laporan Minat Siswa', $resp->getContent());
    }

    public function test_non_admin_cannot_access_laporan_endpoints(): void
    {
        $this->actingAs($this->nonAdmin, 'web')
            ->getJson('/admin/laporan/hasil-penjurusan?periode_id=' . $this->periode->id)
            ->assertStatus(403);

        $this->actingAs($this->nonAdmin, 'web')
            ->getJson('/admin/laporan/peminat-vs-kuota?periode_id=' . $this->periode->id)
            ->assertStatus(403);
    }
}
