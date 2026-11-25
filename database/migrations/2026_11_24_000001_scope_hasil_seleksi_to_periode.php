<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->foreignUuid('periode_pendaftaran_id')->nullable()->constrained('periode_pendaftaran')->cascadeOnDelete();
        });

        DB::table('hasil_seleksi')->update([
            'periode_pendaftaran_id' => DB::table('paket_menu_pilihan')
                ->select('periode_id')
                ->whereColumn('id', 'hasil_seleksi.paket_menu_pilihan_id'),
        ]);

        // Hasil tanpa periode paket hanya bisa dipulihkan jika tepat satu pendaftaran mendahului seleksi.
        foreach (DB::table('hasil_seleksi')->whereNull('periode_pendaftaran_id')->lazyById() as $hasil) {
            $periodeIds = DB::table('pendaftaran_pilihan')
                ->where('siswa_id', $hasil->siswa_id)
                ->where('tanggal_submit', '<=', $hasil->tanggal_diproses)
                ->limit(2)
                ->pluck('periode_pendaftaran_id');

            if ($periodeIds->count() !== 1) {
                throw new RuntimeException("Periode hasil seleksi {$hasil->id} tidak dapat ditentukan.");
            }

            DB::table('hasil_seleksi')->where('id', $hasil->id)->update([
                'periode_pendaftaran_id' => $periodeIds->first(),
            ]);
        }

        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->uuid('periode_pendaftaran_id')->nullable(false)->change();
            $table->index('siswa_id');
            $table->dropUnique(['siswa_id']);
            $table->unique(['periode_pendaftaran_id', 'siswa_id'], 'hasil_seleksi_periode_siswa_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->unique('siswa_id');
            $table->dropIndex(['siswa_id']);
            $table->dropUnique('hasil_seleksi_periode_siswa_unique');
        });

        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('periode_pendaftaran_id');
        });
    }
};
