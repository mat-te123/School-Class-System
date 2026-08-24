<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Helper response untuk method POST/PUT/DELETE.
     * Mengembalikan JSON untuk AJAX/API, atau Redirect back dengan flash message untuk browser.
     */
    protected function handleWriteResponse(\Illuminate\Http\Request $request, array $responseData, int $status = 200)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($responseData, $status);
        }

        $type = $responseData['success'] ?? true ? 'success' : 'error';
        $message = $responseData['message'] ?? 'Proses berhasil.';

        return redirect()->back()->with($type, $message);
    }
}
