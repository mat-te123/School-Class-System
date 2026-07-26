<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessLegerImportJob;
use App\Services\LegerImportService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LegerImportController extends Controller
{
    protected LegerImportService $importService;

    public function __construct(LegerImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Mengimpor file XLSX Leger secara Asynchronous (Background Queue Job) atau Synchronous.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        // 1. Deteksi file yang diunggah
        $uploadedFile = $request->file('file')
            ?? $request->file('excel')
            ?? $request->file('leger')
            ?? collect($request->allFiles())->first();

        // 2. Jika tidak ada file
        if (!$uploadedFile) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi file gagal.',
                'errors' => [
                    'file' => ['File XLSX Leger wajib diunggah pada field "file".'],
                ],
            ], 422);
        }

        // 3. Validasi ekstensi
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        if (!in_array($extension, ['xlsx', 'xls'])) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi file gagal.',
                'errors' => [
                    'file' => ['Format file harus berupa .xlsx atau .xls'],
                ],
            ], 422);
        }

        try {
            // 4. Simpan file sementara di storage
            $fileName = 'leger_' . Str::uuid() . '.' . $extension;
            $savedPath = $uploadedFile->storeAs('leger_imports', $fileName);
            $fullPath = storage_path('app/' . $savedPath);

            // Jika file tersimpan di storage/app/private
            if (!file_exists($fullPath) && file_exists(storage_path('app/private/' . $savedPath))) {
                $fullPath = storage_path('app/private/' . $savedPath);
            }

            // Opsi: Jika meminta respon langsung / sync (misal ?sync=1 atau ?async=0)
            if ($request->boolean('sync', false)) {
                $result = $this->importService->importFromXlsx($fullPath);
                @unlink($fullPath);

                return response()->json([
                    'success' => true,
                    'message' => 'File XLSX Leger berhasil diimpor ke database secara langsung (sync).',
                    'summary' => $result,
                ]);
            }

            // 5. Asynchronous Mode (Background Queue Job) - Respon Instan (< 50ms)
            ProcessLegerImportJob::dispatch($fullPath);

            return response()->json([
                'success' => true,
                'message' => 'File XLSX Leger berhasil diterima dan sedang diproses di background queue (Asynchronous).',
                'status' => 'queued',
                'file_name' => $fileName,
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah file XLSX: ' . $e->getMessage(),
            ], 500);
        }
    }
}
