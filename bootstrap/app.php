<?php

use App\Http\Middleware\EnsureAnyAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.any' => EnsureAnyAuthenticated::class,
        ]);

        // Guest pada route siswa diarahkan ke form login siswa (/);
        // route lain (admin/guru BK) ke form login admin (/login/admin).
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->routeIs('siswa.*') || $request->is('siswa', 'siswa/*')) {
                return route('siswa.login.form');
            }

            return route('login');
        });

        $middleware->validateCsrfTokens(except: [
            'login',
            'login/*',
            'logout',
            'logout/*',
            'register/*',
            'leger/*',
            'nilai-siswa/*',
            'kelas-asal',
            'kelas-asal/*',
            'paket-menu-pilihan',
            'paket-menu-pilihan/*',
            'kriteria-bobot-menu',
            'kriteria-bobot-menu/*',
            'master-mata-pelajaran',
            'master-mata-pelajaran/*',
            'siswa',
            'siswa/*',
            'periode-penjurusan',
            'periode-penjurusan/*',
            'proyeksi-universitas',
            'proyeksi-universitas/*',
            'program-studi',
            'program-studi/*',
            'admin/*',
            'laporan-pesan',
            'laporan-pesan/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
