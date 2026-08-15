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
            ->assertJsonCount(1, 'data');

        $updateResponse = $this->actingAs($admin, 'web')
            ->putJson("/laporan-pesan/{$report->id}/status", [
                'status' => 'diproses',
                'catatan_penanganan' => 'Sedang dicek oleh dev.',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'diproses')
            ->assertJsonPath('data.ditangani_oleh', $admin->id);
    }
}
