<?php

namespace App\Jobs;

use App\Services\LegerImportService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLegerImportJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;
    protected ?string $uploadedBy;
    protected ?string $kelasAsalId;
    protected ?string $angkatan;

    /**
     * Create a new job instance.
     *
     * @param string $filePath Path file XLSX yang tersimpan sementara di storage.
     * @param string|null $uploadedBy ID user pengunggah dari session.
     * @param string|null $kelasAsalId ID kelas asal dari form request.
     * @param string|null $angkatan Angkatan dari form request (misal: 2024/2025).
     */
    public function __construct(string $filePath, ?string $uploadedBy = null, ?string $kelasAsalId = null, ?string $angkatan = null)
    {
        $this->filePath    = $filePath;
        $this->uploadedBy  = $uploadedBy;
        $this->kelasAsalId = $kelasAsalId;
        $this->angkatan    = $angkatan;
    }

    /**
     * Execute the job.
     */
    public function handle(LegerImportService $importService): void
    {
        Log::info("Memulai background job pengimporan Leger XLSX: {$this->filePath}");

        try {
            $result = $importService->importFromXlsx($this->filePath, $this->uploadedBy, $this->kelasAsalId, $this->angkatan);

            Log::info("Berhasil mengimpor Leger XLSX via background job.", $result);
        } catch (Exception $e) {
            Log::error("Gagal mengimpor Leger XLSX via background job: " . $e->getMessage(), [
                'file' => $this->filePath,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
