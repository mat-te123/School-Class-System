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
        Schema::create('periode_pendaftaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_periode', 100);
            $table->string('tahun_ajaran', 10);
            $table->string('gelombang', 20)->default('Utama');
            $table->integer('max_pilihan_siswa')->default(3);
            $table->timestamp('tanggal_buka');
            $table->timestamp('tanggal_tutup');
            $table->enum('status_pengumuman', ['AKTIF', 'NON-AKTIF'])->default('NON-AKTIF');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_pendaftaran');
    }
};
