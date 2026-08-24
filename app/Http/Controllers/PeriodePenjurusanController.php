<?php

namespace App\Http\Controllers;

use App\Models\PeriodePendaftaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodePenjurusanController extends Controller
{
    public function index(): View
    {
        $periode = PeriodePendaftaran::all();

        return view('auth.period.index', compact('periode'));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => PeriodePendaftaran::findOrFail($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate($this->rules());
        $periode = PeriodePendaftaran::create([
            ...$validated,
            'gelombang' => $validated['gelombang'] ?? 'Utama',
            'max_pilihan_siswa' => $validated['max_pilihan_siswa'] ?? 3,
            'status_pengumuman' => $validated['status_pengumuman'] ?? 'NON-AKTIF',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['success' => true, 'message' => 'Periode penjurusan berhasil dibuat.', 'data' => $periode], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureAdmin();
        $periode = PeriodePendaftaran::findOrFail($id);
        $validated = $request->validate($this->rules($periode));
        $periode->update($validated);

        return response()->json(['success' => true, 'message' => 'Periode penjurusan berhasil diperbarui.', 'data' => $periode->fresh()]);
    }

    private function ensureAdmin(): void
    {
        if (Auth::guard('web')->user()->role !== 'admin') {
            abort(403, 'Akses ditolak. Hanya Admin yang dapat mengelola periode penjurusan.');
        }
    }

    private function rules(?PeriodePendaftaran $periode = null): array
    {
        $tanggalBuka = request('tanggal_buka', $periode?->tanggal_buka);

        return [
            'nama_periode' => [$periode ? 'sometimes' : 'required', 'string', 'max:100'],
            'tahun_ajaran' => [$periode ? 'sometimes' : 'required', 'string', 'max:10'],
            'gelombang' => ['nullable', 'string', 'max:20'],
            'max_pilihan_siswa' => ['nullable', 'integer', 'min:1'],
            'tanggal_buka' => [$periode ? 'sometimes' : 'required', 'date'],
            'tanggal_tutup' => [$periode ? 'sometimes' : 'required', 'date', Rule::when($tanggalBuka, 'after:'.$tanggalBuka)],
            'tanggal_mulai_pertukaran' => ['nullable', 'date'],
            'tanggal_selesai_pertukaran' => [
                'nullable',
                'date',
                Rule::when(request('tanggal_mulai_pertukaran', $periode?->tanggal_mulai_pertukaran), 'after:'.(request('tanggal_mulai_pertukaran', $periode?->tanggal_mulai_pertukaran) ?? '')),
            ],
            'status_pengumuman' => ['nullable', 'in:AKTIF,NON-AKTIF'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
