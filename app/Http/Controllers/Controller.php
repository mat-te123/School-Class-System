<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Helper response untuk method POST/PUT/DELETE.
     * Mengembalikan JSON untuk AJAX/API, atau Redirect back dengan flash message untuk browser.
     */
    protected function handleWriteResponse(Request $request, array $responseData, int $status = 200)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($responseData, $status);
        }

        $type = $responseData['success'] ?? true ? 'success' : 'error';
        $message = $responseData['message'] ?? 'Proses berhasil.';

        return redirect()->back()->with($type, $message);
    }

    /**
     * Waktu jam server saat ini (real-time).
     * Dipakai client (frontend/mobile) untuk sinkronisasi jam dan countdown.
     */
    public static function serverClock(): JsonResponse
    {
        $now = Carbon::now();

        return response()->json([
            'success' => true,
            'message' => 'Waktu server berhasil diambil.',
            'data' => [
                'timestamp' => $now->timestamp,
                'timestamp_ms' => (int) round($now->valueOf()),
                'datetime' => $now->format('Y-m-d H:i:s'),
                'iso8601' => $now->toIso8601String(),
                'timezone' => $now->timezoneName,
                'offset_seconds' => $now->offset,
            ],
        ]);
    }

    /**
     * Pastikan request berasal dari admin (guard web).
     *
     * @param  string  $subject  Objek yang dikelola, dipakai di pesan penolakan.
     *                           Contoh: 'hasil penjurusan', 'data program studi'.
     */
    protected function ensureAdmin(string $subject = 'halaman ini'): void
    {
        $user = Auth::guard('web')->user();

        if ($user && $user->role === 'admin') {
            return;
        }

        $message = "Akses ditolak. Hanya Admin yang dapat mengelola {$subject}.";

        if (request()->wantsJson() || request()->ajax()) {
            abort(response()->json(['success' => false, 'message' => $message], 403));
        }

        abort(403, $message);
    }
}
