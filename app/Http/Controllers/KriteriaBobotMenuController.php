<?php

namespace App\Http\Controllers;

use App\Models\KriteriaBobotMenu;
use App\Models\MasterMataPelajaran;
use App\Models\PaketMenuPilihan;
use App\Models\PeriodePendaftaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KriteriaBobotMenuController extends Controller
{
    public function index(Request $request, PeriodePendaftaran $periode)
    {
        $this->ensureAdmin('bobot mata pelajaran');

        $validated = $request->validate(['paket_id' => 'nullable|uuid']);
        $pakets = $periode->paketMenuPilihans()->withSum('kriteriaBobots', 'bobot_persen')->orderBy('nama_menu')->get();
        $paket = isset($validated['paket_id']) ? $pakets->findOrFail($validated['paket_id']) : $pakets->first();
        $bobot = $paket?->kriteriaBobots()->pluck('bobot_persen', 'master_mata_pelajaran_id');
        $mapel = $paket ? MasterMataPelajaran::where('is_active', true)
            ->orWhereIn('id', $bobot->keys())->orderBy('nama_mapel')->get() : collect();

        return view('auth.period.bobot-mata-pelajaran', compact('periode', 'pakets', 'paket', 'bobot', 'mapel'));
    }

    public function savePackage(Request $request, PeriodePendaftaran $periode, string $paket)
    {
        $this->ensureAdmin('bobot mata pelajaran');

        DB::transaction(function () use ($request, $periode, $paket) {
            $package = $periode->paketMenuPilihans()->lockForUpdate()->findOrFail($paket);
            $existing = $package->kriteriaBobots()->pluck('bobot_persen', 'master_mata_pelajaran_id');
            $allowed = MasterMataPelajaran::where('is_active', true)->orWhereIn('id', $existing->keys())->pluck('id');
            $validated = $request->validate([
                'bobot' => ['required', 'array'],
                'bobot.*' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,100'],
            ], ['bobot.*' => 'Isi bobot antara 0 dan 100, maksimal dua angka desimal.']);

            if (array_diff(array_keys($validated['bobot']), $allowed->all())) {
                throw ValidationException::withMessages(['bobot' => 'Ada mata pelajaran yang tidak tersedia untuk paket ini.']);
            }

            foreach ($validated['bobot'] as $id => $percent) {
                if ($percent === null) {
                    if ($existing->has($id)) {
                        $package->kriteriaBobots()->where('master_mata_pelajaran_id', $id)->delete();
                    }
                } elseif (! $existing->has($id) || (float) $percent !== (float) $existing[$id]) {
                    $package->kriteriaBobots()->updateOrCreate(['master_mata_pelajaran_id' => $id], ['bobot_persen' => $percent]);
                }
            }
        });

        return redirect()->route('kriteria-bobot.index', ['periode' => $periode->id, 'paket_id' => $paket])
            ->with('success', 'Bobot paket berhasil disimpan.');
    }

    /**
     * Menentukan/menyimpan bobot mata pelajaran pada paket menu pilihan (Khusus Role Admin).
     * Mendukung simpan single item atau bulk array 'kriteria'.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated / Belum login.',
                ], 401);
            }
            abort(401, 'Unauthenticated / Belum login.');
        }

        if ($user->role !== 'admin') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Admin yang dapat menentukan kriteria bobot menu.',
                ], 403);
            }
            abort(403, 'Akses ditolak. Hanya Admin yang dapat menentukan kriteria bobot menu.');
        }

        // Jika request memiliki array 'kriteria'
        if ($request->has('kriteria') && is_array($request->kriteria)) {
            $validated = $request->validate([
                'paket_menu_pilihan_id' => ['required', 'uuid', Rule::exists('paket_menu_pilihan', 'id')->withoutTrashed()],
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

            return $this->handleWriteResponse($request, [
                'success' => true,
                'message' => 'Berhasil menyimpan kriteria bobot menu.',
                'total' => count($savedItems),
                'data' => $savedItems,
            ], 201);
        }

        // Jika single item request
        $validated = $request->validate([
            'paket_menu_pilihan_id' => ['required', 'uuid', Rule::exists('paket_menu_pilihan', 'id')->withoutTrashed()],
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

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil menyimpan kriteria bobot menu.',
            'data' => $bobot->load(['paketMenuPilihan', 'masterMataPelajaran']),
        ], 201);
    }

    /**
     * Memperbarui bobot persen kriteria (PUT /kriteria-bobot-menu/{identifier}).
     * Identifier dapat berupa paket_menu_pilihan_id atau ID kriteria bobot.
     * Payload dapat berupa array kriteria langsung [ { master_mata_pelajaran_id, bobot_persen }, ... ], { kriteria: [...] }, atau { bobot_persen }.
     *
     * @return JsonResponse
     */
    public function update(Request $request, string $identifier)
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated / Belum login.',
                ], 401);
            }
            abort(401, 'Unauthenticated / Belum login.');
        }

        if ($user->role !== 'admin') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Admin yang dapat mengubah kriteria bobot menu.',
                ], 403);
            }
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengubah kriteria bobot menu.');
        }

        // Cek apakah $identifier merupakan ID dari PaketMenuPilihan
        $paketMenu = PaketMenuPilihan::find($identifier);

        // Periksa apakah request body merupakan array list langsung [ {...}, {...} ] atau objek { "kriteria": [...] }
        $itemsToUpdate = null;
        $jsonContent = $request->json()->all();

        if (is_array($jsonContent) && array_is_list($jsonContent)) {
            $itemsToUpdate = $jsonContent;
        } elseif ($request->has('kriteria') && is_array($request->kriteria)) {
            $itemsToUpdate = $request->kriteria;
        }

        if ($itemsToUpdate !== null && count($itemsToUpdate) > 0) {
            $paketId = $paketMenu ? $paketMenu->id : $request->input('paket_menu_pilihan_id', $identifier);

            if (! PaketMenuPilihan::where('id', $paketId)->exists()) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Paket Menu Pilihan tidak ditemukan.',
                    ], 404);
                }
                abort(404, 'Paket Menu Pilihan tidak ditemukan.');
            }

            $request->merge(['kriteria' => $itemsToUpdate]);
            $validated = $request->validate([
                'kriteria' => ['required', 'array', 'min:1'],
                'kriteria.*.master_mata_pelajaran_id' => ['required', 'uuid', 'exists:master_mata_pelajaran,id'],
                'kriteria.*.bobot_persen' => ['required', 'numeric', 'min:0', 'max:100'],
            ], [
                'kriteria.*.master_mata_pelajaran_id.required' => 'Mata pelajaran wajib dipilih.',
                'kriteria.*.master_mata_pelajaran_id.exists' => 'Mata pelajaran tidak ditemukan.',
                'kriteria.*.bobot_persen.required' => 'Bobot persen wajib diisi.',
                'kriteria.*.bobot_persen.numeric' => 'Bobot persen harus berupa angka.',
            ]);

            $savedItems = [];
            foreach ($validated['kriteria'] as $item) {
                $bobot = KriteriaBobotMenu::updateOrCreate(
                    [
                        'paket_menu_pilihan_id' => $paketId,
                        'master_mata_pelajaran_id' => $item['master_mata_pelajaran_id'],
                    ],
                    [
                        'bobot_persen' => $item['bobot_persen'],
                    ]
                );
                $savedItems[] = $bobot->load(['paketMenuPilihan', 'masterMataPelajaran']);
            }

            return $this->handleWriteResponse($request, [
                'success' => true,
                'message' => 'Berhasil memperbarui kriteria bobot menu.',
                'total' => count($savedItems),
                'data' => $savedItems,
            ]);
        }

        // Jika single item request berdasarkan ID KriteriaBobotMenu
        $bobot = KriteriaBobotMenu::whereHas('paketMenuPilihan')->find($identifier);

        if (! $bobot) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kriteria bobot menu atau paket menu pilihan tidak ditemukan.',
                ], 404);
            }
            abort(404, 'Kriteria bobot menu atau paket menu pilihan tidak ditemukan.');
        }

        $validated = $request->validate([
            'bobot_persen' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'bobot_persen.required' => 'Bobot persen wajib diisi.',
            'bobot_persen.numeric' => 'Bobot persen harus berupa angka.',
            'bobot_persen.min' => 'Bobot persen minimal 0.',
            'bobot_persen.max' => 'Bobot persen maksimal 100.',
        ]);

        $bobot->update([
            'bobot_persen' => $validated['bobot_persen'],
        ]);

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil memperbarui kriteria bobot menu.',
            'data' => $bobot->load(['paketMenuPilihan', 'masterMataPelajaran']),
        ]);
    }

    /**
     * Menghapus kriteria bobot menu (Khusus Role Admin).
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id)
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated / Belum login.',
                ], 401);
            }
            abort(401, 'Unauthenticated / Belum login.');
        }

        if ($user->role !== 'admin') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Hanya Admin yang dapat menghapus kriteria bobot menu.',
                ], 403);
            }
            abort(403, 'Akses ditolak. Hanya Admin yang dapat menghapus kriteria bobot menu.');
        }

        $bobot = KriteriaBobotMenu::whereHas('paketMenuPilihan')->find($id);

        if (! $bobot) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kriteria bobot menu tidak ditemukan.',
                ], 404);
            }
            abort(404, 'Kriteria bobot menu tidak ditemukan.');
        }

        $bobot->delete();

        return $this->handleWriteResponse($request, [
            'success' => true,
            'message' => 'Berhasil menghapus kriteria bobot menu.',
        ]);
    }
}
