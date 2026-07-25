<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pendaftaran_pilihan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->foreignUuid('periode_pendaftaran_id')->constrained('periode_pendaftaran')->onDelete('cascade');
            $table->timestamp('tanggal_submit')->useCurrent();

            $table->unique(['siswa_id', 'periode_pendaftaran_id'], 'unique_siswa_per_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_pilihan');
    }
};
