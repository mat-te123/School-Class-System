<?php

namespace Tests\Feature;

use App\Models\LaporanPesan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanPesanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_report(): void
    {
        $payload = [
            'nisn' => '1234567890',
            'nama' => 'Guest Siswa',
            'kelas' => 'X IPA 1',
            'judul' => 'Kendala Login',
            'kategori' => 'bug',
            'pesan' => 'Tidak bisa login dengan NISN.',
        ];

        $response = $this->postJson('/laporan-pesan', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Laporan pesan berhasil dikirim')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('laporan_pesan', [
            'nisn' => '1234567890',
            'judul' => 'Kendala Login',
        ]);
    }

    public function test_admin_can_view_all_reports_and_update_status(): void
    {
        $admin = User::factory()->create();
        $report = LaporanPesan::create([
            'judul' => 'Saran Fitur',
            'pesan' => 'Tambahkan tombol export.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->getJson('/laporan-pesan');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');

        $this->assertEquals(1, $response->json('data.total'));

        $updateResponse = $this->actingAs($admin, 'web')
            ->putJson("/laporan-pesan/{$report->id}/status", [
                'status' => 'diproses',
                'catatan_penanganan' => 'Sedang dicek oleh dev.',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'diproses')
            ->assertJsonPath('data.ditangani_oleh', $admin->id);
    }

    /** Request browser non-JSON redirect setelah store */
    public function test_browser_store_redirects_back_with_success_flash(): void
    {
        $response = $this->from('/laporan-pesan')
            ->post('/laporan-pesan', [
                'nisn' => '1234567890',
                'nama' => 'Guest Siswa',
                'kelas' => 'X IPA 1',
                'judul' => 'Kendala Login',
                'kategori' => 'bug',
                'pesan' => 'Tidak bisa login dengan NISN.',
            ]);

        $response->assertRedirect('/laporan-pesan');
        $response->assertSessionHas('success', 'Laporan pesan berhasil dikirim');
    }

    /** Request browser non-JSON redirect setelah updateStatus */
    public function test_browser_update_status_redirects_back_with_success_flash(): void
    {
        $admin = User::factory()->create();
        $report = LaporanPesan::create([
            'judul' => 'Saran Fitur',
            'pesan' => 'Tambahkan tombol export.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->from('/laporan-pesan')
            ->put("/laporan-pesan/{$report->id}/status", [
                'status' => 'diproses',
                'catatan_penanganan' => 'Sedang dicek oleh dev.',
            ]);

        $response->assertRedirect('/laporan-pesan');
        $response->assertSessionHas('success', 'Status laporan pesan berhasil diperbarui');
    }

    /** Request browser non-JSON redirect setelah destroy */
    public function test_browser_destroy_redirects_back_with_success_flash(): void
    {
        $admin = User::factory()->create();
        $report = LaporanPesan::create([
            'judul' => 'Laporan Palsu',
            'pesan' => 'Dihapus saja.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->from('/laporan-pesan')
            ->delete("/laporan-pesan/{$report->id}");

        $response->assertRedirect('/laporan-pesan');
        $response->assertSessionHas('success', 'Laporan pesan berhasil dihapus');
        $this->assertDatabaseMissing('laporan_pesan', ['id' => $report->id]);
    }
}
