<?php

namespace App\Http\Controllers;

use App\Models\PaketMenuPilihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaketMenuPilihanController extends Controller
{
    /**
     * Mengambil daftar data Paket Menu Pilihan dengan filter opsional (rumpun, is_active, search).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = PaketMenuPilihan::query();

        // 1. Filter Rumpun (eksakta / sosial)
        if ($request->has('rumpun') && !empty($request->rumpun)) {
            $query->where('rumpun', strtolower($request->rumpun));
        }

        // 2. Filter status aktif (default true jika tidak ditentukan, atau bisa 'all')
        if ($request->has('is_active')) {
            if ($request->is_active !== 'all') {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }
        } else {
            // Default hanya mengambil menu yang aktif
            $query->where('is_active', true);
        }

        // 3. Pencarian berdasarkan nama menu atau kode menu
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_menu', 'like', '%' . $search . '%')
                  ->orWhere('kode_menu', $search);
            });
        }

        // 4. Urutkan berdasarkan kode_menu ascending
        $paketMenu = $query->orderBy('kode_menu', 'asc')->get();

        // 5. Transformasi data dengan menambahkan field sisa kuota
        $data = $paketMenu->map(function ($item) {
            return [
                'id' => $item->id,
                'kode_menu' => $item->kode_menu,
                'nama_menu' => $item->nama_menu,
                'rumpun' => $item->rumpun,
                'kuota_kapasitas' => $item->kuota_kapasitas,
                'kuota_terisi' => $item->kuota_terisi,
                'kuota_tersisa' => $item->kuota_tersisa,
                'is_active' => $item->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar Paket Menu Pilihan.',
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * Mengambil detail satu Paket Menu Pilihan berdasarkan ID (UUID) atau kode_menu.
     *
     * @param string $identifier (UUID id atau kode_menu)
     * @return JsonResponse
     */
    public function show(string $identifier): JsonResponse
    {
        $paketMenu = PaketMenuPilihan::where('id', $identifier)
            ->when(is_numeric($identifier), function ($q) use ($identifier) {
                $q->orWhere('kode_menu', (int) $identifier);
            })
            ->first();

        if (!$paketMenu) {
            return response()->json([
                'success' => false,
                'message' => 'Paket Menu Pilihan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil detail Paket Menu Pilihan.',
            'data' => [
                'id' => $paketMenu->id,
                'kode_menu' => $paketMenu->kode_menu,
                'nama_menu' => $paketMenu->nama_menu,
                'rumpun' => $paketMenu->rumpun,
                'kuota_kapasitas' => $paketMenu->kuota_kapasitas,
                'kuota_terisi' => $paketMenu->kuota_terisi,
                'kuota_tersisa' => $paketMenu->kuota_tersisa,
                'is_active' => $paketMenu->is_active,
            ],
        ]);
    }

    /**
     * Membuat Paket Menu Pilihan baru (Khusus Role Admin).
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
                'message' => 'Akses ditolak. Hanya Admin yang dapat menambah Paket Menu Pilihan.',
            ], 403);
        }

        $validated = $request->validate([
            'kode_menu' => ['required', 'integer', 'min:1', 'unique:paket_menu_pilihan,kode_menu'],
            'nama_menu' => ['required', 'string', 'max:50'],
            'rumpun' => ['required', 'string', 'in:eksakta,sosial'],
            'kuota_kapasitas' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'kode_menu.required' => 'Kode menu wajib diisi.',
            'kode_menu.integer' => 'Kode menu harus berupa angka.',
            'kode_menu.unique' => 'Kode menu sudah terdaftar.',
            'nama_menu.required' => 'Nama menu wajib diisi.',
            'rumpun.required' => 'Rumpun wajib diisi.',
            'rumpun.in' => 'Rumpun harus berupa eksakta atau sosial.',
            'kuota_kapasitas.integer' => 'Kuota kapasitas harus berupa angka.',
            'kuota_kapasitas.min' => 'Kuota kapasitas minimal 1.',
        ]);

        $paketMenu = PaketMenuPilihan::create([
            'kode_menu' => $validated['kode_menu'],
            'nama_menu' => $validated['nama_menu'],
            'rumpun' => strtolower($validated['rumpun']),
            'kuota_kapasitas' => $validated['kuota_kapasitas'] ?? 36,
            'kuota_terisi' => 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menambahkan Paket Menu Pilihan baru.',
            'data' => [
                'id' => $paketMenu->id,
                'kode_menu' => $paketMenu->kode_menu,
                'nama_menu' => $paketMenu->nama_menu,
                'rumpun' => $paketMenu->rumpun,
                'kuota_kapasitas' => $paketMenu->kuota_kapasitas,
                'kuota_terisi' => $paketMenu->kuota_terisi,
                'kuota_tersisa' => $paketMenu->kuota_tersisa,
                'is_active' => $paketMenu->is_active,
            ],
        ], 201);
    }

    /**
     * Memperbarui data Paket Menu Pilihan (Khusus Role Admin).
     *
     * @param Request $request
     * @param string $identifier (UUID id atau kode_menu)
     * @return JsonResponse
     */
    public function update(Request $request, string $identifier): JsonResponse
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
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengubah Paket Menu Pilihan.',
            ], 403);
        }

        $paketMenu = PaketMenuPilihan::where('id', $identifier)
            ->when(is_numeric($identifier), function ($q) use ($identifier) {
                $q->orWhere('kode_menu', (int) $identifier);
            })
            ->first();

        if (!$paketMenu) {
            return response()->json([
                'success' => false,
                'message' => 'Paket Menu Pilihan tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'kode_menu' => ['sometimes', 'required', 'integer', 'min:1', \Illuminate\Validation\Rule::unique('paket_menu_pilihan', 'kode_menu')->ignore($paketMenu->id)],
            'nama_menu' => ['sometimes', 'required', 'string', 'max:50'],
            'rumpun' => ['sometimes', 'required', 'string', 'in:eksakta,sosial'],
            'kuota_kapasitas' => ['sometimes', 'required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'kode_menu.required' => 'Kode menu tidak boleh kosong.',
            'kode_menu.integer' => 'Kode menu harus berupa angka.',
            'kode_menu.unique' => 'Kode menu sudah terdaftar.',
            'nama_menu.required' => 'Nama menu tidak boleh kosong.',
            'rumpun.in' => 'Rumpun harus berupa eksakta atau sosial.',
            'kuota_kapasitas.integer' => 'Kuota kapasitas harus berupa angka.',
            'kuota_kapasitas.min' => 'Kuota kapasitas minimal 1.',
        ]);

        if (isset($validated['rumpun'])) {
            $validated['rumpun'] = strtolower($validated['rumpun']);
        }

        $paketMenu->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memperbarui data Paket Menu Pilihan.',
            'data' => [
                'id' => $paketMenu->id,
                'kode_menu' => $paketMenu->kode_menu,
                'nama_menu' => $paketMenu->nama_menu,
                'rumpun' => $paketMenu->rumpun,
                'kuota_kapasitas' => $paketMenu->kuota_kapasitas,
                'kuota_terisi' => $paketMenu->kuota_terisi,
                'kuota_tersisa' => $paketMenu->kuota_tersisa,
                'is_active' => $paketMenu->is_active,
            ],
        ]);
    }

    /**
     * Menghapus Paket Menu Pilihan (Khusus Role Admin).
     *
     * @param Request $request
     * @param string $identifier (UUID id atau kode_menu)
     * @return JsonResponse
     */
    public function destroy(Request $request, string $identifier): JsonResponse
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
                'message' => 'Akses ditolak. Hanya Admin yang dapat menghapus Paket Menu Pilihan.',
            ], 403);
        }

        $paketMenu = PaketMenuPilihan::where('id', $identifier)
            ->when(is_numeric($identifier), function ($q) use ($identifier) {
                $q->orWhere('kode_menu', (int) $identifier);
            })
            ->first();

        if (!$paketMenu) {
            return response()->json([
                'success' => false,
                'message' => 'Paket Menu Pilihan tidak ditemukan.',
            ], 404);
        }

        if ($paketMenu->kuota_terisi > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Paket Menu Pilihan tidak dapat dihapus karena sudah memiliki ' . $paketMenu->kuota_terisi . ' kuota terisi / pendaftar.',
            ], 422);
        }

        $paketMenu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus Paket Menu Pilihan.',
        ]);
    }
}
