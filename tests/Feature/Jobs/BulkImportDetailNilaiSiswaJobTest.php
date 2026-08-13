<?php

namespace Tests\Feature\Jobs;

use App\Models\MasterMataPelajaran;
use App\Models\Siswa;
use App\Models\NilaiLegerSiswa;
use App\Models\DetailNilaiSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkImportDetailNilaiSiswaJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_values_in_bulk_and_updates_averages()
    {
        // Given: Mapel dan siswa ada
        $mapel = MasterMataPelajaran::factory()->create();
        $siswa = Siswa::factory()->create(['nisn' => '0012345678']);

        $rows = [
            ['nisn' => '0012345678', 'nilai' => 90.5],
            ['nisn' => '0012345678', 'nilai' => 85.0],
        ];

        // When: Job dijalankan
        \App\Jobs\BulkImportDetailNilaiSiswaJob::dispatch(
            $mapel->id,
            '2024/2025',
            'Ganjil',
            $rows
        );

        // Then: Detail nilai tersimpan bulk, leger average terupdate
        $detailCount = DetailNilaiSiswa::where('master_mata_pelajaran_id', $mapel->id)->count();
        $this->assertTrue($detailCount > 0);

        $leger = NilaiLegerSiswa::where('siswa_id', $siswa->id)->first();
        $this->assertNotNull($leger);
        $this->assertEquals(85.0, $leger->rata_keseluruhan);
    }

    public function test_it_handles_missing_siswa_gracefully()
    {
        // Given: NISN tidak ditemukan di database
        $mapel = MasterMataPelajaran::factory()->create();

        $rows = [
            ['nisn' => 'INVALID_NISN', 'nilai' => 90.5],
        ];

        // When: Job dijalankan
        \App\Jobs\BulkImportDetailNilaiSiswaJob::dispatch(
            $mapel->id,
            '2024/2025',
            'Ganjil',
            $rows
        );

        // Then: Tidak ada exception, skipped record logged
        $this->assertDatabaseMissing('detail_nilai_siswa', [
            'master_mata_pelajaran_id' => $mapel->id,
        ]);
    }
}
