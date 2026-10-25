<?php

namespace Tests\Feature;

use App\Models\HasilSeleksi;
use App\Models\KelasAsal;
use App\Models\PaketMenuPilihan;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlurSiswaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Siswa $siswa;
    private KelasAsal $kelasAsal;
    private PeriodePendaftaran $periodeAktif;
    private PaketMenuPilihan $paketA;
    private PaketMenuPilihan $paketB;
    private PaketMenuPilihan $paketC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kelasAsal = KelasAsal::create([
            'id' => (string) Str::uuid(),
            'nama_kelas' => 'X-1',
            'tingkat' => '10',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $this->siswa = Siswa::create([
            'id' => (string) Str::uuid(),
            'nisn' => '1234567890',
            'nis' => '10001',
            'nama_lengkap' => 'Ahmad Siswa',
            'kelas_asal_id' => $this->kelasAsal->id,
            'jenis_kelamin' => 'L',
            'angkatan' => '2026',
            'is_active' => true,
            'password' => 'password123',
        ]);

        $this->periodeAktif = PeriodePendaftaran::create([
            'id' => (string) Str::uuid(),
            'nama_periode' => 'Penjurusan TP 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'gelombang' => '1',
            'max_pilihan_siswa' => 3,
            'tanggal_buka' => now()->subDays(2),
            'tanggal_tutup' => now()->addDays(5),
            'status_pengumuman' => 'NON-AKTIF',
            'is_active' => true,
        ]);

        $this->paketA = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'periode_id' => $this->periodeAktif->id,
            'nama_menu' => 'Kelas MIPA 1',
            'rumpun' => 'eksakta',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $this->paketB = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'periode_id' => $this->periodeAktif->id,
            'nama_menu' => 'Kelas IPS 1',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);

        $this->paketC = PaketMenuPilihan::create([
            'id' => (string) Str::uuid(),
            'periode_id' => $this->periodeAktif->id,
            'nama_menu' => 'Kelas Bahasa 1',
            'rumpun' => 'sosial',
            'kuota_kapasitas' => 36,
            'kuota_terisi' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * 1. Autentikasi: Validasi data login. Jika tidak valid, tampilkan pesan kesalahan.
     */
    public function test_tahap_1_autentikasi_invalid_credentials_returns_error(): void
    {
        // Password salah
        $response = $this->from('/login/siswa')->post('/login/siswa', [
            'nisn' => '1234567890',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login/siswa');
        $response->assertSessionHasErrors('password');

        // NISN tidak terdaftar
        $responseNisn = $this->from('/login/siswa')->post('/login/siswa', [
            'nisn' => '0000000000',
            'password' => 'password123',
        ]);

        $responseNisn->assertRedirect('/login/siswa');
        $responseNisn->assertSessionHasErrors('nisn');
    }

    public function test_tahap_1_autentikasi_valid_credentials_redirects_to_dashboard(): void
    {
        $response = $this->post('/login/siswa', [
            'nisn' => '1234567890',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/siswa/dashboard');
        $this->assertAuthenticatedAs($this->siswa, 'siswa');
    }

    public function test_tahap_1_autentikasi_redirects_to_dashboard_even_if_intended_url_points_to_admin_login(): void
    {
        // Simulasikan session memiliki intended URL ke /login admin
        $response = $this->withSession(['url.intended' => 'http://localhost/login'])
            ->post('/login/siswa', [
                'nisn' => '1234567890',
                'password' => 'password123',
            ]);

        $response->assertRedirect('/siswa/dashboard');
        $this->assertAuthenticatedAs($this->siswa, 'siswa');
    }

    /**
     * 2. Dashboard: Setelah login berhasil, sistem mengambil data profil dan periode aktif.
     */
    public function test_tahap_2_dashboard_retrieves_profile_and_active_period(): void
    {
        $response = $this->actingAs($this->siswa, 'siswa')
            ->get('/siswa/dashboard');

        $response->assertOk()
            ->assertViewIs('siswa.dashboard.index')
            ->assertViewHas('siswa', fn ($viewSiswa) => $viewSiswa->id === $this->siswa->id && $viewSiswa->relationLoaded('kelasAsalRelation'))
            ->assertViewHas('periodeAktif', fn ($viewPeriode) => $viewPeriode->id === $this->periodeAktif->id);

        // Uji juga via endpoint JSON
        $responseJson = $this->actingAs($this->siswa, 'siswa')
            ->getJson('/siswa/dashboard');

        $responseJson->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.siswa.id', $this->siswa->id)
            ->assertJsonPath('data.periode_aktif.id', $this->periodeAktif->id);
    }

    public function test_dashboard_returns_null_periode_aktif_when_period_has_closed(): void
    {
        // Ubah tanggal tutup periode menjadi masa lalu (sudah tutup)
        $this->periodeAktif->update([
            'tanggal_buka' => now()->subDays(10),
            'tanggal_tutup' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->siswa, 'siswa')
            ->get('/siswa/dashboard');

        $response->assertOk()
            ->assertViewIs('siswa.dashboard.index')
            ->assertViewHas('periodeAktif', null)
            ->assertDontSeeText('Periode pendaftaran');

        $responseJson = $this->actingAs($this->siswa, 'siswa')
            ->getJson('/siswa/dashboard');

        $responseJson->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.periode_aktif', null);
    }

    /**
     * 3. Periode: Sistem mengambil daftar periode dan menampilkan data yang sesuai.
     */
    public function test_tahap_3_periode_retrieves_period_list_and_matching_classes(): void
    {
        $response = $this->actingAs($this->siswa, 'siswa')
            ->get('/siswa/periode');

        $response->assertOk()
            ->assertViewIs('siswa.periode.index')
            ->assertViewHas('activePeriods')
            ->assertViewHas('formattedMenus');

        $responseJson = $this->actingAs($this->siswa, 'siswa')
            ->getJson('/siswa/periode');

        $responseJson->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->periodeAktif->id);
    }

    /**
     * 4. Pemilihan kelas: Sistem memvalidasi pilihan kelas, kemudian menyimpan atau memperbarui relasi kelas.
     */
    public function test_tahap_4_pemilihan_kelas_validates_and_stores_or_updates_relation(): void
    {
        // 4a. Menyimpan pilihan kelas baru
        $payload = [
            'pilihan' => [
                $this->paketA->id,
                $this->paketB->id,
                $this->paketC->id,
            ],
        ];

        $responseStore = $this->actingAs($this->siswa, 'siswa')
            ->from('/siswa/pilih-kelas')
            ->post('/siswa/pilih-kelas', $payload);

        $responseStore->assertRedirect('/siswa/pilih-kelas');
        $this->assertDatabaseHas('pendaftaran_pilihan', [
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periodeAktif->id,
        ]);

        // 4b. Memperbarui relasi pilihan kelas
        $payloadUpdate = [
            'pilihan' => [
                $this->paketB->id,
                $this->paketA->id,
                $this->paketC->id,
            ],
        ];

        $responseUpdate = $this->actingAs($this->siswa, 'siswa')
            ->from('/siswa/pilih-kelas')
            ->put('/siswa/pilih-kelas', $payloadUpdate);

        $responseUpdate->assertRedirect('/siswa/pilih-kelas');
        $pendaftaran = PendaftaranPilihan::where('siswa_id', $this->siswa->id)->first();
        $this->assertDatabaseHas('detail_pendaftaran_pilihan', [
            'pendaftaran_pilihan_id' => $pendaftaran->id,
            'paket_menu_pilihan_id' => $this->paketB->id,
            'urutan_pilihan' => 1,
        ]);
    }

    /**
     * 5. Hasil sementara: Sistem menampilkan hasil penilaian berdasarkan periode dan kelas yang dipilih.
     */
    public function test_tahap_5_hasil_sementara_displays_evaluation_by_period_and_class(): void
    {
        // Buat pendaftaran pilihan
        $pendaftaran = PendaftaranPilihan::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'periode_pendaftaran_id' => $this->periodeAktif->id,
            'tanggal_submit' => now(),
            'status' => 'menunggu',
        ]);

        $pendaftaran->detailPendaftaran()->create([
            'id' => (string) Str::uuid(),
            'paket_menu_pilihan_id' => $this->paketA->id,
            'urutan_pilihan' => 1,
        ]);

        // Buat hasil seleksi sementara
        HasilSeleksi::create([
            'id' => (string) Str::uuid(),
            'siswa_id' => $this->siswa->id,
            'paket_menu_pilihan_id' => $this->paketA->id,
            'pilihan_ke_diterima' => 1,
            'rank_pada_pilihan' => 3,
            'skor_penempatan' => 89.25,
            'rata_6_mapel' => 87.50,
            'mekanisme' => 'Pilihan 1',
            'tanggal_diproses' => now(),
        ]);

        // Mengambil hasil sementara berdasarkan periode dan kelas yang dipilih
        $response = $this->actingAs($this->siswa, 'siswa')
            ->getJson("/siswa/hasil-sementara?periode_id={$this->periodeAktif->id}&kelas_id={$this->paketA->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('periode.id', $this->periodeAktif->id)
            ->assertJsonPath('hasil_penilaian.skor_penempatan', 89.25)
            ->assertJsonPath('hasil_penilaian.rank_pada_pilihan', 3)
            ->assertJsonPath('hasil_penilaian.status', 'Lolos Sementara')
            ->assertJsonPath('data.0.paket_menu', 'Kelas MIPA 1')
            ->assertJsonPath('data.0.status', 'Lolos Sementara');
    }
}
