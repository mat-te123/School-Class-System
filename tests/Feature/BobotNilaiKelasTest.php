<?php

namespace Tests\Feature;

use App\Models\BobotNilaiKelas;
use App\Models\KelasAsal;
use App\Models\MasterMataPelajaran;
use Database\Seeders\BobotNilaiKelasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BobotNilaiKelasTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_weights_for_each_class_and_sums_to_100(): void
    {
        $kelas1 = KelasAsal::create(['nama_kelas' => 'X A', 'tingkat' => 'X']);
        $kelas2 = KelasAsal::create(['nama_kelas' => 'X B', 'tingkat' => 'X']);

        $mapel1 = MasterMataPelajaran::create(['kode_mapel' => 'MAT', 'nama_mapel' => 'Matematika', 'is_active' => true]);
        $mapel2 = MasterMataPelajaran::create(['kode_mapel' => 'BIO', 'nama_mapel' => 'Biologi', 'is_active' => true]);
        $mapel3 = MasterMataPelajaran::create(['kode_mapel' => 'FIS', 'nama_mapel' => 'Fisika', 'is_active' => true]);

        $this->seed(BobotNilaiKelasSeeder::class);

        $this->assertDatabaseCount('bobot_nilai_kelas', 6);

        foreach ([$kelas1, $kelas2] as $kelas) {
            $weights = BobotNilaiKelas::where('kelas_asal_id', $kelas->id)->get();
            $this->assertCount(3, $weights);
            $total = $weights->sum('bobot_persen');
            $this->assertEqualsWithDelta(100.00, $total, 0.01);
        }

        // Test relation
        $firstRecord = BobotNilaiKelas::first();
        $this->assertNotNull($firstRecord->kelasAsal);
        $this->assertNotNull($firstRecord->masterMataPelajaran);
        $this->assertNotNull($firstRecord->mataPelajaran);

        // Test idempotency
        $this->seed(BobotNilaiKelasSeeder::class);
        $this->assertDatabaseCount('bobot_nilai_kelas', 6);
    }
}
