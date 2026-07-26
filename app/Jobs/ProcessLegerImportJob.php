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

    /**
     * Create a new job instance.
     *
     * @param string $filePath Path file XLSX yang tersimpan sementara di storage.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(LegerImportService $importService): void
    {
        Log::info("Memulai background job pengimporan Leger XLSX: {$this->filePath}");

        try {
            $result = $importService->importFromXlsx($this->filePath);

            Log::info("Berhasil mengimpor Leger XLSX via background job.", $result);
        } catch (Exception $e) {
            Log::error("Gagal mengimpor Leger XLSX via background job: " . $e->getMessage(), [
                'file' => $this->filePath,
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            // Hapus file sementara setelah pemrosesan selesai
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }
        }
    }
}
