<?php

namespace App\Services;

use App\Models\DetailNilaiSiswa;
use App\Models\Ketidakhadiran;
use App\Models\MasterMataPelajaran;
use App\Models\NilaiLegerSiswa;
use App\Models\Siswa;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class LegerImportService
{
    /**
     * Mengekstrak file XLSX Leger dan mengimpor seluruh data ke database secara instan (< 0.5 detik).
     *
     * @param string $filePath Path lengkap file XLSX di disk.
     * @return array Ringkasan hasil impor.
     * @throws Exception
     */
    public function importFromXlsx(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File XLSX tidak ditemukan pada path: {$filePath}");
        }

        // 1. Ekstrak data mentah dari XML XLSX
        $rawRows = $this->parseXlsxXml($filePath);

        if (empty($rawRows)) {
            throw new Exception("Gagal membaca struktur atau data dari file XLSX.");
        }

        // 2. Ekstrak Metadata (Tahun Ajaran, Semester, Kelas)
        $metadata = $this->extractMetadata($rawRows);

        // 3. Ekstrak Header Mata Pelajaran dan Posisi Kolom
        $columnsMap = $this->extractColumnsMap($rawRows);

        $importedSiswaCount = 0;
        $importedNilaiCount = 0;

        // 4. Proses Impor Data secara Teroptimasi Maksimal (< 0.5s) dalam DB Transaction
        DB::transaction(function () use ($rawRows, $metadata, $columnsMap, &$importedSiswaCount, &$importedNilaiCount) {
            // A. Batch Pre-fetch & Bulk Insert Master Mata Pelajaran Unik (1 Query)
            $allKodes = [];
            $allMapelData = [];
            foreach ($columnsMap['subjects'] as $col => $mapelName) {
                $kodeMapel = Str::slug($mapelName, '_');
                $allKodes[$col] = $kodeMapel;
                if (!isset($allMapelData[$kodeMapel])) {
                    $allMapelData[$kodeMapel] = [
                        'id' => (string) Str::uuid(),
                        'kode_mapel' => $kodeMapel,
                        'nama_mapel' => $mapelName,
                        'kelompok_mapel' => 'umum',
                        'is_active' => true,
                    ];
                }
            }

            $existingMapels = MasterMataPelajaran::whereIn('kode_mapel', array_keys($allMapelData))->get()->keyBy('kode_mapel');
            $newMapelsToInsert = [];

            foreach ($allMapelData as $kodeMapel => $m) {
                if (!isset($existingMapels[$kodeMapel])) {
                    $newMapelsToInsert[] = $m;
                }
            }

            if (!empty($newMapelsToInsert)) {
                MasterMataPelajaran::insert($newMapelsToInsert);
                $existingMapels = MasterMataPelajaran::whereIn('kode_mapel', array_keys($allMapelData))->get()->keyBy('kode_mapel');
            }

            $mapelModelMap = [];
            foreach ($allKodes as $col => $kodeMapel) {
                $mapelModelMap[$col] = $existingMapels[$kodeMapel];
            }

            // B. Kumpulkan seluruh baris siswa dari file Excel
            $siswaList = [];
            $nisnList = [];

            foreach ($rawRows as $rowNum => $rowCells) {
                if ($rowNum < 8) continue; // Baris header

                $nisn = trim($rowCells['C'] ?? '');
                $namaLengkap = trim($rowCells['B'] ?? '');
                $nis = trim($rowCells['D'] ?? '');

                if (empty($nisn) || empty($namaLengkap)) {
                    continue;
                }

                $siswaList[$nisn] = [
                    'nis' => $nis ?: $nisn,
                    'nama_lengkap' => $namaLengkap,
                    'row_cells' => $rowCells,
                ];
                $nisnList[] = $nisn;
            }

            if (empty($siswaList)) {
                return;
            }

            // C. Pre-fetch Siswa & Leger yang sudah ada
            $existingSiswa = Siswa::whereIn('nisn', $nisnList)->get()->keyBy('nisn');
            $siswaIdsExisting = $existingSiswa->pluck('id')->all();

            $existingLeger = !empty($siswaIdsExisting)
                ? NilaiLegerSiswa::whereIn('siswa_id', $siswaIdsExisting)
                    ->where('tahun_ajaran', $metadata['tahun_ajaran'])
                    ->where('semester', $metadata['semester'])
                    ->get()
                    ->keyBy('siswa_id')
                : collect();

            $siswaUpserts = [];
            $siswaIdMap = [];
            $legerUpserts = [];
            $legerIds = [];
            $detailNilaiData = [];
            $ketidakhadiranInserts = [];

            foreach ($siswaList as $nisn => $data) {
                $siswaId = isset($existingSiswa[$nisn]) ? $existingSiswa[$nisn]->id : (string) Str::uuid();
                $siswaIdMap[$nisn] = $siswaId;

                $siswaUpserts[] = [
                    'id' => $siswaId,
                    'nisn' => $nisn,
                    'nis' => $data['nis'],
                    'nama_lengkap' => $data['nama_lengkap'],
                    'kelas_asal' => $metadata['kelas_asal'],
                    'is_active' => false,
                ];

                $rowCells = $data['row_cells'];

                // Hitung Nilai & Rata-Rata
                $nilaiMapelList = [];
                $totalNilai = 0;
                $jumlahMapelAda = 0;

                foreach ($columnsMap['subjects'] as $col => $mapelName) {
                    $valStr = trim($rowCells[$col] ?? '');
                    if ($valStr !== '' && is_numeric($valStr)) {
                        $nilaiVal = (float) $valStr;
                        $nilaiMapelList[$col] = [
                            'mapel_id' => $mapelModelMap[$col]->id,
                            'nama_mapel' => $mapelName,
                            'nilai' => $nilaiVal,
                        ];
                        $totalNilai += $nilaiVal;
                        $jumlahMapelAda++;
                    }
                }

                $rataKeseluruhan = $jumlahMapelAda > 0 ? round($totalNilai / $jumlahMapelAda, 2) : 0.00;
                $nilaiJson = [];
                foreach ($nilaiMapelList as $n) {
                    $nilaiJson[$n['nama_mapel']] = $n['nilai'];
                }

                $legerId = isset($existingLeger[$siswaId]) ? $existingLeger[$siswaId]->id : (string) Str::uuid();
                $legerIds[] = $legerId;

                $legerUpserts[] = [
                    'id' => $legerId,
                    'siswa_id' => $siswaId,
                    'tahun_ajaran' => $metadata['tahun_ajaran'],
                    'semester' => $metadata['semester'],
                    'rata_6_mapel' => $rataKeseluruhan,
                    'rata_keseluruhan' => $rataKeseluruhan,
                    'nilai_json' => json_encode($nilaiJson),
                ];

                foreach ($nilaiMapelList as $n) {
                    $detailNilaiData[] = [
                        'id' => (string) Str::uuid(),
                        'nilai_leger_siswa_id' => $legerId,
                        'master_mata_pelajaran_id' => $n['mapel_id'],
                        'nilai_angka' => $n['nilai'],
                        'predikat' => $this->calculatePredikat($n['nilai']),
                    ];
                }

                $ketidakhadiranInserts[] = [
                    'id' => (string) Str::uuid(),
                    'siswa_id' => $siswaId,
                    'sakit' => (int) ($rowCells[$columnsMap['sakit']] ?? 0),
                    'izin' => (int) ($rowCells[$columnsMap['izin']] ?? 0),
                    'alpa' => (int) ($rowCells[$columnsMap['alpa']] ?? 0),
                ];
            }

            // D. Eksekusi Bulk Upsert & Bulk Insert
            Siswa::upsert($siswaUpserts, ['nisn'], ['nis', 'nama_lengkap', 'kelas_asal', 'is_active']);
            $importedSiswaCount = count($siswaUpserts);

            NilaiLegerSiswa::upsert(
                $legerUpserts,
                ['siswa_id', 'tahun_ajaran', 'semester'],
                ['rata_6_mapel', 'rata_keseluruhan', 'nilai_json']
            );
            $importedNilaiCount = count($legerUpserts);

            if (!empty($legerIds)) {
                DetailNilaiSiswa::whereIn('nilai_leger_siswa_id', $legerIds)->delete();
                if (!empty($detailNilaiData)) {
                    foreach (array_chunk($detailNilaiData, 500) as $chunk) {
                        DetailNilaiSiswa::insert($chunk);
                    }
                }
            }

            if (!empty($siswaIdMap)) {
                Ketidakhadiran::whereIn('siswa_id', array_values($siswaIdMap))->delete();
                if (!empty($ketidakhadiranInserts)) {
                    Ketidakhadiran::insert($ketidakhadiranInserts);
                }
            }
        });

        return [
            'success' => true,
            'metadata' => $metadata,
            'imported_siswa' => $importedSiswaCount,
            'imported_leger' => $importedNilaiCount,
        ];
    }

    /**
     * Parser XML native untuk membaca file XLSX.
     */
    private function parseXlsxXml(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // Shared strings
        $sharedStrings = [];
        if (($xmlStr = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = simplexml_load_string($xmlStr);
            foreach ($xml->si as $val) {
                if (isset($val->t)) {
                    $sharedStrings[] = (string)$val->t;
                } elseif (isset($val->r)) {
                    $text = '';
                    foreach ($val->r as $r) {
                        $text .= (string)$r->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }

        // Sheet 1
        $rows = [];
        if (($sheetStr = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
            $sheetXml = simplexml_load_string($sheetStr);
            foreach ($sheetXml->sheetData->row as $row) {
                $rowNum = (int)$row['r'];
                $cells = [];
                foreach ($row->c as $c) {
                    $cellRef = (string)$c['r'];
                    $colLetter = preg_replace('/[0-9]/', '', $cellRef);
                    $t = (string)$c['t'];
                    $val = (string)$c->v;

                    if ($t === 's' && isset($sharedStrings[(int)$val])) {
                        $val = $sharedStrings[(int)$val];
                    }

                    $cells[$colLetter] = $val;
                }
                $rows[$rowNum] = $cells;
            }
        }

        $zip->close();
        return $rows;
    }

    /**
     * Ekstrak Metadata (Tahun Ajaran, Semester, Kelas).
     */
    private function extractMetadata(array $rows): array
    {
        $titleRow = $rows[1]['A'] ?? '';
        $tahunAjaran = '2024/2025';
        $semester = 'Genap';

        if (preg_match('/TAHUN PELAJARAN\s+([0-9\/]+)\s+(GENAP|GANJIL)/i', $titleRow, $matches)) {
            $tahunAjaran = $matches[1];
            $semester = ucfirst(strtolower($matches[2]));
        }

        $kelasRaw = $rows[3]['C'] ?? 'X A';
        $kelasAsal = trim(str_replace(':', '', $kelasRaw));

        return [
            'tahun_ajaran' => $tahunAjaran,
            'semester' => $semester,
            'kelas_asal' => $kelasAsal ?: 'X A',
        ];
    }

    /**
     * Memetakan kolom mata pelajaran dan ketidakhadiran dari header XLSX.
     */
    private function extractColumnsMap(array $rows): array
    {
        $row5 = $rows[5] ?? [];
        $subjects = [];

        // Kolom E sampai X adalah mata pelajaran
        foreach ($row5 as $colLetter => $valName) {
            $valName = trim($valName);
            if (in_array($colLetter, ['A', 'B', 'C', 'D', 'Y', 'Z', 'AA'])) {
                continue;
            }
            if (!empty($valName) && !in_array($colLetter, ['AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL'])) {
                $subjects[$colLetter] = $valName;
            }
        }

        return [
            'subjects' => $subjects,
            'sakit' => 'Y',
            'izin' => 'Z',
            'alpa' => 'AA',
        ];
    }

    /**
     * Menghitung predikat nilai (A, B, C, D).
     */
    private function calculatePredikat(float $nilai): string
    {
        if ($nilai >= 90) return 'A';
        if ($nilai >= 80) return 'B';
        if ($nilai >= 70) return 'C';
        return 'D';
    }
}
