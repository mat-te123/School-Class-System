<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View as ViewFacade;

class SiswaDashboardController extends Controller
{
    /**
     * Dashboard siswa (render Blade view).
     */
    public function index(Request $request): View
    {
        $siswa = Auth::guard('siswa')->user();

        if (ViewFacade::exists('siswa.dashboard')) {
            return ViewFacade::make('siswa.dashboard', compact('siswa'));
        }

        return ViewFacade::make('siswa.dashboard.index', compact('siswa'));
    }

    /**
     * Alias method untuk dashboard.
     */
    public function dashboard(Request $request): View
    {
        return $this->index($request);
    }
}
