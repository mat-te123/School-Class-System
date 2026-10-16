<?php

namespace App\View\Components;

use App\Models\PeriodePendaftaran;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiswaPeriodeData extends Component
{
    public $DataPeriode;

    public $tahunAjaran;

    /**
     * Create a new component instance.
     */
    public function __construct(?string $tahunAjaran)
    {
        $this->tahunAjaran = $tahunAjaran;

        $this->DataPeriode = PeriodePendaftaran::where('tahun_ajaran', $tahunAjaran)->first();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.siswa-periode-data');
    }
}
