<?php

namespace Tests\Feature;

use App\Jobs\ProcessLegerImportJob;
use App\Models\Ketidakhadiran;
use App\Models\MasterMataPelajaran;
use App\Models\NilaiLegerSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegerImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'id'        => (string) Str::uuid(),
            'username'  => 'admin_leger',
            'password'  => 'password123',
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * Test 1: Sukses mengunggah file XLSX Leger secara Asynchronous (Queue Job).
     */
    public function test_async_leger_xlsx_upload_dispatches_queue_job(): void
    {
        Queue::fake();

        $kelas = \App\Models\KelasAsal::create([
            'id'        => (string) Str::uuid(),
            'nama_kelas'=> 'X A',
            'tingkat'   => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $samplePath = base_path('Leger_20242_X A.xlsx');
        if (file_exists($samplePath)) {
            $uploadedFile = new UploadedFile(
                $samplePath,
                'Leger_20242_X A.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );
        } else {
            $uploadedFile = UploadedFile::fake()->create('Leger_20242_X A.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }

        // Act: POST file ke /leger/import sebagai admin (wajib kelas_asal_id + angkatan)
        $response = $this->actingAs($this->admin, 'web')->postJson('/leger/import', [
            'file'          => $uploadedFile,
            'kelas_asal_id' => $kelas->id,
            'angkatan'      => '2024/2025',
        ]);

        // Assert: HTTP 202 Accepted & status queued
        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'status'  => 'queued',
            ]);

        // Assert: Job ProcessLegerImportJob berhasil masuk ke antrean queue
        Queue::assertPushed(ProcessLegerImportJob::class);
    }

    /**
     * Test 2: Sukses mengunggah file XLSX Leger secara Synchronous (?sync=1) dan pemetaan ke database.
     */
    public function test_sync_leger_xlsx_upload_and_database_import(): void
    {
        $samplePath = base_path('Leger_20242_X A.xlsx');
        if (!file_exists($samplePath)) {
            $this->markTestSkipped('Sample XLSX file Leger_20242_X A.xlsx not present for sync import test.');
        }

        $uploadedFile = new UploadedFile(
            $samplePath,
            'Leger_20242_X A.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        // Act: POST file ke /leger/import?sync=1 sebagai admin (wajib kelas_asal_id + angkatan)
        $kelas = \App\Models\KelasAsal::create([
            'id'        => (string) Str::uuid(),
            'nama_kelas'=> 'X A',
            'tingkat'   => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'web')->postJson('/leger/import?sync=1', [
            'file'          => $uploadedFile,
            'kelas_asal_id' => $kelas->id,
            'angkatan'      => '2024/2025',
        ]);

        // Assert: Respon JSON HTTP 200 OK
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'File XLSX Leger berhasil diimpor ke database secara langsung (sync).',
            ]);

        $importedSiswaCount = $response->json('summary.imported_siswa');
        $importedLegerCount = $response->json('summary.imported_leger');

        $this->assertGreaterThan(0, $importedSiswaCount);
        $this->assertGreaterThan(0, $importedLegerCount);

        // Assert: Seluruh data terimpor ke database
        $this->assertEquals($importedSiswaCount, Siswa::count());
        $this->assertEquals($importedLegerCount, NilaiLegerSiswa::count());
        $this->assertEquals($importedSiswaCount, Ketidakhadiran::count());
        $this->assertGreaterThan(0, MasterMataPelajaran::count());
    }

    /**
     * Test 3: Gagal jika tidak ada file yang diunggah.
     */
    public function test_import_fails_without_file(): void
    {
        $response = $this->actingAs($this->admin, 'web')->postJson('/leger/import', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi file gagal.',
            ])
            ->assertJsonStructure(['errors' => ['file']]);
    }

    /**
     * Test 4: Gagal jika format file bukan .xlsx atau .xls (misal .pdf).
     */
    public function test_import_fails_with_invalid_file_extension(): void
    {
        $file = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin, 'web')->postJson('/leger/import', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi file gagal.',
            ])
            ->assertJsonStructure(['errors' => ['file']]);
    }

    public function test_import_with_explicit_kelas_asal_id(): void
    {
        Queue::fake();

        $kelas = \App\Models\KelasAsal::create([
            'id'         => (string) Str::uuid(),
            'nama_kelas' => 'X B Khusus',
            'tingkat'    => 'X',
            'kapasitas'  => 36,
            'is_active'  => true,
        ]);

        $samplePath = base_path('Leger_20242_X A.xlsx');
        if (file_exists($samplePath)) {
            $uploadedFile = new UploadedFile(
                $samplePath,
                'Leger_Custom.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );
        } else {
            $uploadedFile = UploadedFile::fake()->create('Leger_Custom.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }

        $response = $this->actingAs($this->admin, 'web')->postJson('/leger/import', [
            'file'          => $uploadedFile,
            'kelas_asal_id' => $kelas->id,
            'angkatan'      => '2024/2025',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'success'  => true,
                'status'   => 'queued',
                'kelas'    => 'X B Khusus',
                'angkatan' => '2024/2025',
            ]);

        Queue::assertPushed(ProcessLegerImportJob::class);
    }

    /**
     * Test: Gagal jika angkatan tidak diisi.
     */
    public function test_import_fails_without_angkatan(): void
    {
        $kelas = \App\Models\KelasAsal::create([
            'id'        => (string) Str::uuid(),
            'nama_kelas'=> 'X C',
            'tingkat'   => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->create('Leger.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->admin, 'web')->postJson('/leger/import', [
            'file'          => $file,
            'kelas_asal_id' => $kelas->id,
            // angkatan tidak diisi
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Validasi gagal.'])
            ->assertJsonStructure(['errors' => ['angkatan']]);
    }

    /**
     * Test: Gagal jika kelas_asal_id tidak diisi.
     */
    public function test_import_fails_without_kelas_asal_id(): void
    {
        $file = UploadedFile::fake()->create('Leger.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->admin, 'web')->postJson('/leger/import', [
            'file'     => $file,
            'angkatan' => '2024/2025',
            // kelas_asal_id tidak diisi
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Validasi gagal.'])
            ->assertJsonStructure(['errors' => ['kelas_asal_id']]);
    }

    /**
     * Test 5: Tolak upload jika nama_kelas + angkatan sudah ada di database (HTTP 409).
     */
    public function test_import_rejected_when_kelas_and_angkatan_already_exist(): void
    {
        Queue::fake();

        $kelas = \App\Models\KelasAsal::create([
            'id'        => (string) Str::uuid(),
            'nama_kelas'=> 'X A',
            'tingkat'   => 'X',
            'kapasitas' => 36,
            'is_active' => true,
        ]);

        // Buat riwayat upload yang sudah selesai untuk kelas + angkatan yang sama
        \App\Models\RiwayatUploadLeger::create([
            'id'            => (string) Str::uuid(),
            'kelas_asal_id' => $kelas->id,
            'nama_kelas'    => 'X A',
            'angkatan'      => '2024/2025',
            'file_name'     => 'Leger_lama.xlsx',
            'file_path'     => '/tmp/Leger_lama.xlsx',
            'jumlah_siswa'  => 30,
            'status'        => 'completed',
        ]);

        $file = UploadedFile::fake()->create('Leger_baru.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->admin, 'web')->postJson('/leger/import', [
            'file'          => $file,
            'kelas_asal_id' => $kelas->id,
            'angkatan'      => '2024/2025',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment(['message' => "File Leger untuk Kelas 'X A' Angkatan '2024/2025' sudah pernah diunggah (Leger_lama.xlsx). Satu kelas dan angkatan hanya diizinkan 1 file Excel."]);

        Queue::assertNotPushed(ProcessLegerImportJob::class);
    }

    /**
     * Test 6: Dapat mengunduh file XLSX Leger yang tersimpan di server.
     */
    public function test_can_download_uploaded_leger_file(): void
    {
        $filename = 'test_download_sample.xlsx';
        $path = storage_path('app/public/leger_imports/' . $filename);
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, 'dummy excel content');

        $response = $this->actingAs($this->admin, 'web')->get('/leger/download/' . $filename);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        @unlink($path);
    }
}
