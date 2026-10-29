<?php

namespace App\View\Components;

use App\Models\DetailPendaftaranPilihan;
use App\Models\PendaftaranPilihan;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PilihanSiswaProfile extends Component
{
    public $PilihanSiswa;

    /**
     * Create a new component instance.
     */
    public function __construct(public string $siswaId)
    {
        $pendaftaranPilihanIdBySiswaId = PendaftaranPilihan::where('siswa_id', $this->siswaId)
            ->pluck('id');

        $this->PilihanSiswa = DetailPendaftaranPilihan::whereIn('pendaftaran_pilihan_id', $pendaftaranPilihanIdBySiswaId)
            ->has('paketMenuPilihan')
            ->with('paketMenuPilihan:id,nama_menu,rumpun')
            ->get();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.pilihan-siswa-profile');
    }
}
