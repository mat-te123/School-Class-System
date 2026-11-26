<?php

namespace App\View\Components;

use App\Models\DetailPendaftaranPilihan;
use App\Models\HasilSeleksi;
use App\Models\PendaftaranPilihan;
use App\Models\PeriodePendaftaran;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PilihanSiswaProfile extends Component
{
    public $PilihanSiswa = [];

    public $HasilPilihanSiswa = [];

    /**
     * Create a new component instance.
     */
    public function __construct(public string $siswaId)
    {
        $pendaftaranPilihan = PendaftaranPilihan::where('siswa_id', $this->siswaId)
            ->select('id', 'periode_pendaftaran_id')->first();

        if (! $pendaftaranPilihan) {
            $this->PilihanSiswa = collect();
            $this->HasilPilihanSiswa = collect();

            return;
        }

        $isPublished = PeriodePendaftaran::where('id', $pendaftaranPilihan->periode_pendaftaran_id)->where('status_pengumuman', 'AKTIF')->exists();

        $this->PilihanSiswa = DetailPendaftaranPilihan::where('pendaftaran_pilihan_id', $pendaftaranPilihan->id)
            ->has('paketMenuPilihan')
            ->with('paketMenuPilihan:id,nama_menu,rumpun')
            ->get();
        if ($isPublished) {
            $this->HasilPilihanSiswa = HasilSeleksi::where('siswa_id', $this->siswaId)
                ->with('paketMenuPilihan:id,nama_menu,rumpun')
                ->get();
        } else {
            $this->HasilPilihanSiswa = collect();
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.pilihan-siswa-profile');
    }
}
