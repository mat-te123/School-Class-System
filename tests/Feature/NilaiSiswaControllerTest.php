<?php

namespace Tests\Feature;

use App\Models\DetailNilaiSiswa;
use App\Models\KelasAsal;
use App\Models\MasterMataPelajaran;
use App\Models\NilaiLegerSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NilaiSiswaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Siswa $siswa;
    protected MasterMataPelajaran $mapel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'admin_nilai',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $kelas = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X B',
            'tingkat' => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $this->siswa = Siswa::create([
            'nisn' => '1234567890',
            'nis' => '2001',
            'nama_lengkap' => 'Budi Santoso',
            'kelas_asal_id' => $kelas->id,
            'kelas_asal' => 'X B',
            'is_active' => true,
        ]);

        $this->mapel = MasterMataPelajaran::create([
            'kode_mapel' => 'matematika',
            'nama_mapel' => 'Matematika',
            'kelompok_mapel' => 'umum',
            'is_active' => true,
        ]);
    }

    /** FR-13: impor nilai untuk satu mapel tertentu */
    public function test_admin_can_import_nilai_for_specific_mapel(): void
    {
        $response = $this->actingAs($this->admin, 'web')->postJson('/nilai-siswa/import-mapel', [
            'mapel_id' => $this->mapel->id,
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Genap',
            'rows' => [
                ['nisn' => '1234567890', 'nilai' => 88],
                ['nisn' => '0000000000', 'nilai' => 70],
            ],
        ]);

        $response->assertStatus(202)
            ->assertJson(['success' => true, 'status' => 'queued']);

        $this->assertEquals(1, $response->json('skipped_validation'));

        $leger = NilaiLegerSiswa::where('siswa_id', $this->siswa->id)->firstOrFail();
        $this->assertEquals(88.0, $leger->rata_keseluruhan);

        $detail = DetailNilaiSiswa::where('nilai_leger_siswa_id', $leger->id)->firstOrFail();
        $this->assertEquals('B', $detail->predikat);
    }

    /** FR-13: impor ulang mapel yang sama meng-update nilai, bukan duplikat */
    public function test_reimport_same_mapel_updates_instead_of_duplicating(): void
    {
        $payload = [
            'mapel_id' => $this->mapel->id,
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Genap',
            'rows' => [['nisn' => '1234567890', 'nilai' => 60]],
        ];

        $this->actingAs($this->admin, 'web')->postJson('/nilai-siswa/import-mapel', $payload)->assertStatus(202);

        $payload['rows'][0]['nilai'] = 95;
        $this->actingAs($this->admin, 'web')->postJson('/nilai-siswa/import-mapel', $payload)->assertStatus(202);

        $this->assertEquals(1, DetailNilaiSiswa::count());
        $this->assertEquals(95.0, DetailNilaiSiswa::first()->nilai_angka);
        $this->assertEquals('A', DetailNilaiSiswa::first()->predikat);
    }

    /** FR-14: lihat daftar nilai dengan filter nisn */
    public function test_can_view_nilai_filtered_by_nisn(): void
    {
        $this->seedNilai(75);

        $response = $this->actingAs($this->admin, 'web')->getJson('/nilai-siswa?nisn=1234567890');

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertCount(1, $response->json('data.data'));
    }

    /** FR-14: perbaiki nilai yang tidak sesuai & rata-rata dihitung ulang */
    public function test_admin_can_correct_nilai_and_leger_is_recalculated(): void
    {
        $detail = $this->seedNilai(50);

        $response = $this->actingAs($this->admin, 'web')->putJson('/nilai-siswa/' . $detail->id, [
            'nilai_angka' => 91,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $detail->refresh();
        $this->assertEquals(91.0, $detail->nilai_angka);
        $this->assertEquals('A', $detail->predikat);
        $this->assertEquals(91.0, $detail->leger->rata_keseluruhan);
        $this->assertEquals(91.0, $detail->leger->nilai_json['Matematika']);
    }

    /** Daftar nilai khusus admin */
    public function test_non_admin_cannot_view_nilai(): void
    {
        $guru = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'guru_view_nilai',
            'password' => 'password123',
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $this->seedNilai(80);

        $this->actingAs($guru, 'web')
            ->getJson('/nilai-siswa')
            ->assertStatus(403);
    }

    /** Impor nilai khusus admin */
    public function test_non_admin_cannot_import_nilai(): void
    {
        $guru = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'guru_import_nilai',
            'password' => 'password123',
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $this->actingAs($guru, 'web')->postJson('/nilai-siswa/import-mapel', [
            'mapel_id' => $this->mapel->id,
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Genap',
            'rows' => [['nisn' => '1234567890', 'nilai' => 80]],
        ])->assertStatus(403);
    }

    /** Pagination dibatasi agar query tidak dapat meminta data tanpa batas */
    public function test_per_page_above_limit_is_rejected(): void
    {
        $this->actingAs($this->admin, 'web')
            ->getJson('/nilai-siswa?per_page=1000')
            ->assertStatus(422);
    }

    /** FR-14: nilai di luar 0-100 ditolak */
    public function test_invalid_nilai_is_rejected(): void
    {
        $detail = $this->seedNilai(80);

        $this->actingAs($this->admin, 'web')
            ->putJson('/nilai-siswa/' . $detail->id, ['nilai_angka' => 150])
            ->assertStatus(422);
    }

    /** Non-admin tidak boleh mengubah nilai */
    public function test_non_admin_cannot_correct_nilai(): void
    {
        $guru = User::create([
            'id' => (string) Str::uuid(),
            'username' => 'guru_bk',
            'password' => 'password123',
            'role' => 'guru_bk',
            'is_active' => true,
        ]);

        $detail = $this->seedNilai(80);

        $this->actingAs($guru, 'web')
            ->putJson('/nilai-siswa/' . $detail->id, ['nilai_angka' => 90])
            ->assertStatus(403);
    }

    /** FR-49: Siswa dapat melihat nilai mereka sendiri */
    public function test_siswa_can_view_their_own_nilai(): void
    {
        $this->seedNilai(85);

        $response = $this->actingAs($this->siswa, 'siswa')->getJson('/siswa/nilai');

        $response->assertOk()
            ->assertJson(['success' => true]);
        
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(85.0, $response->json('data.0.nilai_angka'));
        $this->assertEquals($this->siswa->nisn, $response->json('data.0.leger.siswa.nisn'));
    }

    /** FR-49: User belum login ditolak dari endpoint nilai siswa */
    public function test_unauthenticated_cannot_view_siswa_nilai(): void
    {
        $this->getJson('/siswa/nilai')->assertStatus(401);
    }

    /** FR-49: Admin tidak bisa akses endpoint siswa */
    public function test_admin_cannot_view_via_siswa_endpoint(): void
    {
        $this->actingAs($this->admin, 'web')
            ->getJson('/siswa/nilai')
            ->assertStatus(401); // Karena middleware auth:siswa tidak menemukan session guard siswa
    }

    /** Request browser reguler merender view nilai-siswa.index-siswa */
    public function test_siswa_can_view_nilai_index_blade(): void
    {
        $this->seedNilai(85);

        $response = $this->actingAs($this->siswa, 'siswa')->get('/siswa/nilai');

        $response->assertOk();
        $response->assertViewIs('nilai-siswa.index-siswa');
        $response->assertViewHas('data');
    }

    private function seedNilai(float $nilai): DetailNilaiSiswa
    {
        $leger = NilaiLegerSiswa::create([
            'siswa_id' => $this->siswa->id,
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Genap',
            'rata_6_mapel' => $nilai,
            'rata_keseluruhan' => $nilai,
            'nilai_json' => ['Matematika' => $nilai],
        ]);

        return DetailNilaiSiswa::create([
            'nilai_leger_siswa_id' => $leger->id,
            'master_mata_pelajaran_id' => $this->mapel->id,
            'nilai_angka' => $nilai,
            'predikat' => 'C',
        ]);
    }

    /** Request browser non-JSON redirect setelah update */
    public function test_browser_update_redirects_back_with_success_flash(): void
    {
        $detail = $this->seedNilai(75);

        $response = $this->actingAs($this->admin, 'web')
            ->from('/nilai-siswa')
            ->put('/nilai-siswa/' . $detail->id, [
                'nilai_angka' => 90.0,
            ]);

        $response->assertRedirect('/nilai-siswa');
        $response->assertSessionHas('success', 'Berhasil memperbaiki nilai siswa.');
        $this->assertEquals(90.0, $detail->fresh()->nilai_angka);
    }

    /** Request browser non-JSON redirect setelah importMapel */
    public function test_browser_import_mapel_redirects_back_with_success_flash(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->from('/nilai-siswa')
            ->post('/nilai-siswa/import-mapel', [
                'mapel_id' => $this->mapel->id,
                'tahun_ajaran' => '2024/2025',
                'semester' => 'Genap',
                'rows' => [
                    ['nisn' => '1234567890', 'nilai' => 85.0],
                ],
            ]);

        $response->assertRedirect('/nilai-siswa');
        $response->assertSessionHas('success', "Impor nilai '{$this->mapel->nama_mapel}' sedang diproses di background.");
    }
}
