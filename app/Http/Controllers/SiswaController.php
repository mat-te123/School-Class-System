<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SiswaController extends Controller
{
    /**
     * Tampilkan form login siswa (jika menggunakan Blade Web UI).
     */
    public function showLoginForm()
    {
        return view('siswa.login');
    }

    /**
     * Proses Login Siswa via NISN dan Password.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function login(Request $request)
    {
        // 1. Validasi Input NISN dan Password
        $credentials = $request->validate([
            'nisn' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'nisn.required' => 'NISN wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // 2. Cari data Siswa berdasarkan NISN
        $siswa = Siswa::where('nisn', $credentials['nisn'])->first();

        // Jika data siswa tidak ditemukan
        if (!$siswa) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'NISN tidak terdaftar.',
                ], 404);
            }

            throw ValidationException::withMessages([
                'nisn' => ['NISN tidak terdaftar pada sistem.'],
            ]);
        }

        // 3. Cek apakah status akun siswa aktif
        if (!$siswa->is_active) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda sedang dinonaktifkan.',
                ], 403);
            }

            throw ValidationException::withMessages([
                'nisn' => ['Akun siswa Anda sedang tidak aktif.'],
            ]);
        }

        // 4. Verifikasi Password
        if (!$siswa->password || !Hash::check($credentials['password'], $siswa->password)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password salah.',
                ], 401);
            }

            throw ValidationException::withMessages([
                'password' => ['Password yang Anda masukkan salah.'],
            ]);
        }

        // 5. Login Siswa via guard 'siswa' & Regenerate Session
        Auth::guard('siswa')->login($siswa);
        $request->session()->regenerate();

        // 5. Return Response (JSON / Web Redirect)
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil login sebagai siswa.',
                'data' => [
                    'siswa' => [
                        'id' => $siswa->id,
                        'nisn' => $siswa->nisn,
                        'nis' => $siswa->nis,
                        'nama_lengkap' => $siswa->nama_lengkap,
                        'kelas_asal' => $siswa->kelas_asal,
                        'jenis_kelamin' => $siswa->jenis_kelamin,
                        'tanggal_lahir' => $siswa->tanggal_lahir,
                    ],
                ],
            ]);
        }

        return redirect()->intended('/siswa/dashboard')->with('success', 'Selamat datang, ' . $siswa->nama_lengkap);
    }

    /**
     * Mendapatkan profil Siswa yang sedang login.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        $siswa = Auth::guard('siswa')->user();

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated / Akses ditolak.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'siswa' => $siswa,
            ],
        ]);
    }

    /**
     * Proses Logout Siswa.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('siswa')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil logout.',
            ]);
        }

        return redirect('/login')->with('success', 'Anda telah berhasil logout.');
    }
}
