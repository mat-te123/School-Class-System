<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserAuthController extends Controller
{
    /**
     * Tampilkan form login User / Admin / Guru BK (Web UI).
     */
    public function showLoginForm()
    {
        if (view()->exists('auth.login')) {
            return view('auth.login');
        }

        return response()->json([
            'success' => true,
            'message' => 'Silakan gunakan HTTP POST ke /login dengan payload JSON {"username": "...", "password": "..."}.',
        ]);
    }

    /**
     * Proses Login User (Admin / Guru BK) dan simpan ke Session.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function login(Request $request)
    {
        // 1. Validasi Input
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // 2. Cari User berdasarkan Username beserta relasi Role-nya
        $user = User::with('roleRelation')->where('username', $credentials['username'])->first();

        // Jika user tidak ditemukan
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username tidak terdaftar.',
                ], 401);
            }

            throw ValidationException::withMessages([
                'username' => ['Username tidak terdaftar pada sistem.'],
            ]);
        }

        // 3. Cek apakah status user aktif
        if (!$user->is_active) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda sedang dinonaktifkan.',
                ], 403);
            }

            throw ValidationException::withMessages([
                'username' => ['Akun Anda sedang tidak aktif.'],
            ]);
        }

        // 4. Verifikasi Password
        if (!Hash::check($credentials['password'], $user->password)) {
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

        // 5. Autentikasi User via Guard 'web' & Simpan ke Session
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        // Simpan data peran/role ke dalam session untuk akses cepat
        $request->session()->put('user_id', $user->id);
        $request->session()->put('username', $user->username);
        $request->session()->put('user_role', $user->role);

        // 6. Return Response (JSON / Web Redirect)
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil login sebagai ' . ($user->roleRelation?->label ?? $user->role) . '.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'role' => $user->role,
                        'role_detail' => $user->roleRelation,
                        'is_active' => $user->is_active,
                    ],
                ],
            ]);
        }

        return redirect()->intended('/admin/dashboard')->with('success', 'Selamat datang kembali, ' . $user->username);
    }

    /**
     * Mendapatkan profil User yang sedang login dari session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated / Belum login.',
            ], 401);
        }

        // Load relasi roleRelation jika belum di-load
        $user->load('roleRelation');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'role' => $user->role,
                    'role_detail' => $user->roleRelation,
                    'is_active' => $user->is_active,
                ],
            ],
        ]);
    }

    /**
     * Proses Logout User dan hapus Session.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil logout dari sistem.',
            ]);
        }

        return redirect('/login')->with('success', 'Anda telah berhasil logout.');
    }
}
