<?php

namespace App\Jobs;

use App\Models\DetailNilaiSiswa;
use App\Models\MasterMataPelajaran;
use App\Models\NilaiLegerSiswa;
use App\Models\Siswa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkImportDetailNilaiSiswaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $mapelId;
    private string $tahunAjaran;
    private string $semester;
    private array $rows;
    private int $imported = 0;
    private array $skipped = [];

    public function __construct(string $mapelId, string $tahunAjaran, string $semester, array $rows)
    {
        $this->mapelId = $mapelId;
        $this->tahunAjaran = $tahunAjaran;
        $this->semester = $semester;
        $this->rows = $rows;
    }

    public function handle(): void
    {
        $mapel = MasterMataPelajaran::findOrFail($this->mapelId);
        $nisnList = array_column($this->rows, 'nisn');
        
        $siswaMap = Siswa::whereIn('nisn', $nisnList)
            ->get()
            ->keyBy('nisn');

        // Cari semua leger yang sudah ada untuk siswa-siswa ini pada tahun & semester yang sama
        $existingLegers = NilaiLegerSiswa::whereIn('siswa_id', $siswaMap->pluck('id'))
            ->where('tahun_ajaran', $this->tahunAjaran)
            ->where('semester', $this->semester)
            ->get()
            ->keyBy('siswa_id');

        $legerData = [];
        $detailsToUpsert = [];
        
        DB::transaction(function () use ($siswaMap, $existingLegers, &$legerData, &$detailsToUpsert) {
            foreach ($this->rows as $row) {
                $siswa = $siswaMap->get($row['nisn']);
                
                if (!$siswa) {
                    $this->skipped[] = ['nisn' => $row['nisn'], 'reason' => 'Siswa tidak ditemukan'];
                    continue;
                }

                $existingLeger = $existingLegers->get($siswa->id);
                $key = "{$siswa->id}_{$this->tahunAjaran}_{$this->semester}";
                
                if ($existingLeger) {
                    $legerId = $existingLeger->id;
                } else {
                    if (!isset($legerData[$key])) {
                        $legerData[$key] = [
                            'id' => (string) Str::uuid(),
                            'siswa_id' => $siswa->id,
                            'tahun_ajaran' => $this->tahunAjaran,
                            'semester' => $this->semester,
                        ];
                    }
                    $legerId = $legerData[$key]['id'];
                }
                
                $detailsToUpsert[] = [
                    'id' => (string) Str::uuid(),
                    'nilai_leger_siswa_id' => $legerId,
                    'master_mata_pelajaran_id' => $this->mapelId,
                    'nilai_angka' => (float) $row['nilai'],
                    'predikat' => $this->calculatePredikat((float) $row['nilai']),
                ];
                
                $this->imported++;
            }

            // Bulk insert leger records
            if (!empty($legerData)) {
                NilaiLegerSiswa::insertOrIgnore(array_values($legerData));
            }

            // Bulk upsert details
            if (!empty($detailsToUpsert)) {
                DB::table('detail_nilai_siswa')->upsert(
                    $detailsToUpsert,
                    ['nilai_leger_siswa_id', 'master_mata_pelajaran_id'],
                    ['nilai_angka', 'predikat']
                );
            }

            // Dispatch recalculation for all affected leger IDs
            if (!empty($legerData)) {
                $legerIds = array_column(array_values($legerData), 'id');
                RecalculateLegerAverageJob::dispatch($legerIds);
            }
        });
    }

    private function calculatePredikat(float $nilai): string
    {
        if ($nilai >= 90) return 'A';
        if ($nilai >= 80) return 'B';
        if ($nilai >= 70) return 'C';
        return 'D';
    }
}
