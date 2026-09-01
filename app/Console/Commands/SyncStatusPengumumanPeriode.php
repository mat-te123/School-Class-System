<?php

namespace App\Console\Commands;

use App\Models\PeriodePendaftaran;
use Illuminate\Console\Command;

class SyncStatusPengumumanPeriode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'periode:sync-pengumuman';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi status_pengumuman menjadi AKTIF untuk periode yang telah melewati tanggal_pengumuman.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = PeriodePendaftaran::where('status_pengumuman', 'NON-AKTIF')
            ->whereNotNull('tanggal_pengumuman')
            ->where('tanggal_pengumuman', '<=', now())
            ->update(['status_pengumuman' => 'AKTIF']);

        $this->info("Berhasil mengaktifkan pengumuman untuk {$count} periode.");

        return self::SUCCESS;
    }
}
