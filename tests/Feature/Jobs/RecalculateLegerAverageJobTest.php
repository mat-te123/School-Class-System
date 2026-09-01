<?php

namespace Tests\Feature\Jobs;

use App\Models\DetailNilaiSiswa;
use App\Models\NilaiLegerSiswa;
use App\Models\Siswa;
use App\Models\MasterMataPelajaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateLegerAverageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_recalculates_average_for_multiple_leger_records()
    {
        // Given: Multiple leger records with existing detail values
        $siswa1 = Siswa::factory()->create();
        $siswa2 = Siswa::factory()->create();

        $mapel1 = MasterMataPelajaran::factory()->create(['nama_mapel' => 'B.IND']);
        $mapel2 = MasterMataPelajaran::factory()->create(['nama_mapel' => 'PJOK']);
        $mapel3 = MasterMataPelajaran::factory()->create(['nama_mapel' => 'MTK-U']);

        $leger1 = NilaiLegerSiswa::factory()->create([
            'siswa_id' => $siswa1->id,
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Ganjil',
        ]);

        $leger2 = NilaiLegerSiswa::factory()->create([
            'siswa_id' => $siswa2->id,
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Ganjil',
        ]);

        DetailNilaiSiswa::create([
            'nilai_leger_siswa_id' => $leger1->id,
            'master_mata_pelajaran_id' => $mapel1->id,
            'nilai_angka' => 90,
            'predikat' => 'A',
        ]);

        DetailNilaiSiswa::create([
            'nilai_leger_siswa_id' => $leger1->id,
            'master_mata_pelajaran_id' => $mapel2->id,
            'nilai_angka' => 80,
            'predikat' => 'B',
        ]);

        DetailNilaiSiswa::create([
            'nilai_leger_siswa_id' => $leger2->id,
            'master_mata_pelajaran_id' => $mapel1->id,
            'nilai_angka' => 85,
            'predikat' => 'B',
        ]);

        DetailNilaiSiswa::create([
            'nilai_leger_siswa_id' => $leger2->id,
            'master_mata_pelajaran_id' => $mapel2->id,
            'nilai_angka' => 75,
            'predikat' => 'C',
        ]);

        $legerIds = [$leger1->id, $leger2->id];

        // When: Job dijalankan
        \App\Jobs\RecalculateLegerAverageJob::dispatch($legerIds);

        // Then: Average dihitung ulang secara batch
        $freshLeger1 = NilaiLegerSiswa::find($leger1->id);
        // rata_keseluruhan = (90 + 80) / 2 = 85
        $this->assertEquals(85.0, $freshLeger1->rata_keseluruhan);
        // rata_6_mapel hanya mapel utama (B.IND = 90)
        $this->assertEquals(90.0, $freshLeger1->rata_6_mapel);

        $freshLeger2 = NilaiLegerSiswa::find($leger2->id);
        // rata_keseluruhan = (85 + 75) / 2 = 80
        $this->assertEquals(80.0, $freshLeger2->rata_keseluruhan);
        // rata_6_mapel hanya mapel utama (B.IND = 85)
        $this->assertEquals(85.0, $freshLeger2->rata_6_mapel);
    }
}
