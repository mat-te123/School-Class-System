<?php

namespace Tests\Feature;

use App\Jobs\ProcessLegerImportJob;
use App\Models\Ketidakhadiran;
use App\Models\MasterMataPelajaran;
use App\Models\NilaiLegerSiswa;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LegerImportControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Sukses mengunggah file XLSX Leger secara Asynchronous (Queue Job).
     */
    public function test_async_leger_xlsx_upload_dispatches_queue_job(): void
    {
        Queue::fake();

        $samplePath = base_path('Leger_20242_X A.xlsx');
        $this->assertFileExists($samplePath);

        $uploadedFile = new UploadedFile(
            $samplePath,
            'Leger_20242_X A.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        // Act: POST file ke /leger/import
        $response = $this->postJson('/leger/import', [
            'file' => $uploadedFile,
        ]);

        // Assert: HTTP 202 Accepted & status queued
        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'status' => 'queued',
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
        $this->assertFileExists($samplePath);

        $uploadedFile = new UploadedFile(
            $samplePath,
            'Leger_20242_X A.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        // Act: POST file ke /leger/import?sync=1
        $response = $this->postJson('/leger/import?sync=1', [
            'file' => $uploadedFile,
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
        $response = $this->postJson('/leger/import', []);

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

        $response = $this->postJson('/leger/import', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi file gagal.',
            ])
            ->assertJsonStructure(['errors' => ['file']]);
    }
}
