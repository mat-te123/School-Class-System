<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     * Menambahkan kolom untuk jadwal pengajuan pertukaran kelas/paket (FR-10 & FR-11)
     */
    public function up(): void
    {
        Schema::table('periode_pendaftaran', function (Blueprint $table) {
            // Jadwal pengajuan pertukaran kelas/paket (FR-10 & FR-11)
            $table->timestamp('tanggal_mulai_pertukaran')->nullable()->after('tanggal_tutup');
            $table->timestamp('tanggal_selesai_pertukaran')->nullable()->after('tanggal_mulai_pertukaran');
        });
    }

    /**
     * Rollback migrasi.
     */
    public function down(): void
    {
        Schema::table('periode_pendaftaran', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mulai_pertukaran', 'tanggal_selesai_pertukaran']);
        });
    }
};
