<?php

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
            'auth.any' => \App\Http\Middleware\EnsureAnyAuthenticated::class,
        ]);

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
