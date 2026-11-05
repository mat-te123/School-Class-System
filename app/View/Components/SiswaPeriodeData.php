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
        $now = now();

        $periode = null;

        
        if (! empty($tahunAjaran)) {
            $periode = PeriodePendaftaran::where('tahun_ajaran', $tahunAjaran)
                ->where('is_active', true)
                ->where('tanggal_buka', '<=', $now)
                ->where('tanggal_tutup', '>=', $now)
                ->first();
        }

        
        if (! $periode) {
            $periode = PeriodePendaftaran::where('is_active', true)
                ->where('tanggal_buka', '<=', $now)
                ->where('tanggal_tutup', '>=', $now)
                ->first();
        }

        // Jika periode sudah lewat (atau belum ada periode yang dibuka), jangan tampilkan di dashboard
        $this->DataPeriode = $periode;
    }

    /**
     * Tentukan apakah komponen harus dirender di view.
     */
    public function shouldRender(): bool
    {
        return $this->DataPeriode !== null;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        if (! $this->DataPeriode) {
            return '';
        }

        return view('components.siswa-periode-data');
    }
}
