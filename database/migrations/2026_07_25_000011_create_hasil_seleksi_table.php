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
        Schema::create('hasil_seleksi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('siswa_id')->unique()->constrained('siswa')->onDelete('cascade');
            $table->foreignUuid('paket_menu_pilihan_id')->nullable()->constrained('paket_menu_pilihan');
            $table->integer('pilihan_ke_diterima')->nullable();
            $table->integer('rank_pada_pilihan')->nullable();
            $table->decimal('skor_penempatan', 5, 2);
            $table->decimal('rata_6_mapel', 5, 2);
            $table->enum('mekanisme', ['Pilihan 1', 'Pilihan 2', 'Pilihan 3', 'Pelimpahan Kompetensi', 'Kuota Penuh']);
            $table->timestamp('tanggal_diproses')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_seleksi');
    }
};
