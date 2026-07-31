<?php

namespace App\Http\Controllers;

use App\Models\KriteriaBobotMenu;
use App\Models\MasterMataPelajaran;
use App\Models\PaketMenuPilihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KriteriaBobotMenuController extends Controller
{
    /**
     * Mengambil daftar kriteria bobot menu (bisa difilter berdasarkan paket_menu_pilihan_id).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = KriteriaBobotMenu::with(['paketMenuPilihan', 'masterMataPelajaran']);

        if ($request->has('paket_menu_pilihan_id') && !empty($request->paket_menu_pilihan_id)) {
            $query->where('paket_menu_pilihan_id', $request->paket_menu_pilihan_id);
        }

        if ($request->has('master_mata_pelajaran_id') && !empty($request->master_mata_pelajaran_id)) {
            $query->where('master_mata_pelajaran_id', $request->master_mata_pelajaran_id);
        }

        $items = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar kriteria bobot menu.',
            'total' => $items->count(),
            'data' => $items,
        ]);
    }

    /**
     * Menentukan/menyimpan bobot mata pelajaran pada paket menu pilihan (Khusus Role Admin).
     * Mendukung simpan single item atau bulk array 'kriteria'.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = \Illuminate\Support\Facades\Auth::guard('web')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated / Belum login.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat menentukan kriteria bobot menu.',
            ], 403);
        }

        // Jika request memiliki array 'kriteria'
        if ($request->has('kriteria') && is_array($request->kriteria)) {
            $validated = $request->validate([
                'paket_menu_pilihan_id' => ['required', 'uuid', 'exists:paket_menu_pilihan,id'],
                'kriteria' => ['required', 'array', 'min:1'],
                'kriteria.*.master_mata_pelajaran_id' => ['required', 'uuid', 'exists:master_mata_pelajaran,id'],
                'kriteria.*.bobot_persen' => ['required', 'numeric', 'min:0', 'max:100'],
            ], [
                'paket_menu_pilihan_id.required' => 'Paket menu pilihan wajib dipilih.',
                'paket_menu_pilihan_id.exists' => 'Paket menu pilihan tidak ditemukan.',
                'kriteria.*.master_mata_pelajaran_id.required' => 'Mata pelajaran wajib dipilih.',
                'kriteria.*.master_mata_pelajaran_id.exists' => 'Mata pelajaran tidak ditemukan.',
                'kriteria.*.bobot_persen.required' => 'Bobot persen wajib diisi.',
                'kriteria.*.bobot_persen.numeric' => 'Bobot persen harus berupa angka.',
            ]);

            $savedItems = [];
            foreach ($validated['kriteria'] as $item) {
                $bobot = KriteriaBobotMenu::updateOrCreate(
                    [
                        'paket_menu_pilihan_id' => $validated['paket_menu_pilihan_id'],
                        'master_mata_pelajaran_id' => $item['master_mata_pelajaran_id'],
                    ],
                    [
                        'bobot_persen' => $item['bobot_persen'],
                    ]
                );
                $savedItems[] = $bobot->load(['paketMenuPilihan', 'masterMataPelajaran']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menyimpan kriteria bobot menu.',
                'total' => count($savedItems),
                'data' => $savedItems,
            ], 201);
        }

        // Jika single item request
        $validated = $request->validate([
            'paket_menu_pilihan_id' => ['required', 'uuid', 'exists:paket_menu_pilihan,id'],
            'master_mata_pelajaran_id' => ['required', 'uuid', 'exists:master_mata_pelajaran,id'],
            'bobot_persen' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'paket_menu_pilihan_id.required' => 'Paket menu pilihan wajib dipilih.',
            'paket_menu_pilihan_id.exists' => 'Paket menu pilihan tidak ditemukan.',
            'master_mata_pelajaran_id.required' => 'Mata pelajaran wajib dipilih.',
            'master_mata_pelajaran_id.exists' => 'Mata pelajaran tidak ditemukan.',
            'bobot_persen.required' => 'Bobot persen wajib diisi.',
            'bobot_persen.numeric' => 'Bobot persen harus berupa angka.',
        ]);

        $bobot = KriteriaBobotMenu::updateOrCreate(
            [
                'paket_menu_pilihan_id' => $validated['paket_menu_pilihan_id'],
                'master_mata_pelajaran_id' => $validated['master_mata_pelajaran_id'],
            ],
            [
                'bobot_persen' => $validated['bobot_persen'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menyimpan kriteria bobot menu.',
            'data' => $bobot->load(['paketMenuPilihan', 'masterMataPelajaran']),
        ], 201);
    }

    /**
     * Menghapus kriteria bobot menu (Khusus Role Admin).
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = \Illuminate\Support\Facades\Auth::guard('web')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated / Belum login.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat menghapus kriteria bobot menu.',
            ], 403);
        }

        $bobot = KriteriaBobotMenu::find($id);

        if (!$bobot) {
            return response()->json([
                'success' => false,
                'message' => 'Kriteria bobot menu tidak ditemukan.',
            ], 404);
        }

        $bobot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus kriteria bobot menu.',
        ]);
    }
}
