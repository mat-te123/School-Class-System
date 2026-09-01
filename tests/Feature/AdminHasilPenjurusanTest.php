<?php

namespace Tests\Feature;

use App\Models\HasilSeleksi;
use App\Models\KelasAsal;
use App\Models\KriteriaBobotMenu;
use App\Models\MasterMataPelajaran;
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

class AdminHasilPenjurusanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $nonAdmin;
    private Siswa $siswa1;
    private Siswa $siswa2;
    private PeriodePendaftaran $periode;
    private PaketMenuPilihan $paket1;
    private PaketMenuPilihan $paket2;
    private MasterMataPelajaran $mapel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->nonAdmin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'nonadmin',
            'password' => bcrypt('password'),
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'XII IPA 1',
        ]);

        $this->siswa1 = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '1111111111',
            'nis' => '11111',
            'nama_lengkap' => 'Siswa Satu',
            'kelas_asal_id' => $kelas->id,
            'jenis_kelamin' => 'L',
            'angkatan' => '2024',
            'is_active' => true,
        ]);

        $this->siswa2 = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '2222222222',
            'nis' => '22222',
            'nama_lengkap' => 'Siswa Dua',
            'kelas_asal_id' => $kelas->id,
            'jenis_kelamin' => 'P',
            'angkatan' => '2024',
            'is_active' => true,
        ]);

        $this->periode = PeriodePendaftaran::create([
            'id' => (string) Str::uuid(),
            'nama_periode' => 'Periode Test',
            'tahun_ajaran' => '2024/2025',
            'gelombang' => 'Utama',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDays(10),
            'tanggal_tutup' => now()->subDay(),
            'status_pengumuman' => 'NON-AKTIF',
            'is_active' => true,
            'is_hasil_final' => false,
        ]);

        $this->paket1 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket IPA',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 2,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $this->paket2 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket IPS',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 2,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $this->mapel = MasterMataPelajaran::create([
            'id' => (string) Str::uuid(),
            'kode_mapel' => 'BIND',
            'nama_mapel' => 'B.IND',
            'is_active' => true,
        ]);

        // Setup kriteria bobot
        KriteriaBobotMenu::create([
            'id' => (string) Str::uuid(),
            'paket_menu_pilihan_id' => $this->paket1->id,
            'master_mata_pelajaran_id' => $this->mapel->id,
            'bobot_persen' => 100,
        ]);

        // Setup nilai leger
        NilaiLegerSiswa::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa1->id,
            'tahun_ajaran' => '2024/2025',
            'semester' => 1,
            'rata_6_mapel' => 85.00,
            'rata_keseluruhan' => 85.00,
            'nilai_json' => ['B.IND' => 85],
        ]);

        NilaiLegerSiswa::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa2->id,
            'tahun_ajaran' => '2024/2025',
            'semester' => 1,
            'rata_6_mapel' => 90.00,
            'rata_keseluruhan' => 90.00,
            'nilai_json' => ['B.IND' => 90],
        ]);

        // Setup pendaftaran disetujui
        $pend1 = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa1->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'disetujui',
        ]);

        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pend1->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'urutan_pilihan' => 1,
        ]);

        $pend2 = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa2->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'disetujui',
        ]);

        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pend2->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'urutan_pilihan' => 1,
        ]);
    }

    public function test_admin_can_run_placement_process(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/hasil-penjurusan/process', [
                'periode_id' => $this->periode->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('hasil_seleksi', [
            'siswa_id' => $this->siswa1->id,
            'mekanisme' => 'Pilihan 1',
        ]);

        $this->assertDatabaseHas('hasil_seleksi', [
            'siswa_id' => $this->siswa2->id,
        ]);
    }

    public function test_non_admin_cannot_run_placement_process(): void
    {
        $response = $this->actingAs($this->nonAdmin, 'web')
            ->postJson('/admin/hasil-penjurusan/process', [
                'periode_id' => $this->periode->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_hasil_penjurusan(): void
    {
        HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa1->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 1,
            'skor_penempatan' => 85.00,
            'rata_6_mapel' => 85.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/hasil-penjurusan?periode_id=' . $this->periode->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_view_rekap_kuota(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/hasil-penjurusan/rekap-kuota?periode_id=' . $this->periode->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_view_detail_siswa(): void
    {
        $hasil = HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa1->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 1,
            'skor_penempatan' => 85.00,
            'rata_6_mapel' => 85.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/admin/hasil-penjurusan/siswa/' . $this->siswa1->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_override_hasil_with_reason(): void
    {
        $hasil = HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa1->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 1,
            'skor_penempatan' => 85.00,
            'rata_6_mapel' => 85.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->putJson('/admin/hasil-penjurusan/' . $hasil->id . '/override', [
                'paket_menu_pilihan_id' => $this->paket2->id,
                'catatan_perubahan' => 'Perubahan atas permintaan orang tua siswa.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('hasil_seleksi', [
            'id' => $hasil->id,
            'paket_menu_pilihan_id' => $this->paket2->id,
            'is_manual_override' => true,
            'diubah_oleh' => $this->admin->id,
        ]);
    }

    public function test_override_requires_catatan_perubahan(): void
    {
        $hasil = HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa1->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 1,
            'skor_penempatan' => 85.00,
            'rata_6_mapel' => 85.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->putJson('/admin/hasil-penjurusan/' . $hasil->id . '/override', [
                'paket_menu_pilihan_id' => $this->paket2->id,
                'catatan_perubahan' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_override_when_hasil_is_locked(): void
    {
        $this->periode->update(['is_hasil_final' => true]);

        $hasil = HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa1->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 1,
            'skor_penempatan' => 85.00,
            'rata_6_mapel' => 85.00,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->putJson('/admin/hasil-penjurusan/' . $hasil->id . '/override', [
                'paket_menu_pilihan_id' => $this->paket2->id,
                'catatan_perubahan' => 'Test reason',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Hasil penjurusan sudah dikunci. Buka kunci terlebih dahulu untuk melakukan perubahan.',
            ]);
    }

    public function test_admin_can_lock_hasil_final(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/hasil-penjurusan/lock', [
                'periode_id' => $this->periode->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id' => $this->periode->id,
            'is_hasil_final' => true,
        ]);
    }

    public function test_admin_can_unlock_hasil_final(): void
    {
        $this->periode->update(['is_hasil_final' => true]);

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/hasil-penjurusan/unlock', [
                'periode_id' => $this->periode->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id' => $this->periode->id,
            'is_hasil_final' => false,
        ]);
    }

    public function test_admin_can_publish_hasil(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/hasil-penjurusan/publish', [
                'periode_id' => $this->periode->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id' => $this->periode->id,
            'status_pengumuman' => 'AKTIF',
        ]);
    }

    public function test_admin_can_unpublish_hasil(): void
    {
        $this->periode->update(['status_pengumuman' => 'AKTIF']);

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/hasil-penjurusan/unpublish', [
                'periode_id' => $this->periode->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id' => $this->periode->id,
            'status_pengumuman' => 'NON-AKTIF',
        ]);
    }

    public function test_placement_prioritizes_choice_1_over_choice_3_even_with_higher_choice_3_score(): void
    {
        $this->paket1->update(['kuota_kapasitas' => 10]);

        // Buat paket ke-3
        $paket3 = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'nama_menu' => 'Paket Sosial 2',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 5,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $mapel2 = MasterMataPelajaran::create([
            'id' => (string) Str::uuid(),
            'kode_mapel' => 'BING',
            'nama_mapel' => 'B.ING',
            'is_active' => true,
        ]);

        KriteriaBobotMenu::create([
            'id' => (string) Str::uuid(),
            'paket_menu_pilihan_id' => $paket3->id,
            'master_mata_pelajaran_id' => $mapel2->id,
            'bobot_persen' => 100,
        ]);

        // Buat siswa baru dengan nilai B.IND = 75 (skor paket 1 = 75), dan B.ING = 95 (skor paket 3 = 95)
        $siswa3 = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '3333333333',
            'nis' => '33333',
            'nama_lengkap' => 'Siswa Tiga',
            'kelas_asal_id' => $this->siswa1->kelas_asal_id,
            'jenis_kelamin' => 'P',
            'angkatan' => '2024',
            'is_active' => true,
        ]);

        NilaiLegerSiswa::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $siswa3->id,
            'tahun_ajaran' => '2024/2025',
            'semester' => 1,
            'rata_6_mapel' => 80.00,
            'rata_keseluruhan' => 80.00,
            'nilai_json' => ['B.IND' => 75, 'B.ING' => 95],
        ]);

        $pend3 = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $siswa3->id,
            'periode_pendaftaran_id' => $this->periode->id,
            'tanggal_submit' => now(),
            'status' => 'disetujui',
        ]);

        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pend3->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'urutan_pilihan' => 1,
        ]);

        DetailPendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'pendaftaran_pilihan_id' => $pend3->id,
            'paket_menu_pilihan_id' => $paket3->id,
            'urutan_pilihan' => 3,
        ]);

        // Jalankan proses penempatan
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/admin/hasil-penjurusan/process', [
                'periode_id' => $this->periode->id,
            ]);

        $response->assertStatus(200);

        // Siswa 3 harus diterima di Paket 1 (Pilihan 1), BUKAN di Paket 3 (Pilihan 3)
        $this->assertDatabaseHas('hasil_seleksi', [
            'siswa_id' => $siswa3->id,
            'paket_menu_pilihan_id' => $this->paket1->id,
            'pilihan_ke_diterima' => 1,
            'mekanisme' => 'Pilihan 1',
        ]);
    }
}
