<?php

namespace App\View\Components;

use App\Models\DetailNilaiSiswa;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NilaiSiswaProfile extends Component
{
    public $nilaiData;

    public $rata_rata;

    /**
     * Create a new component instance.
     */
    public function __construct(public string $siswaId)
    {
        $this->nilaiData = DetailNilaiSiswa::from('detail_nilai_siswa as dns')
            ->select('dns.*')
            ->join('nilai_leger_siswa as nls', 'dns.nilai_leger_siswa_id', '=', 'nls.id')
            ->where('nls.siswa_id', $this->siswaId)
            ->with('mataPelajaran:id,kode_mapel,nama_mapel') // Opsional: eager load nama mapel
            ->get();

        $this->rata_rata = round($this->nilaiData->avg('nilai_angka') ?? 0, 2);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.nilai-siswa-profile');
    }
}
