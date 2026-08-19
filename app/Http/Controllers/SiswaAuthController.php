<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SiswaAuthController extends Controller
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
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'NISN tidak terdaftar.',
                ], 401);
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
                        'angkatan' => $siswa->angkatan,
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
    public function profile(Request $request)
    {
        $siswa = Auth::guard('siswa')->user();

        if (!$siswa) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated / Akses ditolak.',
                ], 401);
            }
            abort(401, 'Unauthenticated / Akses ditolak.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'siswa' => $siswa,
                ],
            ]);
        }

        return view('siswa.profile', compact('siswa'));
    }

    /**
     * Tampilkan form registrasi siswa (Web UI).
     */
    public function showRegisterForm()
    {
        if (view()->exists('siswa.register')) {
            return view('siswa.register');
        }

        return response()->json([
            'success' => true,
            'message' => 'Silakan gunakan HTTP POST ke /register/siswa/check dengan payload JSON {"nisn": "..."}.',
        ]);
    }

    /**
     * Tahap 1: Cek & Validasi Awal NISN untuk Registrasi Siswa.
     * Memastikan NISN terdaftar di sistem dan password masih kosong.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function checkNisn(Request $request)
    {
        $request->validate([
            'nisn' => ['required', 'string'],
        ], [
            'nisn.required' => 'NISN wajib diisi.',
        ]);

        $siswa = Siswa::where('nisn', $request->nisn)->first();

        // 1. Cek apakah NISN terdaftar di sistem
        if (!$siswa) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'NISN tidak terdaftar pada sistem sekolah.',
                ], 404);
            }

            throw ValidationException::withMessages([
                'nisn' => ['NISN tidak terdaftar pada sistem sekolah.'],
            ]);
        }

        // 2. Cek apakah password siswa sudah pernah diisi / didaftarkan
        if (!empty($siswa->password)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun dengan NISN ini sudah terdaftar. Silakan lakukan login.',
                ], 422);
            }

            throw ValidationException::withMessages([
                'nisn' => ['Akun dengan NISN ini sudah terdaftar. Silakan lakukan login.'],
            ]);
        }

        // 3. Jika NISN valid dan password masih kosong
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'NISN valid. Silakan lengkapi data registrasi Anda.',
                'data' => [
                    'nisn' => $siswa->nisn,
                    'nama_lengkap' => $siswa->nama_lengkap,
                    'kelas_asal' => $siswa->kelas_asal,
                    'can_register' => true,
                ],
            ]);
        }

        return back()->with([
            'step' => 2,
            'siswa' => $siswa,
        ]);
    }

    /**
     * Tahap 2: Proses Registrasi Siswa dengan melengkapi data
     * jenis kelamin, tanggal lahir, dan password.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function register(Request $request)
    {
        // 1. Validasi Input Lengkap
        $validated = $request->validate([
            'nisn' => ['required', 'string'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tanggal_lahir' => ['required', 'date'],
            'angkatan' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nisn.required' => 'NISN wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib diisi.',
            'jenis_kelamin.in' => 'Jenis kelamin harus L (Laki-laki) atau P (Perempuan).',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'angkatan.required' => 'Angkatan wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // 2. Cari Siswa berdasarkan NISN
        $siswa = Siswa::where('nisn', $validated['nisn'])->first();

        if (!$siswa) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'NISN tidak terdaftar pada sistem sekolah.',
                ], 404);
            }

            throw ValidationException::withMessages([
                'nisn' => ['NISN tidak terdaftar pada sistem sekolah.'],
            ]);
        }

        // 3. Pastikan password masih kosong (belum pernah diisi)
        if (!empty($siswa->password)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun dengan NISN ini sudah terdaftar. Silakan lakukan login.',
                ], 422);
            }

            throw ValidationException::withMessages([
                'nisn' => ['Akun dengan NISN ini sudah terdaftar. Silakan lakukan login.'],
            ]);
        }

        // 4. Update data siswa & aktifkan akun
        $siswa->update([
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'angkatan' => $validated['angkatan'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        // 5. Return Response
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Registrasi akun siswa berhasil. Silakan login.',
                'data' => [
                    'siswa' => [
                        'id' => $siswa->id,
                        'nisn' => $siswa->nisn,
                        'nama_lengkap' => $siswa->nama_lengkap,
                        'jenis_kelamin' => $siswa->jenis_kelamin,
                        'tanggal_lahir' => $siswa->tanggal_lahir?->format('Y-m-d'),
                        'angkatan' => $siswa->angkatan,
                        'is_active' => $siswa->is_active,
                    ],
                ],
            ]);
        }

        return redirect('/login/siswa')->with('success', 'Registrasi berhasil. Silakan login menggunakan NISN dan password Anda.');
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
