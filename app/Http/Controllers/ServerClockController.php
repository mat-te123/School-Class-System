<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ServerClockController extends Controller
{
    /**
     * Mengambil waktu jam server saat ini (real-time).
     * Dapat digunakan oleh client (frontend / mobile) untuk sinkronisasi jam dan countdown.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
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
}
