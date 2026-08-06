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
        // Hanya admin yang boleh upload file Leger Excel
        $user = \Illuminate\Support\Facades\Auth::guard('web')->user();
        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin yang dapat mengunggah file Leger Excel.',
            ], 403);
        }

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

        // 4. Validasi kelas_asal_id dan angkatan wajib diisi dari form data
        $kelasAsalId = $request->input('kelas_asal_id') ?? $request->input('kelas_id');
        $angkatan    = $request->input('angkatan');

        $validationErrors = [];

        if (empty($kelasAsalId)) {
            $validationErrors['kelas_asal_id'] = ['Field kelas_asal_id wajib diisi.'];
        } else {
            $kelasModel = \App\Models\KelasAsal::where('id', $kelasAsalId)
                ->orWhere('nama_kelas', $kelasAsalId)
                ->first();
            if (!$kelasModel) {
                $validationErrors['kelas_asal_id'] = ['Kelas asal dengan ID atau nama tersebut tidak ditemukan.'];
            }
        }

        if (empty($angkatan)) {
            $validationErrors['angkatan'] = ['Field angkatan wajib diisi. Contoh: 2024/2025'];
        }

        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validationErrors,
            ], 422);
        }

        // Ambil nama kelas dari model yang sudah ditemukan
        $kelasNama = $kelasModel->nama_kelas;

        // 5. Cek duplikat: kelas_asal_id + angkatan sudah ada di riwayat_upload_leger
        $duplicate = \App\Models\RiwayatUploadLeger::where('kelas_asal_id', $kelasModel->id)
            ->where('angkatan', $angkatan)
            ->where('status', 'completed')
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => "File Leger untuk Kelas '{$kelasNama}' Angkatan '{$angkatan}' sudah pernah diunggah ({$duplicate->file_name}). Satu kelas dan angkatan hanya diizinkan 1 file Excel.",
            ], 409);
        }

        try {
            // 6. Simpan file sementara di storage
            $fileName  = 'leger_' . Str::uuid() . '.' . $extension;
            $savedPath = $uploadedFile->storeAs('leger_imports', $fileName);
            $fullPath  = storage_path('app/' . $savedPath);

            // Jika file tersimpan di storage/app/private
            if (!file_exists($fullPath) && file_exists(storage_path('app/private/' . $savedPath))) {
                $fullPath = storage_path('app/private/' . $savedPath);
            }

            $userId = \Illuminate\Support\Facades\Auth::id() ?? $request->user()?->id;

            // 7. Opsi: Jika meminta respon langsung / sync
            if ($request->boolean('sync', false)) {
                $result = $this->importService->importFromXlsx($fullPath, $userId, $kelasAsalId, $angkatan);

                return response()->json([
                    'success' => true,
                    'message' => 'File XLSX Leger berhasil diimpor ke database secara langsung (sync).',
                    'file_url' => url('/leger/download/' . $fileName),
                    'summary' => $result,
                ]);
            }

            // 8. Asynchronous Mode (Background Queue Job) - Respon Instan (< 50ms)
            ProcessLegerImportJob::dispatch($fullPath, $userId, $kelasAsalId, $angkatan);

            return response()->json([
                'success'   => true,
                'message'   => 'File XLSX Leger berhasil diterima dan sedang diproses di background queue (Asynchronous).',
                'status'    => 'queued',
                'file_name' => $fileName,
                'file_url'  => url('/leger/download/' . $fileName),
                'kelas'     => $kelasNama,
                'angkatan'  => $angkatan,
            ], 202);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah file XLSX: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengunduh file XLSX Leger berdasarkan nama file.
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function download(string $filename)
    {
        $filename = basename($filename);

        $possiblePaths = [
            storage_path('app/public/leger_imports/' . $filename),
            storage_path('app/private/leger_imports/' . $filename),
            storage_path('app/leger_imports/' . $filename),
            storage_path('app/' . $filename),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return response()->download($path, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'File XLSX Leger tidak ditemukan pada server.',
        ], 404);
    }

    /**
     * Mengambil riwayat dan tracking unggah file Leger Excel berdasarkan kelas dan angkatan.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $query = \App\Models\RiwayatUploadLeger::with(['kelasAsal', 'uploader']);

        if ($request->has('nama_kelas') && !empty($request->nama_kelas)) {
            $query->where('nama_kelas', $request->nama_kelas);
        }

        if ($request->has('angkatan') && !empty($request->angkatan)) {
            $query->where('angkatan', $request->angkatan);
        }

        $history = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil riwayat unggah file Leger Excel.',
            'total' => $history->count(),
            'data' => $history,
        ]);
    }
}
